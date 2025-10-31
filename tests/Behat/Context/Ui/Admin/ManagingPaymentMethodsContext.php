<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPageInterface;

final class ManagingPaymentMethodsContext implements Context
{
    public function __construct(
        private EditPageInterface $updatePage,
    ) {
    }

    /**
     * @When I configure it with test GoPay credentials
     */
    public function iConfigureItWithTestGoPayCredentials(): void
    {
        $this->updatePage->setIsProductionMode(true);
        $this->updatePage->setGoPayGoId('TEST GOID');
        $this->updatePage->setGoPayClientId('TEST CLIENT ID');
        $this->updatePage->setGoPayClientSecret('TEST CLIENT SECRET');
    }
}
