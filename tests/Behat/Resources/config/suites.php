<?php

declare(strict_types=1);

use Behat\Config\Config;

return (new Config())
    ->import([
        'suites/adding_payment_method.php',
        'suites/capturing_authorized_payment.php',
        'suites/managing_payment_method_authorize.php',
        'suites/refunding_order_payment.php',
        'suites/select_payment_method_checkout.php',
    ]);
