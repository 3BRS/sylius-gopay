<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\RefundPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

#[AsMessageHandler]
final readonly class RefundPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayApiInterface $goPayApi,
    ) {
    }

    /**
     * Handles @see PaymentRequestInterface::ACTION_REFUND
     *
     * Refund a payment at GoPay (full or partial refund)
     */
    public function __invoke(RefundPaymentRequest $refundPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($refundPaymentRequest);

        // Get external payment ID from original capture request
        $captureRequest = $this->findCaptureRequest($paymentRequest);
        if ($captureRequest === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        /** @var array<string, mixed> $capturePayload */
        $capturePayload = $captureRequest->getPayload() ?? [];
        $externalPaymentId = isset($capturePayload[PaymentConstants::EXTERNAL_PAYMENT_ID]) && is_int($capturePayload[PaymentConstants::EXTERNAL_PAYMENT_ID])
            ? $capturePayload[PaymentConstants::EXTERNAL_PAYMENT_ID]
            : null;

        if ($externalPaymentId === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Authorize GoPay API
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig() ?? [];
        $this->authorizeGoPayApi($gatewayConfig);

        // Get refund amount from payment request
        $payment = $paymentRequest->getPayment();
        assert($payment !== null);
        $amount = $payment->getAmount();

        // Execute refund at GoPay
        $goPayResponse = $this->goPayApi->refund($externalPaymentId, $amount);

        if ($goPayResponse === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Store refund information in payload
        $payload = [
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
            PaymentConstants::REFUND_ID => $goPayResponse->json['id'] ?? null,
            'refund_state' => $goPayResponse->json['result'] ?? null,
        ];
        $paymentRequest->setPayload($payload);

        /** @var array<string, mixed> $responseData */
        $responseData = $goPayResponse->json;
        $paymentRequest->setResponseData($responseData);

        // Check if refund was successful
        $result = $goPayResponse->json['result'] ?? null;
        if ($result === 'FINISHED') {
            $this->completePaymentRequest($paymentRequest);
        } else {
            $this->failPaymentRequest($paymentRequest);
        }
    }

    private function findCaptureRequest(PaymentRequestInterface $paymentRequest): ?PaymentRequestInterface
    {
        $payment = $paymentRequest->getPayment();
        if ($payment === null) {
            return null;
        }

        foreach ($payment->getPaymentRequests() as $request) {
            if ($request->getAction() === PaymentRequestInterface::ACTION_CAPTURE) {
                return $request;
            }
        }

        return null;
    }

    private function completePaymentRequest(PaymentRequestInterface $paymentRequest): void
    {
        if ($this->stateMachine->can(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        )) {
            $this->stateMachine->apply(
                $paymentRequest,
                PaymentRequestTransitions::GRAPH,
                PaymentRequestTransitions::TRANSITION_COMPLETE,
            );
        }
    }

    private function failPaymentRequest(PaymentRequestInterface $paymentRequest): void
    {
        if ($this->stateMachine->can(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_FAIL,
        )) {
            $this->stateMachine->apply(
                $paymentRequest,
                PaymentRequestTransitions::GRAPH,
                PaymentRequestTransitions::TRANSITION_FAIL,
            );
        }
    }

    /**
     * @param array<string, mixed> $gatewayConfig
     */
    private function authorizeGoPayApi(array $gatewayConfig): void
    {
        $this->goPayApi->authorize(
            goId: (string) ($gatewayConfig['goid'] ?? ''),
            clientId: (string) ($gatewayConfig['clientId'] ?? ''),
            clientSecret: (string) ($gatewayConfig['clientSecret'] ?? ''),
            isProductionMode: (bool) ($gatewayConfig['isProductionMode'] ?? false),
        );
    }
}
