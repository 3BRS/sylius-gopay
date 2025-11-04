<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\EventListener\Workflow;

use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use ThreeBRS\SyliusGoPayPlugin\StateMachine\PaymentStateProcessorInterface;
use Webmozart\Assert\Assert;

final readonly class PaymentFromCompletedStateListener
{
    public function __construct(
        private PaymentStateProcessorInterface $paymentStateProcessor,
    ) {
    }

    public function __invoke(CompletedEvent $event): void
    {
        /** @var PaymentInterface|object $payment */
        $payment = $event->getSubject();
        Assert::isInstanceOf($payment, PaymentInterface::class);

        $transition = $event->getTransition();
        Assert::notNull($transition);

        // state machine "transition from" list always contains 1 element
        $fromStates = $transition->getFroms();
        Assert::count($fromStates, 1, 'Expected exactly one "from" state in payment workflow transition');
        $fromState = reset($fromStates);
        Assert::string($fromState, 'Expected "from" state to be a string');

        $this->paymentStateProcessor->__invoke($payment, $fromState);
    }
}
