<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\ResponseProvider;

use GoPay\Definition\Language;
use Sylius\Bundle\PaymentBundle\Provider\HttpResponseProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use ThreeBRS\SyliusGoPayPlugin\Model\OrderForPayment;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;
use ThreeBRS\SyliusGoPayPlugin\ResponseProvider\Partials\GoPayApiConfigurationTrait;

final readonly class CaptureHttpResponseProvider implements HttpResponseProviderInterface
{
    use GoPayApiConfigurationTrait;

    public function __construct(
        private GoPayApiInterface $goPayApi,
        private RouterInterface $router,
    ) {
    }

    public function supports(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): bool {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_CAPTURE;
    }

    public function getResponse(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): Response {
        // Get order data from payload
        /** @var array<string, mixed>|null $payloadArray */
        $payloadArray = $paymentRequest->getPayload();
        if (!is_array($payloadArray)) {
            throw new \RuntimeException('Payment request payload expected to be an array');
        }

        $orderForPayment = OrderForPayment::fromArray($payloadArray);

        // Get gateway configuration
        $gatewayConfig = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig();
        if ($gatewayConfig === null) {
            throw new \RuntimeException('GoPay payment method configuration is missing');
        }

        // Authorize GoPay API
        $this->goPayApi->authorize(
            goId: $this->getGoId($gatewayConfig),
            clientId: $this->getClientId($gatewayConfig),
            clientSecret: $this->getClientSecret($gatewayConfig),
            isProductionMode: $this->isProductionMode($gatewayConfig),
        );

        // Prepare GoPay order
        $payment = $paymentRequest->getPayment();
        assert($payment instanceof PaymentInterface);

        $order = $payment->getOrder();
        assert($order instanceof OrderInterface);

        $goPayOrder = $this->prepareGoPayOrder(
            orderForPayment: $orderForPayment,
            order: $order,
            paymentRequest: $paymentRequest,
            goid: $this->getGoId($gatewayConfig),
        );

        // Create payment at GoPay
        $goPayResponse = $this->goPayApi->create($goPayOrder);

        if (!isset($goPayResponse->json['errors']) && GoPayApiInterface::CREATED === $goPayResponse->json['state']) {
            // Store external payment ID and order ID in payload
            $payload = $payloadArray;
            $payload[PaymentConstants::EXTERNAL_PAYMENT_ID] = $goPayResponse->json['id'];
            $payload[PaymentConstants::ORDER_ID] = $goPayResponse->json['order_number'];
            $payload[PaymentConstants::GOPAY_STATUS] = $goPayResponse->json['state'];
            $paymentRequest->setPayload($payload);

            /** @var array<string, mixed> $responseData */
            $responseData = $goPayResponse->json;
            $paymentRequest->setResponseData($responseData);

            // Redirect to GoPay gateway
            $gwUrl = $goPayResponse->json['gw_url'] ?? null;
            if (!is_string($gwUrl)) {
                throw new \RuntimeException('GoPay gateway URL is missing');
            }

            return new RedirectResponse($gwUrl);
        }

        throw new \RuntimeException('GoPay error: ' . $goPayResponse->__toString());
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareGoPayOrder(
        OrderForPayment $orderForPayment,
        OrderInterface $order,
        PaymentRequestInterface $paymentRequest,
        string $goid,
    ): array {
        $notifyUrl = $this->router->generate(
            'sylius_shop_order_after_pay',
            ['hash' => $paymentRequest->getHash()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $localeCode = $order->getLocaleCode() ?? 'en';
        $goPayLanguage = $this->mapLocaleToGoPayLanguage($localeCode);

        $goPayOrder = [
            'currency' => $orderForPayment->getCurrency(),
            'target' => [
                'type' => 'ACCOUNT',
                'goid' => $goid,
            ],
            'amount' => $orderForPayment->getAmount(),
            'order_number' => $orderForPayment->getOrderNumber(),
            'lang' => $goPayLanguage,
            'callback' => [
                'return_url' => $notifyUrl,
                'notification_url' => $notifyUrl,
            ],
        ];

        // Add payer contact information if available
        $customerData = $orderForPayment->getCustomerData();
        if (!empty($customerData)) {
            $payerContact = [];

            if (isset($customerData['email'])) {
                $payerContact['email'] = $customerData['email'];
            }
            if (isset($customerData['firstName'])) {
                $payerContact['first_name'] = $customerData['firstName'];
            }
            if (isset($customerData['lastName'])) {
                $payerContact['last_name'] = $customerData['lastName'];
            }
            if (isset($customerData['phoneNumber'])) {
                $payerContact['phone_number'] = $customerData['phoneNumber'];
            }
            if (isset($customerData['city'])) {
                $payerContact['city'] = $customerData['city'];
            }
            if (isset($customerData['street'])) {
                $payerContact['street'] = $customerData['street'];
            }
            if (isset($customerData['postcode'])) {
                $payerContact['postal_code'] = $customerData['postcode'];
            }
            if (isset($customerData['countryCode'])) {
                $payerContact['country_code'] = $customerData['countryCode'];
            }

            if (!empty($payerContact)) {
                $goPayOrder['payer'] = [
                    'contact' => $payerContact,
                ];
            }
        }

        return $goPayOrder;
    }

    private function mapLocaleToGoPayLanguage(string $localeCode): string
    {
        // Extract language code from locale (e.g., 'en_US' -> 'en', 'cs_CZ' -> 'cs')
        $languageCode = strtolower(substr($localeCode, 0, 2));

        // Map to GoPay supported languages
        return match ($languageCode) {
            'cs' => Language::CZECH,
            'sk' => Language::SLOVAK,
            'en' => Language::ENGLISH,
            'de' => Language::GERMAN,
            'fr' => Language::FRENCH,
            'pl' => Language::POLISH,
            'hu' => Language::HUNGARIAN,
            'it' => Language::ITALIAN,
            'es' => Language::SPANISH,
            'ro' => Language::ROMANIAN,
            'bg' => Language::BULGARIAN,
            'hr' => Language::CROATIAN,
            'sl' => Language::SLOVENIAN,
            'ru' => Language::RUSSIAN,
            default => Language::ENGLISH,
        };
    }
}
