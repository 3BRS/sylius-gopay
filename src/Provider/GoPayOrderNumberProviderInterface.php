<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Provider;

use Sylius\Component\Core\Model\PaymentInterface;

interface GoPayOrderNumberProviderInterface
{
    public function provideOrderNumber(PaymentInterface $payment): string;
}
