<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Request\Factory;

use Payum\Core\Request\Cancel;
use Payum\Core\Security\TokenInterface;

final class CancelRequestFactory implements CancelRequestFactoryInterface
{
    public function createNewWithToken(TokenInterface $token): Cancel
    {
        return new Cancel($token);
    }
}
