@refunding_order_payment
Feature: Refunding order's payment
    In order to refund order's payment
    As an Administrator
    I want to be able to mark order's payment as refunded

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Green Arrow" priced at "$100.00"
        And the store ships everywhere for Free
        And the store allows paying with name "GoPay" and code "gopay" GoPay gateway
        And there is a customer "oliver@teamarrow.com" that placed an order "#00000001"
        And the customer bought a single "Green Arrow"
        And the customer chose "Free" shipping method to "United States" with "GoPay" payment
        And this order is already paid by GoPay with external payment ID 9999
        And I am logged in as an administrator
        And I am viewing the summary of this order

    @ui
    Scenario: Marking order's payment as refunded
        When I mark this order's payment as refunded
        Then GoPay should be requested to refund this order with this external payment ID
        And I should be notified that the order's payment has been successfully refunded
        And it should have payment with state refunded

    @ui
    Scenario: Marking an order as refunded after refunding all its payments
        When I mark this order's payment as refunded
        Then GoPay should be requested to refund this order with this external payment ID
        And it should have payment with state refunded
        And its payment state should be refunded
