
## Upgrade from Sylius v1 to v2
(from 3brs/sylius-gopay-payum-plugin <= 2.3.0 to 3brs/sylius-gopay-plugin:^3.0)

### 1. Update Composer Dependencies

Update your `composer.json`:

```bash
# The package was renamed, so remove the old one first
composer remove 3brs/sylius-gopay-payum-plugin

# Install the new package
composer require 3brs/sylius-gopay-plugin:^3.0
```

### 2. Update Bundle Registration

Update `config/bundles.php`:

```diff
- ThreeBRS\SyliusGoPayPayumPlugin\SyliusGoPayPayumPlugin::class => ['all' => true],
+ ThreeBRS\SyliusGoPayPlugin\ThreeBRSSyliusGoPayPlugin::class => ['all' => true],
```

### 3. Update Configuration Files

#### Remove Old State Machine Configuration

If you have custom winzou_state_machine configuration for GoPay payment callbacks, remove it. The plugin now uses Symfony Workflow event listeners configured automatically.

**Remove** from your configuration (if present):

```yaml
# Old winzou_state_machine configuration - NO LONGER NEEDED
winzou_state_machine:
    sylius_payment:
        callbacks:
            after:
                threebrs.sylius_gopay_payum.refund:
                    on: [ "refund" ]
                    do: [ "@threebrs.gopay_payum.state_machine.refund", "__invoke" ]
                    args: [ "object", "event.getState()" ]
                threebrs.sylius_gopay_payum.cancel:
                    on: [ "cancel" ]
                    do: [ "@threebrs.gopay_payum.state_machine.cancel", "__invoke" ]
                    args: [ "object", "event.getState()" ]
```

The plugin now automatically handles payment state transitions using Symfony Workflow.

### 4. Update Custom Code (if applicable)

#### Namespace Changes

If your code references plugin classes directly, update the namespace:

```diff
- use ThreeBRS\SyliusGoPayPayumPlugin\Api\GoPayApiInterface;
+ use ThreeBRS\SyliusGoPayPlugin\Api\GoPayApiInterface;
```

#### Service ID Changes

If you reference plugin services in your configuration, update service IDs:

```diff
- threebrs.gopay_payum.api
+ threebrs.gopay.api

- threebrs.gopay_payum.payments.factory
+ threebrs.gopay.payments.factory
```

#### Translation Key Changes

If you override translations, update the keys:

```diff
  threebrs:
-     gopay_payum_plugin:
+     gopay_plugin:
          gateway_label: GoPay
          # ... other translations
```

### 5. Architectural Changes

The plugin no longer uses Payum. It now integrates with Sylius 2.0's native Payment Request system:

- **Removed**: All Payum actions, gateway factories, and request classes
- **Added**: Command/CommandHandler architecture for payment operations
- **Added**: Payment authorization support (reserve without immediate charge)

These changes are internal and should not require code changes in your application unless you:
- Extended Payum action classes
- Customized the Payum gateway factory
- Used Payum request factories directly

If you customized any Payum-specific code, you'll need to migrate to the new Command/CommandHandler pattern. Review the new classes in:
- `src/Command/` - Payment request commands
- `src/CommandHandler/` - Payment operation handlers
- `src/CommandProvider/` - Command providers for payment actions

### 6. New Features

#### Payment Authorization

Version 3.0 adds support for payment authorization. You can now configure payment methods to only reserve the amount without immediately charging:

1. Go to Admin → Payment Methods → Edit GoPay payment method
2. Enable "Just authorize the payments" option
3. Save the changes

When enabled, payments will be reserved but not charged until manually marked as "Completed" in the admin panel.

### 7. Clear Cache and Test

After upgrading:

```bash
# Clear cache
bin/console cache:clear

# Clear Symfony cache
rm -rf var/cache/*

# Test payment flows in your application
```

### 8. Database Migrations

No database migrations are required. The plugin uses Sylius's existing payment tables.

### Breaking Changes Summary

| Category | Old (v2.x) | New (v3.0) |
|----------|-----------|-----------|
| Package name | `3brs/sylius-gopay-payum-plugin` | `3brs/sylius-gopay-plugin` |
| Namespace | `ThreeBRS\SyliusGoPayPayumPlugin` | `ThreeBRS\SyliusGoPayPlugin` |
| Plugin class | `SyliusGoPayPayumPlugin` | `ThreeBRSSyliusGoPayPlugin` |
| PHP version | ^8.0 | ^8.2 |
| Sylius version | 1.14.* | 2.0.* |
| Architecture | Payum-based | Sylius Payment Request |
| State machine | winzou_state_machine | Symfony Workflow |
| Translation keys | `threebrs.gopay_payum_plugin.*` | `threebrs.gopay_plugin.*` |
