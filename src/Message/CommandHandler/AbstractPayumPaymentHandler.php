<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Message\CommandHandler;

use Payum\Core\Payum;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use ThreeBRS\SyliusGoPayPlugin\Message\Command\PaymentCommandInterface;

abstract class AbstractPayumPaymentHandler
{
    /**
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     * @param string[] $supportedGateways
     */
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly Payum $payum,
        private readonly array $supportedGateways = ['gopay'],
    ) {
    }

    protected function getPayment(PaymentCommandInterface $paymentCommand): ?PaymentInterface
    {
        /** @var PaymentInterface|null $payment */
        $payment = $this->paymentRepository->find($paymentCommand->getPaymentId());

        return $payment;
    }

    protected function getGatewayNameFromPayment(PaymentInterface $payment): ?string
    {
        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $payment->getMethod();
        if (null === $paymentMethod) {
            return null;
        }

        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if (null === $gatewayConfig) {
            return null;
        }

        $gatewayName = $gatewayConfig->getGatewayName();
        if (!in_array($gatewayName, $this->supportedGateways, true)) {
            return null;
        }

        return $gatewayName;
    }

    protected function buildToken(
        string $gatewayName,
        PaymentInterface $payment,
    ): TokenInterface {
        $tokenFactory = $this->payum->getTokenFactory();

        /** see @see vendor/payum/payum-bundle/Resources/config/routing for targetPath */
        return $tokenFactory->createToken($gatewayName, $payment, 'payum_notify_do');
    }
}
