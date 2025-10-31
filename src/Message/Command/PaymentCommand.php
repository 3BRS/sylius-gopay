<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Message\Command;

abstract class PaymentCommand implements PaymentCommandInterface
{
    public function __construct(private readonly int $paymentId)
    {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
