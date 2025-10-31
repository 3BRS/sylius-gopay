<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Request\Factory;

use Payum\Core\Request\Cancel;
use Payum\Core\Security\TokenInterface;

interface CancelRequestFactoryInterface extends ModelAggregateFactoryInterface
{
    public function createNewWithToken(TokenInterface $token): Cancel;
}
