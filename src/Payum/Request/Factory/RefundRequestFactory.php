<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Request\Factory;

use Payum\Core\Request\Refund;
use Payum\Core\Security\TokenInterface;

final class RefundRequestFactory implements RefundRequestFactoryInterface
{
    public function createNewWithToken(TokenInterface $token): Refund
    {
        return new Refund($token);
    }
}
