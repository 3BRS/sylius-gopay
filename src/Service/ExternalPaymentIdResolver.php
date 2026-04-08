<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Service;

use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

/**
 * Resolves the GoPay external payment ID for a given payment.
 *
 * Supports both:
 * - Old Payum flow: externalPaymentId stored in Payment::details
 * - New Sylius 2.x Payment Request flow: externalPaymentId stored in capture PaymentRequest payload
 */
final readonly class ExternalPaymentIdResolver
{
    /**
     * @return int|null The GoPay external payment ID, or null if not found
     */
    public function resolve(PaymentInterface $payment): ?int
    {
        return $this->fromPaymentDetails($payment)
               ?? $this->fromCaptureRequest($payment);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function extractExternalPaymentId(array $data): ?int
    {
        return isset($data[PaymentConstants::EXTERNAL_PAYMENT_ID]) && is_int($data[PaymentConstants::EXTERNAL_PAYMENT_ID])
            ? $data[PaymentConstants::EXTERNAL_PAYMENT_ID]
            : null;
    }

    private function fromPaymentDetails(PaymentInterface $payment): ?int
    {
        /** @var array<string, mixed> $details */
        $details = $payment->getDetails();

        return $this->extractExternalPaymentId($details);
    }

    private function fromCaptureRequest(PaymentInterface $payment): ?int
    {
        foreach ($payment->getPaymentRequests() as $request) {
            if ($request->getAction() === PaymentRequestInterface::ACTION_CAPTURE) {
                /** @var array<string, mixed> $payload */
                $payload = $request->getPayload() ?? [];

                return $this->extractExternalPaymentId($payload);
            }
        }

        return null;
    }
}
