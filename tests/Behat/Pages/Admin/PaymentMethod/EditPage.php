<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod;

use Sylius\Behat\Page\Admin\PaymentMethod\UpdatePage as BaseUpdatePage;

final class EditPage extends BaseUpdatePage implements EditPageInterface
{
    public function setIsProductionMode(bool $value): void
    {
        $this->getElement('isProductionMode')->selectOption($value ? '1' : '0');
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
            'goid' => '#sylius_admin_payment_method_gatewayConfig_config_goid',
            'clientId' => '#sylius_admin_payment_method_gatewayConfig_config_clientId',
            'clientSecret' => '#sylius_admin_payment_method_gatewayConfig_config_clientSecret',
        ]);
    }
}
