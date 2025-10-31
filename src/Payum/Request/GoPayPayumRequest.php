<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Request;

use Payum\Core\Request\Generic;

/**
 * Processed by @see GoPayAction::execute
 */
class GoPayPayumRequest extends Generic
{
    private ?string $triggeringAction = null;

    public function setTriggeringAction(string $triggeringAction): void
    {
        $this->triggeringAction = $triggeringAction;
    }

    public function getTriggeringAction(): ?string
    {
        return $this->triggeringAction;
    }
}
