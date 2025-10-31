<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Command;

use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareInterface;
use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareTrait;
use ThreeBRS\SyliusGoPayPlugin\CommandHandler\RefundPaymentRequestHandler;

/**
 * Processed by @see RefundPaymentRequestHandler
 */
class RefundPaymentRequest implements PaymentRequestHashAwareInterface
{
    use PaymentRequestHashAwareTrait;

    public function __construct(?string $hash)
    {
        $this->hash = $hash;
    }
}
