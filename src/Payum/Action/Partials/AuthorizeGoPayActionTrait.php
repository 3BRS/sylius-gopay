<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum\Action\Partials;

use Payum\Core\Exception\UnsupportedApiException;
use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
use Webmozart\Assert\Assert;

trait AuthorizeGoPayActionTrait
{
    /**
     * @var array{
     *     goid: string,
     *     clientId: string,
     *     clientSecret: string,
     *     isProductionMode: bool
     * }|array{}
     */
    private array $api = [];

    public function setApi(mixed $api): void
    {
        if (!is_array($api)) {
            throw new UnsupportedApiException('Not supported.');
        }
        Assert::keyExists($api, 'goid');
        Assert::keyExists($api, 'clientId');
        Assert::keyExists($api, 'clientSecret');
        Assert::keyExists($api, 'isProductionMode');

        $this->api = $api;
    }

    /**
     * @param \ArrayObject<string, string> $model
     */
    private function authorizeGoPayAction(
        \ArrayAccess $model,
        GoPayApiInterface $goPayApi,
    ): void {
        Assert::string($model['locale'] ?? null, 'Locale is missing');
        assert($this->api !== [], 'API configuration is missing');

        $goPayApi->authorize(
            $this->api['goid'],
            $this->api['clientId'],
            $this->api['clientSecret'],
            $this->api['isProductionMode'],
            $model['locale'],
        );
    }
}
