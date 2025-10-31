<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Message\Command;

interface PaymentCommandInterface
{
    public function getPaymentId(): int;
}
