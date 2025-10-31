<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\StateMachine;

use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use ThreeBRS\SyliusGoPayPlugin\Message\Command\RefundPayment;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\GoPayAction;
use Webmozart\Assert\Assert;

final class RefundOrderProcessor implements PaymentStateProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        PaymentInterface $payment,
        string $fromState,
    ): void {
        if ($payment->getState() === PaymentInterface::STATE_REFUNDED &&
            !empty($payment->getDetails()[GoPayAction::REFUND_ID])) {
            return;
        }

        /** @var int|null $paymentId */
        $paymentId = $payment->getId();
        Assert::notNull($paymentId, 'Missing payment ID on the payment object');
        $this->commandBus->dispatch(new RefundPayment($paymentId));
    }
}
