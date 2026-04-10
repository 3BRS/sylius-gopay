<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Unit\ResponseProvider;

use Doctrine\ORM\EntityManagerInterface;
use GoPay\Http\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\ResponseProvider\CaptureHttpResponseProvider;

#[AllowMockObjectsWithoutExpectations]
final class CaptureHttpResponseProviderTest extends TestCase
{
    #[Test]
    public function it_calls_entity_manager_flush_after_creating_payment(): void
    {
        $goPayApi = $this->createMock(GoPayApiInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $router->method('generate')->willReturn('https://example.com/notify');

        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn([
            'goid' => '123',
            'clientId' => 'client',
            'clientSecret' => 'secret',
            'isProductionMode' => false,
        ]);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getLocaleCode')->willReturn('en');

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getAction')->willReturn(PaymentRequestInterface::ACTION_CAPTURE);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);
        $paymentRequest->method('getPayment')->willReturn($payment);
        $paymentRequest->method('getHash')->willReturn(Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'));
        $paymentRequest->method('getPayload')->willReturn([
            'amount' => 10000,
            'currency' => 'CZK',
            'orderNumber' => 'ORD-001',
            'customerData' => [],
        ]);

        $goPayResponse = new Response();
        $goPayResponse->statusCode = 200;
        $goPayResponse->json = [
            'id' => 12345,
            'order_number' => 'ORD-001',
            'state' => GoPayApiInterface::CREATED,
            'gw_url' => 'https://gw.gopay.com/pay/12345',
        ];
        $goPayApi->method('create')->willReturn($goPayResponse);

        $entityManager->expects($this->once())->method('flush');

        $requestConfiguration = $this->createMock(RequestConfiguration::class);

        $provider = new CaptureHttpResponseProvider($goPayApi, $router, $entityManager);
        $response = $provider->getResponse($requestConfiguration, $paymentRequest);

        $this->assertSame(302, $response->getStatusCode());
    }
}
