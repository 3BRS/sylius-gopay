<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action;

use ArrayObject;
use GoPay\Http\Response;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject as PayumArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Payum;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\IdentityInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Sylius\Component\Core\Model\CustomerInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Exception\PaymentCanNotBeCanceledException;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\AuthorizeGoPayActionTrait;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials\UpdateOrderActionTrait;
use ThreeBRS\SyliusGoPayPlugin\Payum\Request\GoPayPayumRequest;
use Webmozart\Assert\Assert;

class GoPayAction implements ApiAwareInterface, ActionInterface
{
    use UpdateOrderActionTrait;
    use AuthorizeGoPayActionTrait;

    public const EXTERNAL_PAYMENT_ID = 'externalPaymentId';

    public const ORDER_ID = 'orderId';

    public const REFUND_ID = 'refundId';

    public const GOPAY_STATUS = 'gopayStatus';

    public function __construct(
        private GoPayApiInterface $goPayApi,
        private Payum $payum,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        \assert($request instanceof GoPayPayumRequest);
        $model = PayumArrayObject::ensureArrayObject($request->getModel());

        $this->authorizeGoPayAction($model, $this->goPayApi);

        switch ($request->getTriggeringAction()) {
            case CaptureAction::CAPTURE_ACTION:
                $this->processCapture($request, $model);

                return;
            case RefundAction::REFUND_ACTION:
                $this->processRefund($request, $model);

                return;
            case CancelAction::CANCEL_ACTION:
                $this->processCancel($request, $model);

                return;
            default:
                throw new RuntimeException('Unknown triggering action ' . $request->getTriggeringAction());
        }
    }

    private function processRefund(
        GoPayPayumRequest $request,
        PayumArrayObject $model,
    ): void {
        if (empty($model[self::ORDER_ID])) {
            throw new RuntimeException('Order ID is missing.');
        }
        if (empty($model[self::EXTERNAL_PAYMENT_ID])) {
            throw new RuntimeException('External payment ID is missing.');
        }

        $response = $this->goPayApi->refund(
            $this->getExternalPaymentId($model),
            $this->getAmount($model),
        );

        // example of result '{"id":3276091767,"result":"FINISHED"}'
        if (!isset($response->json['errors']) && GoPayApiInterface::RESULT_FINISHED === $response->json['result']) {
            $model[self::REFUND_ID] = $response->json['id'];
            $request->setModel($model);

            throw new HttpResponse('OK');
        }

        throw new RuntimeException('GoPay error: ' . $response->__toString());
    }

    private function processCancel(
        GoPayPayumRequest $request,
        PayumArrayObject $model,
    ): void {
        if (empty($model[self::ORDER_ID])) {
            throw new RuntimeException('Order ID is missing.');
        }
        if (empty($model[self::EXTERNAL_PAYMENT_ID])) {
            throw new RuntimeException('External payment ID is missing.');
        }
        // fetch current status of the payment
        $this->processCapture($request, $model);

        $goPayStatus = $model[self::GOPAY_STATUS] ?? null;
        \assert($goPayStatus === null || is_string($goPayStatus));

        if (in_array($goPayStatus, [GoPayApiInterface::REFUNDED, GoPayApiInterface::CANCELED, GoPayApiInterface::TIMEOUTED])) {
            throw new HttpResponse('OK');
        }

        if ($goPayStatus === GoPayApiInterface::AUTHORIZED) {
            $response = $this->goPayApi->voidAuthorization($this->getExternalPaymentId($model));
            if ($this->isResponseSuccess($response)) {
                $model[self::GOPAY_STATUS] = GoPayApiInterface::CANCELED;
                $request->setModel($model);

                throw new HttpResponse('OK');
            }
        }

        if (in_array($goPayStatus, [GoPayApiInterface::PAID, GoPayApiInterface::PARTIALLY_REFUNDED])) {
            $response = $this->goPayApi->refund(
                $this->getExternalPaymentId($model),
                $this->getAmount($model),
            );

            // example of result '{"id":3276091767,"result":"FINISHED"}'
            if ($this->isResponseSuccess($response)) {
                $model[self::REFUND_ID] = $response->json['id'];
                $request->setModel($model);

                throw new HttpResponse('OK');
            }

            throw new PaymentCanNotBeCanceledException(
                message: 'GoPay error: ' . $response->__toString(),
                goPayStatus: $goPayStatus,
            );
        }

        throw new PaymentCanNotBeCanceledException(
            message: sprintf('Payment with GoPay status %s can not be canceled', var_export($goPayStatus, true)),
            goPayStatus: $goPayStatus,
        );
    }

