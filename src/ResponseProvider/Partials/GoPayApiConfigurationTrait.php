<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\ResponseProvider\Partials;

trait GoPayApiConfigurationTrait
{
    /**
     * @param array<string, mixed> $gatewayConfig
     */
    private function getGoId(array $gatewayConfig): string
    {
        $goid = $gatewayConfig['goid'] ?? '';
        assert(is_string($goid), 'GoId must be a string');

        return $goid;
    }

    /**
     * @param array<string, mixed> $gatewayConfig
     */
    private function getClientId(array $gatewayConfig): string
    {
        $clientId = $gatewayConfig['clientId'] ?? '';
        assert(is_string($clientId), 'ClientId must be a string');

        return $clientId;
    }

    /**
     * @param array<string, mixed> $gatewayConfig
     */
    private function getClientSecret(array $gatewayConfig): string
    {
        $clientSecret = $gatewayConfig['clientSecret'] ?? '';
        assert(is_string($clientSecret), 'ClientSecret must be a string');

        return $clientSecret;
    }

    /**
     * @param array<string, mixed> $gatewayConfig
     */
    private function isProductionMode(array $gatewayConfig): bool
    {
        return (bool) ($gatewayConfig['isProductionMode'] ?? false);
    }
}
