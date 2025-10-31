<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandProvider;

use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\RefundPaymentRequest;

readonly class RefundPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_REFUND;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return new RefundPaymentRequest($paymentRequest->getId());
    }
}
