<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Service;

use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use ThreeBRS\SyliusGoPayPlugin\Model\PaymentConstants;

readonly class ExternalPaymentIdResolver implements ExternalPaymentIdResolverInterface
{
    public function resolve(PaymentInterface $payment): ?int
    {
        return $this->fromPaymentDetails($payment)
               ?? $this->fromCaptureRequest($payment);
    }

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
