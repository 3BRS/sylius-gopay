<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action\Exception;

class PaymentCanNotBeCanceledException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private ?string $goPayStatus = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getGoPayStatus(): ?string
    {
        return $this->goPayStatus;
    }
}
