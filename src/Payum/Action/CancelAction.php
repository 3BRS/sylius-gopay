<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject as PayumArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\AuthorizeGoPayActionTrait;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\ParseFallbackLocaleCodeTrait;
use ThreeBRS\SyliusGoPayPlugin\Payum\Request\GoPayPayumRequest;

final class CancelAction implements
    ActionInterface,
    GatewayAwareInterface,
    ApiAwareInterface
{
    use GatewayAwareTrait;
    use AuthorizeGoPayActionTrait;
    use ParseFallbackLocaleCodeTrait;

    public const CANCEL_ACTION = 'cancel';

    public function __construct(
        private GoPayApiInterface $goPayApi,
    ) {
    }

    /**
     * De facto a callback processed on @see Cancel request.
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        \assert($request instanceof Cancel);
        $model = PayumArrayObject::ensureArrayObject($request->getModel());

        $payment = $request->getFirstModel();
        \assert($payment instanceof PaymentInterface);
        $model['amount'] = $payment->getAmount();

        $localeCode = $payment->getOrder()?->getLocaleCode();
        \assert($localeCode !== null);
        $model['locale'] = $this->parseFallbackLocaleCode($localeCode);

        $this->authorizeGoPayAction($model, $this->goPayApi);

        $token = $request->getToken();
        \assert($token instanceof TokenInterface);
        $this->gateway->execute(
            request: $this->createRequest($token, $model),
            catchReply: true, // do not want to throw a "redirect-to-landing-page" exception
        );
    }

    public function supports($request): bool
    {
        return
            $request instanceof Cancel &&
            $request->getModel() instanceof \ArrayAccess;
    }

    private function createRequest(
        TokenInterface $token,
        PayumArrayObject $model,
    ): GoPayPayumRequest {
        $goPayPayumRequest = new GoPayPayumRequest($token);
        $goPayPayumRequest->setModel($model);
        $goPayPayumRequest->setTriggeringAction(self::CANCEL_ACTION);

        return $goPayPayumRequest;
    }
}
