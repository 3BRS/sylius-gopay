<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Payment;

use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use GoPay\Payments;
use ThreeBRS\SyliusGoPayPlugin\Factory\GoPayPaymentsFactoryInterface;

class GoPayPaymentsMockFactory implements GoPayPaymentsFactoryInterface
{
    /**
     * @var array<GoPayPaymentsMock>
     */
    private static array $lastPayments = [];

    public function createPayments(
        string  $goId,
        string  $clientId,
        string  $clientSecret,
        bool    $isProductionMode,
        string  $language = Language::ENGLISH,
        ?string $gatewayUrl = null,
        string  $scope = TokenScope::ALL,
        int     $timeout = 30,
    ): Payments {
        $payments = new GoPayPaymentsMock();
        self::$lastPayments[] = $payments;

        return $payments;
    }

    /**
     * @return array<GoPayPaymentsMock>
     */
    public function getLastPayments(): array
    {
        return self::$lastPayments;
    }
}
