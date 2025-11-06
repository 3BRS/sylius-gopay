<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\CompleteAuthorizedPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

#[AsMessageHandler]
final readonly class CompleteAuthorizedPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayApiInterface $goPayApi,
    ) {
    }

    /**
     * Handles capturing an authorized payment
     *
     * This is triggered when admin manually captures an authorized payment
     * by transitioning payment state from AUTHORIZED to COMPLETED
     */
    public function __invoke(CompleteAuthorizedPaymentRequest $completeAuthorizedPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($completeAuthorizedPaymentRequest);

        // Find the original CAPTURE/AUTHORIZE request to get external payment ID
        $authorizeRequest = $this->findAuthorizeRequest($paymentRequest);
        if ($authorizeRequest === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        /** @var array<string, mixed> $authorizePayload */
        $authorizePayload = $authorizeRequest->getPayload() ?? [];
        $externalPaymentId = isset($authorizePayload[PaymentConstants::EXTERNAL_PAYMENT_ID]) && is_int($authorizePayload[PaymentConstants::EXTERNAL_PAYMENT_ID])
            ? $authorizePayload[PaymentConstants::EXTERNAL_PAYMENT_ID]
            : null;

        if ($externalPaymentId === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Authorize GoPay API
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig() ?? [];
        $this->authorizeGoPayApi($gatewayConfig);

        // Get capture amount from payment
        $payment = $paymentRequest->getPayment();
        $amount = $payment->getAmount();

        if ($amount === null) {
            $this->failPaymentRequest($paymentRequest);

            return;
        }

        // Get authorized amount from original request
        $authorizedAmount = $this->getAuthorizedAmount($authorizePayload);

        // Determine if full or partial capture
        $goPayResponse = ($amount >= $authorizedAmount)
            ? $this->goPayApi->captureAuthorization($externalPaymentId)
            : $this->goPayApi->captureAuthorizationPartial($externalPaymentId, $amount);

        // Store capture information in payload
        $payload = [
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
            'capture_result' => $goPayResponse->json['result'] ?? null,
            'capture_state' => $goPayResponse->json['state'] ?? null,
        ];
        $paymentRequest->setPayload($payload);

        /** @var array<string, mixed> $responseData */
        $responseData = $goPayResponse->json;
        $paymentRequest->setResponseData($responseData);

        // Check if capture was successful
        $result = $goPayResponse->json['result'] ?? null;
        if ($result === GoPayApiInterface::RESULT_FINISHED) {
            $this->completePaymentRequest($paymentRequest);
        } else {
            $this->failPaymentRequest($paymentRequest);
        }
    }

    private function findAuthorizeRequest(PaymentRequestInterface $paymentRequest): ?PaymentRequestInterface
    {
        $payment = $paymentRequest->getPayment();

        foreach ($payment->getPaymentRequests() as $request) {
            if (in_array($request->getAction(), [
                PaymentRequestInterface::ACTION_CAPTURE,
                PaymentRequestInterface::ACTION_AUTHORIZE,
            ], true)) {
                return $request;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $authorizePayload
     */
    private function getAuthorizedAmount(array $authorizePayload): int
    {
        // The amount is stored in the payload from CapturePaymentRequestHandler
        return isset($authorizePayload['amount']) && is_int($authorizePayload['amount'])
            ? $authorizePayload['amount']
            : 0;
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
            // @phpstan-ignore-next-line
            goId: (string) ($gatewayConfig['goid'] ?? ''),
            // @phpstan-ignore-next-line
            clientId: (string) ($gatewayConfig['clientId'] ?? ''),
            // @phpstan-ignore-next-line
            clientSecret: (string) ($gatewayConfig['clientSecret'] ?? ''),
            isProductionMode: (bool) ($gatewayConfig['isProductionMode'] ?? false),
        );
    }
}
