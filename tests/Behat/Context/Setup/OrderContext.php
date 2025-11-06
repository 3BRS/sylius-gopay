<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Tests\ThreeBRS\SyliusGoPayPlugin\Payment\GoPayPaymentsMockFactory;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;
use ThreeBRS\SyliusGoPayPlugin\Factory\GoPayPaymentsFactoryInterface;
use Webmozart\Assert\Assert;

readonly class OrderContext implements Context
{
    public function __construct(
        private ObjectManager $objectManager,
        private StateMachineInterface $stateMachineFactory,
        private GoPayPaymentsFactoryInterface $gopayPaymentsFactory,
        private SharedStorageInterface $sharedStorage,
        private PaymentRequestFactoryInterface $paymentRequestFactory,
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
    ) {
    }

    /**
     * @Given /^(this order) is already paid by GoPay with external payment ID ([0-9]+)$/
     * @Given the order :order is already paid by GoPay with external payment ID :externalPaymentId
     */
    public function thisOrderIsAlreadyPaidByGoPay(
        OrderInterface $order,
        int $externalPaymentId,
    ): void {
        $lastPayment = $order->getLastPayment();
        Assert::notNull($lastPayment);
        $paymentMethod = $lastPayment->getMethod();
        Assert::notNull($paymentMethod);
        Assert::same($paymentMethod->getCode(), 'gopay');

        Assert::numeric($externalPaymentId);

        // Create CAPTURE payment request with external payment ID in payload
        $captureRequest = $this->paymentRequestFactory->create($lastPayment, $paymentMethod);
        $captureRequest->setAction(PaymentRequestInterface::ACTION_CAPTURE);
        $captureRequest->setState(PaymentRequestInterface::STATE_COMPLETED);

        // Store external payment ID in capture request payload (this is what RefundPaymentRequestHandler looks for)
        $captureRequest->setPayload([
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
            PaymentConstants::ORDER_ID => 123456,
            PaymentConstants::GOPAY_STATUS => GoPayApiInterface::PAID,
        ]);

        $this->paymentRequestRepository->add($captureRequest);

        // Also set payment details for backward compatibility
        $lastPayment->setDetails([
            PaymentConstants::ORDER_ID => 123456,
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
        ]);

        $this->sharedStorage->set('external_payment_ID', $externalPaymentId);

        // Complete the payment
        $this->stateMachineFactory->apply(
            $lastPayment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_COMPLETE
        );

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this order) is already authorized by GoPay with external payment ID ([0-9]+)$/
     * @Given the order :order is already authorized by GoPay with external payment ID :externalPaymentId
     */
    public function thisOrderIsAlreadyAuthorizedByGoPay(
        OrderInterface $order,
        int $externalPaymentId,
    ): void {
        $lastPayment = $order->getLastPayment();
        Assert::notNull($lastPayment);
        $paymentMethod = $lastPayment->getMethod();
        Assert::notNull($paymentMethod);
        Assert::same($paymentMethod->getCode(), 'gopay');

        Assert::numeric($externalPaymentId);

        // Create CAPTURE payment request with external payment ID in payload and AUTHORIZED status
        $captureRequest = $this->paymentRequestFactory->create($lastPayment, $paymentMethod);
        $captureRequest->setAction(PaymentRequestInterface::ACTION_CAPTURE);
        $captureRequest->setState(PaymentRequestInterface::STATE_COMPLETED);

        // Store external payment ID in capture request payload with AUTHORIZED status
        $captureRequest->setPayload([
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
            PaymentConstants::ORDER_ID => 123456,
            PaymentConstants::GOPAY_STATUS => GoPayApiInterface::AUTHORIZED,
        ]);

        $this->paymentRequestRepository->add($captureRequest);

        // Also set payment details for backward compatibility
        $lastPayment->setDetails([
            PaymentConstants::ORDER_ID => 123456,
            PaymentConstants::EXTERNAL_PAYMENT_ID => $externalPaymentId,
        ]);

        $this->sharedStorage->set('external_payment_ID', $externalPaymentId);

        // Authorize the payment (not complete)
        $this->stateMachineFactory->apply(
            $lastPayment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_AUTHORIZE
        );

        $this->objectManager->flush();
    }

    /**
     * @Then /^GoPay should be requested to refund (this order) with (this external payment ID)$/
     */
    public function goPayShouldBeRequestedToRefundThatOrder(
        OrderInterface $order,
        $externalPaymentId,
    ): void {
        $lastPayment = $order->getLastPayment();
        Assert::notNull($lastPayment);

        $lastGoPayPaymentApis = $this->gopayPaymentsFactory->getLastPayments();
        Assert::minCount(
            $lastGoPayPaymentApis,
            1,
            'Expected at least 1 GoPay payment API for refund, got ' . count($lastGoPayPaymentApis),
        );
        $lastGoPayPaymentApi = end($lastGoPayPaymentApis);
        Assert::same($lastGoPayPaymentApi->getLastPaymentId(), $externalPaymentId);
        Assert::notNull($lastPayment->getAmount());
        Assert::same($lastGoPayPaymentApi->getLastAmount(), $lastPayment->getAmount());
    }

    /**
     * @Then /^GoPay should be requested to capture authorization for (this order) with (this external payment ID)$/
     */
    public function goPayShouldBeRequestedToCaptureAuthorizationForThatOrder(
        OrderInterface $order,
        $externalPaymentId,
    ): void {
        $lastPayment = $order->getLastPayment();
        Assert::notNull($lastPayment);

        $lastGoPayPaymentApis = $this->gopayPaymentsFactory->getLastPayments();
        Assert::minCount(
            $lastGoPayPaymentApis,
            1,
            'Expected at least 1 GoPay payment API for capture authorization, got ' . count($lastGoPayPaymentApis),
        );
        $lastGoPayPaymentApi = end($lastGoPayPaymentApis);
        Assert::same($lastGoPayPaymentApi->getLastPaymentId(), $externalPaymentId);

        // Only check amount if it was recorded (partial capture)
        // Full capture doesn't pass an amount parameter, so lastAmount will be null
        $lastAmount = $lastGoPayPaymentApi->getLastAmount();
        if ($lastAmount !== null) {
            Assert::notNull($lastPayment->getAmount());
            Assert::same($lastAmount, $lastPayment->getAmount());
        }
    }
}
