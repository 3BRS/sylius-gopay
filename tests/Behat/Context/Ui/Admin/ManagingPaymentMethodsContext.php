<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\Resolver\CurrentPageResolverInterface;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\CreatePageInterface;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPageInterface;

final class ManagingPaymentMethodsContext implements Context
{
    public function __construct(
        private CurrentPageResolverInterface $currentPageResolver,
        private CreatePageInterface $createPage,
        private EditPageInterface $updatePage,
    ) {
    }

    /**
     * @When I configure it with test GoPay credentials
     */
    public function iConfigureItWithTestGoPayCredentials(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        $currentPage->setIsProductionMode(true);
        $currentPage->setGoPayGoId('TEST GOID');
        $currentPage->setGoPayClientId('TEST CLIENT ID');
        $currentPage->setGoPayClientSecret('TEST CLIENT SECRET');
    }
}