    private function isResponseSuccess(Response $response): bool
    {
        return !isset($response->json['errors']) && GoPayApiInterface::RESULT_FINISHED === $response->json['result'];
    }

    private function getAmount(PayumArrayObject $model): int
    {
        $amount = $model['amount'];
        assert(is_numeric($amount));

        return (int) $amount;
    }

    private function processCapture(
        GoPayPayumRequest $request,
        PayumArrayObject $model,
    ): void {
        if (null === $model[self::ORDER_ID] || null === $model[self::EXTERNAL_PAYMENT_ID]) {
            $this->createNewOrder($request, $model);

            return;
        }
        $this->updateExistingOrder($this->goPayApi, $request, $model);
    }

    private function createNewOrder(
        GoPayPayumRequest $request,
        PayumArrayObject $model,
    ): void {
        $token = $request->getToken();
        assert($token instanceof TokenInterface, 'Payum token is missing');
        assert($this->api !== [], 'API configuration is missing');
        $order = $this->prepareOrder($token, $model, $this->api['goid']);
        $response = $this->goPayApi->create($order);

        if (!isset($response->json['errors']) && GoPayApiInterface::CREATED === $response->json['state']) {
            $model[self::ORDER_ID] = $response->json['order_number'];
            $model[self::EXTERNAL_PAYMENT_ID] = $response->json['id'];
            $request->setModel($model);

            throw new HttpRedirect($response->json['gw_url']);
        }

        throw new RuntimeException('GoPay error: ' . $response->__toString());
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof GoPayPayumRequest &&
            $request->getModel() instanceof ArrayObject;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareOrder(
        TokenInterface $token,
        PayumArrayObject $model,
        string $goid,
    ): array {
        $notifyToken = $this->createNotifyToken($token->getGatewayName(), $token->getDetails());

        $order = [];
        $order['target']['type'] = 'ACCOUNT';
        $order['target']['goid'] = $goid;
        $order['currency'] = $model['currencyCode'];
        $order['amount'] = $model['totalAmount'];
        $order['order_number'] = $model['extOrderId'];
        $order['lang'] = $model['locale'];

        /** @var CustomerInterface $customer */
        $customer = $model['customer'];

        Assert::isInstanceOf(
            $customer,
            CustomerInterface::class,
            sprintf(
                'Make sure the first model is the %s instance.',
                CustomerInterface::class,
            ),
        );

        $payerContact = [
            'email' => (string) $customer->getEmail(),
            'first_name' => (string) $customer->getFirstName(),
            'last_name' => (string) $customer->getLastName(),
        ];

        $order['payer']['contact'] = $payerContact;
        $order['items'] = $this->resolveProducts($model);

        $order['callback']['return_url'] = $token->getTargetUrl();
        $order['callback']['notification_url'] = $notifyToken->getTargetUrl();

        return $order;
    }

    /**
     * @return array<array{name: mixed, amount: mixed}>|array{}
     */
    private function resolveProducts(PayumArrayObject $model): array
    {
        if (false === $model->offsetExists('items') ||
            (is_countable($model['items']) && 0 === count($model['items']))
        ) {
            return [
                [
                    'name' => $model['description'],
                    'amount' => $model['totalAmount'],
                ],
            ];
        }

        return [];
    }

    private function createNotifyToken(
        string $gatewayName,
        IdentityInterface $model,
    ): TokenInterface {
        return $this->payum->getTokenFactory()->createNotifyToken($gatewayName, $model);
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
