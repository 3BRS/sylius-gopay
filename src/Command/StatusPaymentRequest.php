<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Command;

use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareInterface;
use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareTrait;
use ThreeBRS\SyliusGoPayPlugin\CommandHandler\StatusPaymentRequestHandler;

/**
 * Processed by @see StatusPaymentRequestHandler
 */
class StatusPaymentRequest implements PaymentRequestHashAwareInterface
{
    use PaymentRequestHashAwareTrait;

    public function __construct(?string $hash)
    {
        $this->hash = $hash;
    }
}
