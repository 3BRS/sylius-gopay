<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials;

trait ParseFallbackLocaleCodeTrait
{
    private function parseFallbackLocaleCode(string $localeCode): string
    {
        return explode('_', $localeCode)[0] ?: $localeCode;
    }
}
