<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Payum;

use GoPay\Definition\Language;
use GoPay\Definition\TokenScope;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\CaptureAction;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\ConvertPaymentAction;
use ThreeBRS\SyliusGoPayPlugin\Payum\Action\StatusAction;

class GoPayPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'gopay',
            'payum.factory_title' => 'GoPay',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.status' => new StatusAction(),
        ]);

        if (!$config['payum.api']) {
            $defaultOptions = [
                'goid' => '',
                'clientId' => '',
                'clientSecret' => '',
                'isProductionMode' => false,
            ];
            $config['payum.default_options'] = $defaultOptions;
            $config->defaults($defaultOptions);

            $config['payum.required_options'] = ['goid', 'clientId', 'clientSecret'];

            $config['payum.api'] = static function (ArrayObject $config) {
                $requiredOptions = $config['payum.required_options'];
                assert(is_array($requiredOptions));
                $config->validateNotEmpty($requiredOptions);

                return [
                    'goid' => $config['goid'],
                    'clientId' => $config['clientId'],
                    'clientSecret' => $config['clientSecret'],
                    'isProductionMode' => $config['isProductionMode'],
                    'scope' => TokenScope::ALL,
                    'language' => Language::ENGLISH,
                    'timeout' => 30,
                ];
            };
        }
    }
}
