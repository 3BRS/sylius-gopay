<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Api;

use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use GoPay\Http\Response;
use GoPay\Payments;
use ThreeBRS\SyliusGoPayPlugin\Factory\GoPayPaymentsFactoryInterface;

final class GoPayApi implements GoPayApiInterface
{
    private Payments $gopay;

    public function __construct(
        private readonly GoPayPaymentsFactoryInterface $gopayPaymentsFactory,
    ) {
    }

    /**
     * For supported languages @see \GoPay\Definition\Language
     *
     * For client ID and client secret see @see https://doc.gopay.com/#access-token
     *
     * For gateway URL see @see https://help.gopay.com/en/s/uY
     */
    public function authorize(
        string $goId,
        string $clientId,
        string $clientSecret,
        bool $isProductionMode,
        string $language = Language::ENGLISH,
        ?string $gatewayUrl = null,
        string $scope = TokenScope::ALL,
        int $timeout = 30,
    ): void {
        $this->gopay = $this->gopayPaymentsFactory->createPayments(
            goId: $goId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            isProductionMode: $isProductionMode,
            language: $language,
            gatewayUrl: $gatewayUrl,
            scope: $scope,
            timeout: $timeout,
        );
    }

    /**
     * @param array<string, mixed> $order
     */
    public function create(array $order): Response
    {
        // @phpstan-ignore return.type
        return $this->gopay->createPayment($order);
    }

    public function retrieve(int $paymentId): Response
    {
        // @phpstan-ignore return.type
        return $this->gopay->getStatus($paymentId);
    }

    public function voidAuthorization(int $paymentId): Response
    {
        // @phpstan-ignore return.type
        return $this->gopay->voidAuthorization($paymentId);
    }

    /**
     * Note: refund requires GoPay token with scope=payment-all
     *
     * @see https://help.gopay.com/en/knowledge-base/integration-of-payment-gateway/integration-of-payment-gateway-1/refunds
     *
     * @param int $amount Use full price to refund the whole payment, or partial amount to do partial refund (partial refund can be done only after 24 hours from the payment)
     */
    public function refund(
        int $paymentId,
        int $amount,
    ): Response {
        // @phpstan-ignore return.type
        return $this->gopay->refundPayment($paymentId, $amount);
    }

    /**
     * Capture the full amount of a pre-authorized payment
     *
     * @see https://doc.gopay.com/#pre-authorization
     */
    public function captureAuthorization(int $paymentId): Response
    {
        // @phpstan-ignore return.type
        return $this->gopay->captureAuthorization($paymentId);
    }

    /**
     * Capture a partial amount of a pre-authorized payment
     *
     * @see https://doc.gopay.com/#pre-authorization
     *
     * @param int $amount Amount to capture (must be less than or equal to authorized amount)
     */
    public function captureAuthorizationPartial(int $paymentId, int $amount): Response
    {
        // @phpstan-ignore return.type
        return $this->gopay->captureAuthorizationPartial($paymentId, [
            'amount' => $amount,
        ]);
    }
}
