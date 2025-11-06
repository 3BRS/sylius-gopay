<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\CommandHandler;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use ThreeBRS\SyliusGoPayPlugin\Command\CapturePaymentRequest;
use ThreeBRS\SyliusGoPayPlugin\Model\OrderForPayment;
use ThreeBRS\SyliusGoPayPlugin\Provider\GoPayOrderNumberProviderInterface;

#[AsMessageHandler]
final readonly class CapturePaymentRequestHandler
{
    /**
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     */
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private StateMachineInterface $stateMachine,
        private GoPayOrderNumberProviderInterface $goPayOrderNumberProvider,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    /**
     * Handles @see PaymentRequestInterface::ACTION_CAPTURE
     *
     * Prepare the payment request for capture, later processed on the payment provider website
     */
    public function __invoke(CapturePaymentRequest $capturePaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($capturePaymentRequest);

        $orderForPayment = $this->createOrderForPayment($paymentRequest);
        $paymentRequest->setPayload($orderForPayment->toArray());
        $this->paymentRepository->add($paymentRequest);

        if ($this->stateMachine->can(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_PROCESS,
        )) {
            $this->stateMachine->apply(
                $paymentRequest,
                PaymentRequestTransitions::GRAPH,
                PaymentRequestTransitions::TRANSITION_PROCESS,
            );
        }
    }

    private function createOrderForPayment(PaymentRequestInterface $paymentRequest): OrderForPayment
    {
        $payment = $paymentRequest->getPayment();
        assert($payment instanceof PaymentInterface);

        $order = $payment->getOrder();
        assert($order instanceof OrderInterface);

        $customer = $order->getCustomer();
        $customerData = $customer instanceof CustomerInterface ? $this->extractCustomerData($customer, $order) : [];

        $currencyCode = $payment->getCurrencyCode();
        assert($currencyCode !== null, 'Currency code must not be null');

        $amount = $payment->getAmount();
        assert($amount !== null, 'Amount must not be null');

        return new OrderForPayment(
            currency: $currencyCode,
            amount: $amount,
            orderNumber: $this->goPayOrderNumberProvider->provideOrderNumber($payment),
            customerData: $customerData,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractCustomerData(CustomerInterface $customer, OrderInterface $order): array
    {
        $billingAddress = $order->getBillingAddress();

        return [
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'phoneNumber' => $customer->getPhoneNumber(),
            'city' => $billingAddress?->getCity(),
            'street' => $billingAddress?->getStreet(),
            'postcode' => $billingAddress?->getPostcode(),
            'countryCode' => $billingAddress?->getCountryCode(),
        ];
    }
}
