<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Behat\Pages\Admin\PaymentMethod;

use Sylius\Behat\Page\Admin\PaymentMethod\CreatePageInterface as BaseCreatePageInterface;

interface CreatePageInterface extends BaseCreatePageInterface
{
    public function setIsProductionMode(bool $value): void;

    public function setGoPayGoId(string $value): void;

    public function setGoPayClientId(string $value): void;

    public function setGoPayClientSecret(string $value): void;
}
