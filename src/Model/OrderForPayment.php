<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Model;

final readonly class OrderForPayment
{
    /**
     * @param array<string, mixed> $customerData
     */
    public function __construct(
        private string $currency,
        private int $amount,
        private string $orderNumber,
        private array $customerData = [],
    ) {
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerData(): array
    {
        return $this->customerData;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'amount' => $this->amount,
            'orderNumber' => $this->orderNumber,
            'customerData' => $this->customerData,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            // @phpstan-ignore-next-line
            currency: (string) ($data['currency'] ?? ''),
            // @phpstan-ignore-next-line
            amount: (int) ($data['amount'] ?? 0),
            // @phpstan-ignore-next-line
            orderNumber: (string) ($data['orderNumber'] ?? ''),
            // @phpstan-ignore-next-line
            customerData: (array) ($data['customerData'] ?? []),
        );
    }
}
