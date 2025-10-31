<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum;

use GoPay\Api;
use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use GoPay\Payments;

class GoPayPaymentsFactory implements GoPayPaymentsFactoryInterface
{
    public const PRODUCTION_GATEWAY_URL = 'https://gate.gopay.cz/api';

    public const SANDBOX_TEST_GATEWAY_URL = 'https://gw.sandbox.gopay.com/api';

    public function __construct(
        private string $productionGatewayUrl = self::PRODUCTION_GATEWAY_URL,
        private string $sandboxTestGatewayUrl = self::SANDBOX_TEST_GATEWAY_URL,
    ) {
    }

    public function createPayments(
        string $goId,
        string $clientId,
        string $clientSecret,
        bool $isProductionMode,
        string $language = Language::ENGLISH,
        ?string $gatewayUrl = null,
        string $scope = TokenScope::ALL,
        int $timeout = 30,
    ): Payments {
        // @phpstan-ignore return.type
        return Api::payments([
            'goid' => $goId,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'gatewayUrl' => $this->getGatewayUrl($isProductionMode, $gatewayUrl),
            'isProductionMode' => $isProductionMode,
            'language' => $language,
            'scope' => $scope,
            'timeout' => $timeout,
        ]);
    }

    private function getGatewayUrl(
        bool $isProductionMode,
        ?string $gatewayUrl,
    ): string {
        if ($gatewayUrl !== null) {
            return $gatewayUrl;
        }

        return $isProductionMode
            ? $this->productionGatewayUrl
            : $this->sandboxTestGatewayUrl;
    }
}
