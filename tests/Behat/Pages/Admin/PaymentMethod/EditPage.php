<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod;

use Sylius\Behat\Page\Admin\Channel\UpdatePage as BaseUpdatePage;

final class EditPage extends BaseUpdatePage implements EditPageInterface
{
    public function setIsProductionMode(bool $value): void
    {
        $this->getDocument()->fillField('sylius_payment_method_gatewayConfig_config_isProductionMode', $value);
    }

    public function setGoPayGoId(string $value): void
    {
        $this->getDocument()->fillField('sylius_payment_method_gatewayConfig_config_goid', $value);
    }

    public function setGoPayClientId(string $value): void
    {
        $this->getDocument()->fillField('sylius_payment_method_gatewayConfig_config_clientId', $value);
    }

    public function setGoPayClientSecret(string $value): void
    {
        $this->getDocument()->fillField('sylius_payment_method_gatewayConfig_config_clientSecret', $value);
    }
}
