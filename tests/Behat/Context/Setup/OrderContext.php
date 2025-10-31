<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Tests\ThreeBRS\SyliusGoPayPlugin\Payum\GoPayPaymentsMockFactory;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\GoPayAction;
use ThreeBRS\SyliusGoPayPlugin\Payum\GoPayPaymentsFactoryInterface;
use Webmozart\Assert\Assert;

readonly class OrderContext implements Context
{
    public function __construct(
        private ObjectManager $objectManager,
        private StateMachineInterface $stateMachineFactory,
        /** @var GoPayPaymentsMockFactory */
        private GoPayPaymentsFactoryInterface $gopayPaymentsFactory,
        private SharedStorageInterface $sharedStorage,
    ) {
        \assert(
            $gopayPaymentsFactory instanceof GoPayPaymentsMockFactory,
            'The GoPayPaymentsFactory should be an instance of GoPayPaymentsMockFactory, got ' . get_class($gopayPaymentsFactory),
        );
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
        Assert::same($lastPayment->getMethod()->getCode(), 'gopay');

        Assert::numeric($externalPaymentId);

        $lastPayment->setDetails([
            GoPayAction::ORDER_ID => 123456,
            GoPayAction::EXTERNAL_PAYMENT_ID => (int)$externalPaymentId,
        ]);

        $this->sharedStorage->set('external_payment_ID', $externalPaymentId);

        $this->stateMachineFactory->apply(
            $lastPayment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_COMPLETE
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
            2,
            'Expected at least 2 GoPay payment APIs, one for refund, second for capture, got ' . count($lastGoPayPaymentApis),
        );
        $lastButOneGoPayPaymentApi = array_slice($lastGoPayPaymentApis, -2, 1)[0];
        Assert::same($lastButOneGoPayPaymentApi->getLastPaymentId(), $externalPaymentId);
        Assert::notNull($lastPayment->getAmount());
        Assert::same($lastButOneGoPayPaymentApi->getLastAmount(), $lastPayment->getAmount());
    }
}
