<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use GoPay\Http\Response;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\StatusPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;
use ThreeBRS\SyliusGoPayPlugin\Service\ExternalPaymentIdResolver;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    /** @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository */
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayApiInterface $goPayApi,
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
        private ExternalPaymentIdResolver $externalPaymentIdResolver,
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

        // Fallback: look up the CAPTURE payment request for the external payment ID
        if ($externalPaymentId === null) {
            $capturePaymentRequest = $this->paymentRequestRepository->findOneByActionPaymentAndMethod(
                PaymentRequestInterface::ACTION_CAPTURE,
                $paymentRequest->getPayment(),
                $paymentRequest->getMethod(),
            );

            if ($capturePaymentRequest !== null) {
                /** @var array<string, mixed> $capturePayload */
                $capturePayload = $capturePaymentRequest->getPayload() ?? [];
                $externalPaymentId = $this->externalPaymentIdResolver->extractExternalPaymentId($capturePayload);
            }
        }

        if ($externalPaymentId === null) {
            // No external payment ID found anywhere, nothing to check
            return;
        }

        // Authorize GoPay API
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig() ?? [];
        $this->authorizeGoPayApi($gatewayConfig);

        // Retrieve current status from GoPay
        $goPayResponse = $this->goPayApi->retrieve($externalPaymentId);

        // Update payload with current status
        $payload[PaymentConstants::GOPAY_STATUS] = $goPayResponse->json['state'] ?? null;
        $paymentRequest->setPayload($payload);

        /** @var array<string, mixed> $responseData */
        $responseData = $goPayResponse->json;
        $paymentRequest->setResponseData($responseData);

        // Check payment status and transition accordingly
        if ($this->isPaymentPaid($goPayResponse)) {
            $this->completePaymentRequest($paymentRequest);
            $this->completePayment($paymentRequest);
        } elseif ($this->isPaymentAuthorized($goPayResponse)) {
            $this->completePaymentRequest($paymentRequest);
            $this->authorizePayment($paymentRequest);
        } elseif (in_array($goPayResponse->json['state'] ?? null, [GoPayApiInterface::CANCELED, GoPayApiInterface::TIMEOUTED], true)) {
            $this->failPaymentRequest($paymentRequest);
            $this->failPayment($paymentRequest);
        }
    }

    private function isPaymentPaid(Response $goPayResponse): bool
    {
        return ($goPayResponse->json['state'] ?? null) === GoPayApiInterface::PAID;
    }

    private function isPaymentAuthorized(Response $goPayResponse): bool
    {
        return ($goPayResponse->json['state'] ?? null) === GoPayApiInterface::AUTHORIZED;
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

    private function authorizePayment(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();

        if ($this->stateMachine->can(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_AUTHORIZE,
        )) {
            $this->stateMachine->apply(
                $payment,
                PaymentTransitions::GRAPH,
                PaymentTransitions::TRANSITION_AUTHORIZE,
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

    private function failPayment(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();

        if ($this->stateMachine->can(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_FAIL,
        )) {
            $this->stateMachine->apply(
                $payment,
                PaymentTransitions::GRAPH,
                PaymentTransitions::TRANSITION_FAIL,
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
