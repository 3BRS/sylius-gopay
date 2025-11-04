<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Api;

use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use GoPay\Http\Response;

interface GoPayApiInterface
{
    public const CREATED = 'CREATED';

    public const PAYMENT_METHOD_CHOSEN = 'PAYMENT_METHOD_CHOSEN';

    public const AUTHORIZED = 'AUTHORIZED';

    public const PAID = 'PAID';

    public const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    public const REFUNDED = 'REFUNDED';

    public const CANCELED = 'CANCELED';

    /**
     * Customer does not pay in time
     *
     * @noinspection PhpUnused
     */
    public const TIMEOUTED = 'TIMEOUTED';

    /**
     * payment was finalized, for example by a refund
     *
     * @noinspection PhpUnused
     */
    public const RESULT_FINISHED = 'FINISHED';

    public function authorize(
        string $goId,
        string $clientId,
        string $clientSecret,
        bool $isProductionMode,
        string $language = Language::ENGLISH,
        ?string $gatewayUrl = null,
        string $scope = TokenScope::ALL,
        int $timeout = 30,
    ): void;

    /**
     * @param array<string, mixed> $order
     */
    public function create(array $order): Response;

    public function retrieve(int $paymentId): Response;

    public function voidAuthorization(int $paymentId): Response;

    public function refund(
        int $paymentId,
        int $amount,
    ): Response;

    public function captureAuthorization(int $paymentId): Response;

    public function captureAuthorizationPartial(int $paymentId, int $amount): Response;
}
