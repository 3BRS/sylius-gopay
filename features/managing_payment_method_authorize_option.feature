@managing_payment_method_authorize
Feature: Managing GoPay payment method authorize option
	In order to control whether payments should be authorized or immediately captured
	As an Administrator
	I want to be able to enable or disable the authorize payment only option

	Background:
		Given the store operates on a single channel in "United States"
		And the store allows paying with name "GoPay" and code "gopay" GoPay gateway
		And I am logged in as an administrator

	@ui
	Scenario: Enabling authorize payment only for existing payment method
		Given I want to modify the "GoPay" payment method
		When I enable authorize payment only
		And I save my changes
		Then I should be notified that it has been successfully edited
		And I want to modify the "GoPay" payment method
		And the authorize payment only option should be enabled

	@ui
	Scenario: Disabling authorize payment only for existing payment method
		Given the store allows paying with name "GoPay Preauth" and code "gopay_preauth" GoPay gateway with pre-authorization enabled
		And I want to modify the "GoPay Preauth" payment method
		When I disable authorize payment only
		And I save my changes
		Then I should be notified that it has been successfully edited
		And I want to modify the "GoPay Preauth" payment method
		And the authorize payment only option should be disabled

	@ui
	Scenario: Verifying authorize payment only is disabled by default
		Given I want to modify the "GoPay" payment method
		Then the authorize payment only option should be disabled
