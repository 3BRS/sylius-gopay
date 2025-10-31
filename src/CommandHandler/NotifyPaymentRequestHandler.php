<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use GoPay\Http\Response;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\NotifyPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

#[AsMessageHandler]
final readonly class NotifyPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayApiInterface $goPayApi,
    ) {
    }

    /**
     * Handles @see PaymentRequestInterface::ACTION_NOTIFY
     *
     * Process GoPay callback/webhook notifications by retrieving current payment status from GoPay
     */
    public function __invoke(NotifyPaymentRequest $notifyPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($notifyPaymentRequest);
        $responseData = $notifyPaymentRequest->getResponseData();

        // Store the response data
        /** @var array<string, mixed> $responseDataTyped */
        $responseDataTyped = $responseData;
        $paymentRequest->setResponseData($responseDataTyped);

        // Extract payment ID from response and retrieve current status from GoPay
        $externalPaymentId = isset($responseData['id']) && is_int($responseData['id'])
            ? $responseData['id']
            : null;

        if ($externalPaymentId === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Authorize GoPay API with payment method configuration
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig() ?? [];
        $this->authorizeGoPayApi($gatewayConfig);

        // Retrieve current payment status from GoPay
        $goPayResponse = $this->goPayApi->retrieve($externalPaymentId);

        if ($goPayResponse === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Store external payment ID in payload for future reference
        $payload = $paymentRequest->getPayload() ?? [];
        $payload[PaymentConstants::EXTERNAL_PAYMENT_ID] = $externalPaymentId;
        $payload[PaymentConstants::GOPAY_STATUS] = $goPayResponse->json['state'] ?? null;
        $paymentRequest->setPayload($payload);

        // Check if payment is successful
        if ($this->isPaymentSuccessful($goPayResponse)) {
            $this->completePaymentRequest($paymentRequest);
            $this->completePayment($paymentRequest);
        } else {
            $this->failPaymentRequest($paymentRequest);
        }
    }

    private function isPaymentSuccessful(Response $goPayResponse): bool
    {
        $state = $goPayResponse->json['state'] ?? null;

        return in_array($state, [
            GoPayApiInterface::PAID,
            GoPayApiInterface::AUTHORIZED,
        ], true);
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

    private function completePayment(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();
        assert($payment !== null, 'Payment must not be null when processing successful payment request');

        if ($this->stateMachine->can(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_COMPLETE,
        )) {
            $this->stateMachine->apply(
                $payment,
                PaymentTransitions::GRAPH,
                PaymentTransitions::TRANSITION_COMPLETE,
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
