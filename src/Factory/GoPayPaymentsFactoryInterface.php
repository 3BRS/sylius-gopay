<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Factory;

use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use GoPay\Payments;

interface GoPayPaymentsFactoryInterface
{
    public function createPayments(
        string $goId,
        string $clientId,
        string $clientSecret,
        bool $isProductionMode,
        string $language = Language::ENGLISH,
        ?string $gatewayUrl = null,
        string $scope = TokenScope::ALL,
        int $timeout = 30,
    ): Payments;
}
