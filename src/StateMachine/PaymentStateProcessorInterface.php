<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\StateMachine;

use Symfony\Component\Workflow\Event\CompletedEvent;

interface PaymentStateProcessorInterface
{
    public function __invoke(CompletedEvent $event): void;
}
