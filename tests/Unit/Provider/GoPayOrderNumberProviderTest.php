<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Unit\Provider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use ThreeBRS\SyliusGoPayPlugin\Provider\GoPayOrderNumberProvider;

final class GoPayOrderNumberProviderTest extends TestCase
{
    #[Test]
    public function it_provides_order_number_in_correct_format(): void
    {
        // Arrange
        $clock = $this->createMock(ClockInterface::class);
        $dateTime = new \DateTimeImmutable('@1234567890');
        $clock->method('now')->willReturn($dateTime);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getNumber')->willReturn('000000001');

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $provider = new GoPayOrderNumberProvider($clock);

        // Act
        $result = $provider->provideOrderNumber($payment);

        // Assert
        $this->assertSame('000000001-1234567890', $result);
    }

    #[Test]
    public function it_generates_different_numbers_for_different_timestamps(): void
    {
        // Arrange
        $clock1 = $this->createMock(ClockInterface::class);
        $dateTime1 = new \DateTimeImmutable('@1111111111');
        $clock1->method('now')->willReturn($dateTime1);

        $clock2 = $this->createMock(ClockInterface::class);
        $dateTime2 = new \DateTimeImmutable('@2222222222');
        $clock2->method('now')->willReturn($dateTime2);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getNumber')->willReturn('000000001');

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $provider1 = new GoPayOrderNumberProvider($clock1);
        $provider2 = new GoPayOrderNumberProvider($clock2);

        // Act
        $result1 = $provider1->provideOrderNumber($payment);
        $result2 = $provider2->provideOrderNumber($payment);

        // Assert
        $this->assertSame('000000001-1111111111', $result1);
        $this->assertSame('000000001-2222222222', $result2);
        $this->assertNotSame($result1, $result2);
    }

    #[Test]
    public function it_generates_different_numbers_for_different_order_numbers(): void
    {
        // Arrange
        $clock = $this->createMock(ClockInterface::class);
        $dateTime = new \DateTimeImmutable('@1234567890');
        $clock->method('now')->willReturn($dateTime);

        $order1 = $this->createMock(OrderInterface::class);
        $order1->method('getNumber')->willReturn('ABC-123');

        $order2 = $this->createMock(OrderInterface::class);
        $order2->method('getNumber')->willReturn('ORDER-999');

        $payment1 = $this->createMock(PaymentInterface::class);
        $payment1->method('getOrder')->willReturn($order1);

        $payment2 = $this->createMock(PaymentInterface::class);
        $payment2->method('getOrder')->willReturn($order2);

        $provider = new GoPayOrderNumberProvider($clock);

        // Act
        $result1 = $provider->provideOrderNumber($payment1);
        $result2 = $provider->provideOrderNumber($payment2);

        // Assert
        $this->assertSame('ABC-123-1234567890', $result1);
        $this->assertSame('ORDER-999-1234567890', $result2);
        $this->assertNotSame($result1, $result2);
    }

    #[Test]
    public function it_throws_assertion_error_when_order_number_is_null(): void
    {
        // Arrange
        $clock = $this->createMock(ClockInterface::class);
        $dateTime = new \DateTimeImmutable('@1234567890');
        $clock->method('now')->willReturn($dateTime);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getNumber')->willReturn(null);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $provider = new GoPayOrderNumberProvider($clock);

        // Assert & Act
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Order number must not be null');

        $provider->provideOrderNumber($payment);
    }

    #[Test]
    public function it_throws_assertion_error_when_order_is_null(): void
    {
        // Arrange
        $clock = $this->createMock(ClockInterface::class);

        $payment = $this->createMock(PaymentInterface::class);
        // Return null (which is allowed by PaymentInterface::getOrder() return type)
        $payment->method('getOrder')->willReturn(null);

        $provider = new GoPayOrderNumberProvider($clock);

        // Assert & Act
        $this->expectException(\AssertionError::class);

        $provider->provideOrderNumber($payment);
    }
}
