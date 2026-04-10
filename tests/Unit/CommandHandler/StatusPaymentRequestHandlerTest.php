<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Unit\CommandHandler;

use GoPay\Http\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\StatusPaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\CommandHandler\StatusPaymentRequestHandler;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;
use ThreeBRS\SyliusGoPayPlugin\Service\ExternalPaymentIdResolverInterface;
use Sylius\Component\Payment\Model\PaymentInterface;

#[AllowMockObjectsWithoutExpectations]
final class StatusPaymentRequestHandlerTest extends TestCase
{
    private PaymentRequestProviderInterface $paymentRequestProvider;

    private StateMachineInterface $stateMachine;

    private GoPayApiInterface $goPayApi;

    private PaymentRequestRepositoryInterface $paymentRequestRepository;

    private ExternalPaymentIdResolverInterface $externalPaymentIdResolver;

    private PaymentRequestInterface $paymentRequest;

    private PaymentRequestInterface $capturePaymentRequest;

    private PaymentInterface $payment;

    private PaymentMethodInterface $paymentMethod;

    protected function setUp(): void
    {
        $this->paymentRequestProvider = $this->createMock(PaymentRequestProviderInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);
        $this->goPayApi = $this->createMock(GoPayApiInterface::class);
        $this->paymentRequestRepository = $this->createMock(PaymentRequestRepositoryInterface::class);
        $this->externalPaymentIdResolver = $this->createMock(ExternalPaymentIdResolverInterface::class);

        $this->paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $this->capturePaymentRequest = $this->createMock(PaymentRequestInterface::class);
        $this->payment = $this->createMock(\Sylius\Component\Payment\Model\PaymentInterface::class);
        $this->paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);

