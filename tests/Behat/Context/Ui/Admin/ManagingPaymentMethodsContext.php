<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\Resolver\CurrentPageResolverInterface;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\CreatePageInterface;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPageInterface;

final readonly class ManagingPaymentMethodsContext implements Context
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

    /**
     * @When I configure it with test GoPay credentials and enable pre-authorization
     */
    public function iConfigureItWithTestGoPayCredentialsAndEnablePreAuthorization(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        $currentPage->setIsProductionMode(true);
        $currentPage->setUseAuthorize(true);
        $currentPage->setGoPayGoId('TEST GOID');
        $currentPage->setGoPayClientId('TEST CLIENT ID');
        $currentPage->setGoPayClientSecret('TEST CLIENT SECRET');
    }

    /**
     * @When I enable authorize payment only
     */
    public function iEnableAuthorizePaymentOnly(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        $currentPage->setUseAuthorize(true);
    }

    /**
     * @When I disable authorize payment only
     */
    public function iDisableAuthorizePaymentOnly(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        $currentPage->setUseAuthorize(false);
    }

    /**
     * @Then the authorize payment only option should be enabled
     */
    public function theAuthorizePaymentOnlyOptionShouldBeEnabled(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        \Webmozart\Assert\Assert::true(
            $currentPage->isUseAuthorizeChecked(),
            'Authorize payment only option should be enabled but it is not.'
        );
    }

    /**
     * @Then the authorize payment only option should be disabled
     */
    public function theAuthorizePaymentOnlyOptionShouldBeDisabled(): void
    {
        /** @var CreatePageInterface|EditPageInterface $currentPage */
        $currentPage = $this->currentPageResolver->getCurrentPageWithForm([
            $this->createPage,
            $this->updatePage,
        ]);

        \Webmozart\Assert\Assert::false(
            $currentPage->isUseAuthorizeChecked(),
            'Authorize payment only option should be disabled but it is enabled.'
        );
    }
}
