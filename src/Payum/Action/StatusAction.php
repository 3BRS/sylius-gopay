<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;

class StatusAction implements ActionInterface
{
    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        assert($request instanceof GetStatusInterface);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $status = $model[GoPayAction::GOPAY_STATUS]
            ?? null;

        if ((null === $status || GoPayApiInterface::CREATED === $status) && !isset($model['orderId'])) {
            $request->markNew();

            return;
        }

        if (GoPayApiInterface::REFUNDED === $status) {
            $request->markRefunded();

            return;
        }

        if (GoPayApiInterface::CANCELED === $status) {
            $request->markCanceled();

            return;
        }

        if (GoPayApiInterface::PAID === $status) {
            $request->markCaptured();

            return;
        }

        $request->markUnknown();
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof GetStatusInterface && $request->getModel() instanceof ArrayAccess;
    }
}
