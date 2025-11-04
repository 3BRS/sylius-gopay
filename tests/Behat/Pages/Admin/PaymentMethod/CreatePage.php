<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod;

use Sylius\Behat\Page\Admin\PaymentMethod\CreatePage as BaseCreatePage;

final class CreatePage extends BaseCreatePage implements CreatePageInterface
{
    public function setIsProductionMode(bool $value): void
    {
        if ($value) {
            $this->getElement('isProductionMode')->check();
        } else {
            $this->getElement('isProductionMode')->uncheck();
        }
    }

    public function setUseAuthorize(bool $value): void
    {
        if ($value) {
            $this->getElement('useAuthorize')->check();
        } else {
            $this->getElement('useAuthorize')->uncheck();
        }
    }

    public function isUseAuthorizeChecked(): bool
    {
        return $this->getElement('useAuthorize')->isChecked();
    }

    public function isProductionModeChecked(): bool
    {
        return $this->getElement('isProductionMode')->isChecked();
    }

    public function setGoPayGoId(string $value): void
    {
        $this->getElement('goid')->setValue($value);
    }

    public function setGoPayClientId(string $value): void
    {
        $this->getElement('clientId')->setValue($value);
    }

    public function setGoPayClientSecret(string $value): void
    {
        $this->getElement('clientSecret')->setValue($value);
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'isProductionMode' => '#sylius_admin_payment_method_gatewayConfig_config_isProductionMode',
            'useAuthorize' => '#sylius_admin_payment_method_gatewayConfig_config_useAuthorize',
            'goid' => '#sylius_admin_payment_method_gatewayConfig_config_goid',
            'clientId' => '#sylius_admin_payment_method_gatewayConfig_config_clientId',
            'clientSecret' => '#sylius_admin_payment_method_gatewayConfig_config_clientSecret',
        ]);
    }
}
