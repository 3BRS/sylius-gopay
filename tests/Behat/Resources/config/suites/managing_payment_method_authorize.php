<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Setup\PaymentContext;
use Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Context\Ui\Admin\ManagingPaymentMethodsContext;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('managing_payment_method_authorize'))
                    ->withContexts(
                        'sylius.behat.context.hook.doctrine_orm',
                        'sylius.behat.context.setup.channel',
                        'sylius.behat.context.setup.currency',
                        'sylius.behat.context.setup.locale',
                        'sylius.behat.context.setup.payment',
                        'sylius.behat.context.setup.admin_security',
                        'sylius.behat.context.setup.user',
                        'sylius.behat.context.transform.payment',
                        'sylius.behat.context.transform.shared_storage',
                        PaymentContext::class,
                        'theebrs_sylius.gopay.behat.context.ui.admin.managing_payment_methods',
                        ManagingPaymentMethodsContext::class,
                        'sylius.behat.context.ui.admin.managing_payment_methods',
                        'sylius.behat.context.ui.admin.notification',
                        'sylius.behat.context.ui.save',
                        'sylius.behat.context.ui.shop.locale',
                    )
                    ->withFilter(new TagFilter('@managing_payment_method_authorize && @ui')),
            ),
    );
