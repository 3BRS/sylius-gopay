@capturing_authorized_payment
Feature: Capturing authorized payment
    In order to capture funds that were previously authorized
    As an Administrator
    I want to be able to complete an authorized payment

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Green Arrow" priced at "$100.00"
        And the store ships everywhere for Free
        And the store allows paying with name "GoPay" and code "gopay" GoPay gateway with pre-authorization enabled
        And there is a customer "oliver@teamarrow.com" that placed an order "#00000001"
        And the customer bought a single "Green Arrow"
        And the customer chose "Free" shipping method to "United States" with "GoPay" payment
        And this order is already authorized by GoPay with external payment ID 8888
        And I am logged in as an administrator
        And I am viewing the summary of this order

    @ui
    Scenario: Capturing an authorized payment
        When I mark this order as paid
        Then GoPay should be requested to capture authorization for this order with this external payment ID
        And I should be notified that the order's payment has been successfully completed
        And it should have payment with state completed

    @ui
    Scenario: Marking an order as paid after capturing authorized payment
        When I mark this order as paid
        Then GoPay should be requested to capture authorization for this order with this external payment ID
        And it should have payment with state completed
