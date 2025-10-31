<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;

final class ConvertPaymentAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * De facto a callback processed on @see Convert "request" (event).
     *
     * Those details are used as payload for GoPay API later and also saved as @see PaymentInterface::getDetails()
     */
    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        \assert($request instanceof Convert);
        $payment = $request->getSource();
        \assert($payment instanceof PaymentInterface);

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details['totalAmount'] = $payment->getTotalAmount();
        $details['currencyCode'] = $payment->getCurrencyCode();
        $details['extOrderId'] = $payment->getNumber();
        $details['number'] = $payment->getNumber() . date('His');
        $details['description'] = $payment->getDescription();
        $details['client_email'] = $payment->getClientEmail();
        $details['client_id'] = $payment->getClientId();
        $details['status'] = GoPayApiInterface::CREATED;

        $request->setResult((array) $details);
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof Convert &&
               $request->getSource() instanceof PaymentInterface &&
               'array' === $request->getTo();
    }
}
