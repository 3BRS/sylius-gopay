<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Message\CommandHandler;

use Payum\Core\Payum;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use ThreeBRS\SyliusGoPayPlugin\Message\Command\RefundPayment;
use ThreeBRS\SyliusGoPayPlugin\Payum\Request\Factory\CaptureRequestFactoryInterface;
use ThreeBRS\SyliusGoPayPlugin\Payum\Request\Factory\RefundRequestFactoryInterface;

final class RefundPaymentHandler extends AbstractPayumPaymentHandler
{
    /**
     * @param string[] $supportedGateways
     */
    public function __construct(
        private readonly RefundRequestFactoryInterface $refundRequestFactory,
        private readonly CaptureRequestFactoryInterface $captureRequestFactory,
        private readonly Payum $payum,
        PaymentRepositoryInterface $paymentRepository,
        array $supportedGateways = ['gopay'],
    ) {
        parent::__construct($paymentRepository, $payum, $supportedGateways);
    }

    public function __invoke(RefundPayment $command): void
    {
        $payment = $this->getPayment($command);
        if (null === $payment) {
            return;
        }

        $gatewayName = $this->getGatewayNameFromPayment($payment);

        if (null === $gatewayName) {
            return;
        }

        $gateway = $this->payum->getGateway($gatewayName);
        $token = $this->buildToken($gatewayName, $payment);

        $refundRequest = $this->refundRequestFactory->createNewWithToken($token);
        $gateway->execute($refundRequest);

        $captureRequest = $this->captureRequestFactory->createNewWithToken($token);
        // to sync GoPay payment status which is not given by refund request
        $gateway->execute($captureRequest);
    }
}
