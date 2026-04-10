<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Service;

use Sylius\Component\Payment\Model\PaymentInterface;

/**
 * Resolves the GoPay external payment ID for a given payment.
 *
 * Supports both:
 * - Old Payum flow: externalPaymentId stored in Payment::details
 * - New Sylius 2.x Payment Request flow: externalPaymentId stored in capture PaymentRequest payload
 */
interface ExternalPaymentIdResolverInterface
{
    /**
     * @return int|null The GoPay external payment ID, or null if not found
     */
    public function resolve(PaymentInterface $payment): ?int;

    /**
     * @param array<string, mixed> $data
     */
    public function extractExternalPaymentId(array $data): ?int;
}
