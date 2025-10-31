<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\StatusPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayApiInterface $goPayApi,
    ) {
    }

    /**
     * Handles @see PaymentRequestInterface::ACTION_STATUS
     *
     * Check payment status at GoPay and update payment request state accordingly
     */
    public function __invoke(StatusPaymentRequest $statusPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($statusPaymentRequest);

        // Get external payment ID from payload
        /** @var array<string, mixed> $payload */
        $payload = $paymentRequest->getPayload() ?? [];
        $externalPaymentId = isset($payload[PaymentConstants::EXTERNAL_PAYMENT_ID]) && is_int($payload[PaymentConstants::EXTERNAL_PAYMENT_ID])
            ? $payload[PaymentConstants::EXTERNAL_PAYMENT_ID]
            : null;

        if ($externalPaymentId === null) {
            // No external payment ID, nothing to check
            return;
        }

        // Authorize GoPay API
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig() ?? [];
        $this->authorizeGoPayApi($gatewayConfig);

        // Retrieve current status from GoPay
        $goPayResponse = $this->goPayApi->retrieve($externalPaymentId);

        if ($goPayResponse === null) {
            return;
        }

        // Update payload with current status
        $payload[PaymentConstants::GOPAY_STATUS] = $goPayResponse->json['state'] ?? null;
        $paymentRequest->setPayload($payload);

        /** @var array<string, mixed> $responseData */
        $responseData = $goPayResponse->json;
        $paymentRequest->setResponseData($responseData);

        // Update payment request state based on GoPay status
        $state = $goPayResponse->json['state'] ?? null;

        if (in_array($state, [GoPayApiInterface::PAID, GoPayApiInterface::AUTHORIZED], true)) {
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
        } elseif (in_array($state, [GoPayApiInterface::CANCELED, GoPayApiInterface::TIMEOUTED], true)) {
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
