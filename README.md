# Sylius GoPay payment gateway plugin  
<div align="center">
    <a href="https://www.gopay.com" title="GoPay" target="_blank"><img src="https://dl.dropboxusercontent.com/s/af8fiebcqmk9wgm/GoPay-logo-varianta-A-PANTONE.png" width="300" /></a>
</div>

<a href="https://packagist.org/packages/3brs/sylius-gopay-payum-plugin" title="License" target="_blank">
    <img src="https://img.shields.io/packagist/l/3brs/sylius-gopay-payum-plugin.svg" />
</a>
<a href="https://packagist.org/packages/3brs/sylius-gopay-payum-plugin" title="Version" target="_blank">
    <img src="https://img.shields.io/packagist/v/3brs/sylius-gopay-payum-plugin.svg" />
</a>
<a href="https://circleci.com/gh/3BRS/sylius-gopay-payum-plugin" title="Build status" target="_blank">
    <img src="https://circleci.com/gh/3BRS/sylius-gopay-payum-plugin.svg?style=shield" />
</a>

## Capabilities

- **Payment** via Sylius standard checkout
- **Refund** via Sylius admin
  - _Refund_ button in order detail triggers call of GoPay API

## Installation
Until pull request is merged, require it this way:
```bash
composer require 3brs/sylius-gopay-payum-plugin
```

Add plugin dependencies to your bundles.php file:

```php
ThreeBRS\SyliusGoPayPlugin\ThreeBRSSyliusGoPayPlugin::class => ['all' => true]
```

## Usage
Add your test credentials in Sylius admin as new payment method. Complete couple of orders with different states and send email to GoPay authorities.

After the review you will get production credentials, so just change it in Sylius admin, and you are ready to go.

#### Add GoPay programatically
```mysql
-- CHANGE the `config` JSON values to your GoPay credentials
INSERT INTO sylius_gateway_config (config, gateway_name, factory_name) VALUES ('{"isProductionMode": false, "goid": "TEST", "clientId": "TEST", "clientSecret": "TEST"}', 'gopay', 'gopay');

INSERT INTO sylius_payment_method (code, environment, is_enabled, position, created_at, updated_at, gateway_config_id)
VALUES ('gopay', NULL, 1, 0, NOW(), NOW(), (SELECT id FROM sylius_gateway_config WHERE gateway_name = 'gopay'));

INSERT INTO sylius_payment_method_translation (translatable_id, name, description, instructions, locale) VALUES ((SELECT id FROM sylius_payment_method WHERE code = 'gopay'), 'GoPay', '', null, 'en_US');

INSERT INTO sylius_payment_method_channels (payment_method_id, channel_id)
VALUES ((SELECT id FROM sylius_payment_method WHERE code = 'gopay'), (SELECT id FROM sylius_channel LIMIT 1));
```

## Credits

Built on top of https://github.com/Prometee/gopay-plugin, which is fork of https://github.com/bratiask/gopay-plugin, which is fork of https://github.com/czende/gopay-plugin.

Thank you all for your work!
