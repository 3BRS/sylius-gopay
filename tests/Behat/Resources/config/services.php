<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->defaults()
        ->public();

    $services->set('theebrs_sylius.gopay.behat.context.ui.admin.managing_payment_methods', \Sylius\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext::class)
        ->decorate('sylius.behat.context.ui.admin.managing_payment_methods')
        ->args([
            service('sylius.behat.page.admin.payment_method.create'),
            service('sylius.behat.page.admin.payment_method.index'),
            service('sylius.behat.page.admin.payment_method.update'),
            service('sylius.behat.current_page_resolver'),
            ['offline' => 'Offline', 'paypal_express_checkout' => 'Paypal Express Checkout', 'stripe_checkout' => 'Stripe Checkout', 'gopay' => 'GoPay'],
        ]);

    $services->set(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext::class, \Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext::class)
        ->args([
            service('sylius.behat.current_page_resolver'),
            service(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\CreatePageInterface::class),
            service(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPageInterface::class),
        ]);

    $services->set(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\CreatePageInterface::class, \Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\CreatePage::class)
        ->private()
        ->parent('sylius.behat.page.admin.crud.create')
        ->args(['sylius_admin_payment_method_create']);

    $services->set(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPageInterface::class, \Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod\EditPage::class)
        ->private()
        ->parent('sylius.behat.page.admin.crud.update')
        ->args(['sylius_admin_payment_method_update']);

    $services->set(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup\PaymentContext::class, \Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup\PaymentContext::class)
        ->args([
            service('sylius.behat.shared_storage'),
            service('sylius.repository.payment_method'),
            service('sylius.fixture.example_factory.payment_method'),
            service('sylius.manager.payment_method'),
            ['offline' => 'Offline', 'paypal_express_checkout' => 'Paypal Express Checkout', 'stripe_checkout' => 'Stripe Checkout', 'gopay' => 'GoPay'],
        ]);

    $services->set(\Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup\OrderContext::class, \Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup\OrderContext::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sylius_abstraction.state_machine'),
            service('threebrs.gopay.payments.factory'),
            service('sylius.behat.shared_storage'),
            service('sylius.factory.payment_request'),
            service('sylius.repository.payment_request'),
        ]);
};