        $gatewayConfig->method('getConfig')->willReturn([
            'goid' => '123',
            'clientId' => 'client',
            'clientSecret' => 'secret',
            'isProductionMode' => false,
        ]);
        $this->paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);
        $this->paymentRequest->method('getMethod')->willReturn($this->paymentMethod);
        $this->paymentRequest->method('getPayment')->willReturn($this->payment);
    }

    private function createHandler(): StatusPaymentRequestHandler
    {
        return new StatusPaymentRequestHandler(
            $this->paymentRequestProvider,
            $this->stateMachine,
            $this->goPayApi,
            $this->paymentRequestRepository,
            $this->externalPaymentIdResolver,
        );
    }

    private function createGoPayResponse(string $state): Response
    {
        $response = new Response();
        $response->json = ['state' => $state];
        $response->statusCode = 200;

        return $response;
    }

    #[Test]
    public function it_falls_back_to_capture_request_for_external_payment_id(): void
    {
        // Status request has no external payment ID in payload
        $this->paymentRequest->method('getPayload')->willReturn([]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        // Capture request has the external payment ID
        $this->capturePaymentRequest->method('getPayload')->willReturn([
            PaymentConstants::EXTERNAL_PAYMENT_ID => 12345,
        ]);
        $this->paymentRequestRepository->method('findOneByActionPaymentAndMethod')
            ->with(PaymentRequestInterface::ACTION_CAPTURE, $this->payment, $this->paymentMethod)
            ->willReturn($this->capturePaymentRequest);

        $this->externalPaymentIdResolver->method('extractExternalPaymentId')
            ->with([PaymentConstants::EXTERNAL_PAYMENT_ID => 12345])
            ->willReturn(12345);

        $goPayResponse = $this->createGoPayResponse(GoPayApiInterface::PAID);
        $this->goPayApi->expects($this->once())->method('retrieve')->with(12345)->willReturn($goPayResponse);

        $this->stateMachine->method('can')->willReturn(true);
        $this->stateMachine->expects($this->atLeastOnce())->method('apply');

        $handler = $this->createHandler();
        $handler($command);
    }

    #[Test]
    public function it_returns_early_when_no_external_payment_id_found_anywhere(): void
    {
        // Status request has no external payment ID
        $this->paymentRequest->method('getPayload')->willReturn([]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        // Capture request also has no external payment ID
        $this->paymentRequestRepository->method('findOneByActionPaymentAndMethod')
            ->willReturn(null);

        $this->goPayApi->expects($this->never())->method('retrieve');

        $handler = $this->createHandler();
        $handler($command);
    }

    #[Test]
    public function it_transitions_payment_to_complete_when_gopay_returns_paid(): void
    {
        $this->paymentRequest->method('getPayload')->willReturn([
            PaymentConstants::EXTERNAL_PAYMENT_ID => 12345,
        ]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        $goPayResponse = $this->createGoPayResponse(GoPayApiInterface::PAID);
        $this->goPayApi->method('retrieve')->willReturn($goPayResponse);

        $this->stateMachine->method('can')->willReturn(true);

        $expectedTransitions = [];
        $this->stateMachine->expects($this->atLeastOnce())->method('apply')
            ->willReturnCallback(function ($subject, $graph, $transition) use (&$expectedTransitions) {
                $expectedTransitions[] = [$subject instanceof PaymentRequestInterface ? 'request' : 'payment', $transition];
            });

        $handler = $this->createHandler();
        $handler($command);

        $this->assertContains(
            ['request', PaymentRequestTransitions::TRANSITION_COMPLETE],
            $expectedTransitions,
        );
        $this->assertContains(
            ['payment', PaymentTransitions::TRANSITION_COMPLETE],
            $expectedTransitions,
        );
    }

    #[Test]
    public function it_transitions_payment_to_authorize_when_gopay_returns_authorized(): void
    {
        $this->paymentRequest->method('getPayload')->willReturn([
            PaymentConstants::EXTERNAL_PAYMENT_ID => 12345,
        ]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        $goPayResponse = $this->createGoPayResponse(GoPayApiInterface::AUTHORIZED);
        $this->goPayApi->method('retrieve')->willReturn($goPayResponse);

        $this->stateMachine->method('can')->willReturn(true);

        $expectedTransitions = [];
        $this->stateMachine->expects($this->atLeastOnce())->method('apply')
            ->willReturnCallback(function ($subject, $graph, $transition) use (&$expectedTransitions) {
                $expectedTransitions[] = [$subject instanceof PaymentRequestInterface ? 'request' : 'payment', $transition];
            });

        $handler = $this->createHandler();
        $handler($command);

        $this->assertContains(
            ['request', PaymentRequestTransitions::TRANSITION_COMPLETE],
            $expectedTransitions,
        );
        $this->assertContains(
            ['payment', PaymentTransitions::TRANSITION_AUTHORIZE],
            $expectedTransitions,
        );
    }

    #[Test]
    public function it_transitions_payment_to_fail_when_gopay_returns_canceled(): void
    {
        $this->paymentRequest->method('getPayload')->willReturn([
            PaymentConstants::EXTERNAL_PAYMENT_ID => 12345,
        ]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        $goPayResponse = $this->createGoPayResponse(GoPayApiInterface::CANCELED);
        $this->goPayApi->method('retrieve')->willReturn($goPayResponse);

        $this->stateMachine->method('can')->willReturn(true);

        $expectedTransitions = [];
        $this->stateMachine->expects($this->atLeastOnce())->method('apply')
            ->willReturnCallback(function ($subject, $graph, $transition) use (&$expectedTransitions) {
                $expectedTransitions[] = [$subject instanceof PaymentRequestInterface ? 'request' : 'payment', $transition];
            });

        $handler = $this->createHandler();
        $handler($command);

        $this->assertContains(
            ['request', PaymentRequestTransitions::TRANSITION_FAIL],
            $expectedTransitions,
        );
        $this->assertContains(
            ['payment', PaymentTransitions::TRANSITION_FAIL],
            $expectedTransitions,
        );
    }

    #[Test]
    public function it_transitions_payment_to_fail_when_gopay_returns_timeouted(): void
    {
        $this->paymentRequest->method('getPayload')->willReturn([
            PaymentConstants::EXTERNAL_PAYMENT_ID => 12345,
        ]);

        $command = new StatusPaymentRequest('test-hash');
        $this->paymentRequestProvider->method('provide')->with($command)->willReturn($this->paymentRequest);

        $goPayResponse = $this->createGoPayResponse(GoPayApiInterface::TIMEOUTED);
        $this->goPayApi->method('retrieve')->willReturn($goPayResponse);

        $this->stateMachine->method('can')->willReturn(true);

        $expectedTransitions = [];
        $this->stateMachine->expects($this->atLeastOnce())->method('apply')
            ->willReturnCallback(function ($subject, $graph, $transition) use (&$expectedTransitions) {
                $expectedTransitions[] = [$subject instanceof PaymentRequestInterface ? 'request' : 'payment', $transition];
            });

        $handler = $this->createHandler();
        $handler($command);

        $this->assertContains(
            ['request', PaymentRequestTransitions::TRANSITION_FAIL],
            $expectedTransitions,
        );
        $this->assertContains(
            ['payment', PaymentTransitions::TRANSITION_FAIL],
            $expectedTransitions,
        );
    }
}
