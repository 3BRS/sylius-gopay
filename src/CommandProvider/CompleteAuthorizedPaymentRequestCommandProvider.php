<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandProvider;

use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use ThreeBRS\SyliusGoPayPlugin\Command\CompleteAuthorizedPaymentRequest;

final readonly class CompleteAuthorizedPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return $paymentRequest->getAction() === 'complete_authorized';
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return new CompleteAuthorizedPaymentRequest($paymentRequest->getId());
    }
}
