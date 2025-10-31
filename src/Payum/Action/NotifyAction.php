<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action;

use ArrayObject;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject as PayumArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\AuthorizeGoPayActionTrait;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\UpdateOrderActionTrait;
use Webmozart\Assert\Assert;

final class NotifyAction implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use UpdateOrderActionTrait;
    use AuthorizeGoPayActionTrait;

    public function __construct(
        private GoPayApiInterface $goPayApi,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * De facto a callback processed on @see Notify "request" (event), triggered by GoPay calling eshop URL.
     */
    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        \assert($request instanceof Notify);
        $model = PayumArrayObject::ensureArrayObject($request->getModel());

        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);

        $this->authorizeGoPayAction($model, $this->goPayApi);

        try {
            $this->updateExistingOrder($this->goPayApi, $request, $model);

            throw new HttpResponse('SUCCESS');
        } catch (Exception $e) {
            throw new HttpResponse($e->getMessage());
        }
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof Notify && $request->getModel() instanceof ArrayObject;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
