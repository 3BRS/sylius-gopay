<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Provider;

use Psr\Clock\ClockInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final readonly class GoPayOrderNumberProvider implements GoPayOrderNumberProviderInterface
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function provideOrderNumber(PaymentInterface $payment): string
    {
        $order = $payment->getOrder();
        assert($order instanceof OrderInterface);

        $orderNumber = $order->getNumber();
        assert($orderNumber !== null, 'Order number must not be null');

        // GoPay requires unique order numbers. We append timestamp to ensure uniqueness
        // for cases where the same order is paid multiple times (e.g., after failed payment)
        $timestamp = $this->clock->now()->getTimestamp();

        return sprintf('%s-%d', $orderNumber, $timestamp);
    }
}
