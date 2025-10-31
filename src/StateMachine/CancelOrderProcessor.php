<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\StateMachine;

use Sylius\Component\Payment\Model\PaymentInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use ThreeBRS\SyliusGoPayPlugin\Message\Command\CancelPayment;
use Webmozart\Assert\Assert;

final class CancelOrderProcessor implements PaymentStateProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        PaymentInterface $payment,
        string $fromState,
    ): void {
        if (!in_array($fromState, [PaymentInterface::STATE_NEW, PaymentInterface::STATE_AUTHORIZED], true)) {
            return;
        }

        /** @var int|null $paymentId */
        $paymentId = $payment->getId();
        Assert::notNull($paymentId, 'Missing ID on payment object');

        $this->commandBus->dispatch(new CancelPayment($paymentId));
    }
}
