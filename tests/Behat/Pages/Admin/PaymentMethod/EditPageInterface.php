<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod;

use Sylius\Behat\Page\Admin\PaymentMethod\UpdatePageInterface as BaseUpdatePageInterface;

interface EditPageInterface extends BaseUpdatePageInterface
{
    public function setIsProductionMode(bool $value): void;

    public function setGoPayGoId(string $value): void;

    public function setGoPayClientId(string $value): void;

    public function setGoPayClientSecret(string $value): void;
}
