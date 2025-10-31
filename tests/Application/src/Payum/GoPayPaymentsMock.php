<?php

declare(strict_types=1);

namespace Tests\ThreeBRS\SyliusGoPayPlugin\Payum;

use GoPay\Http\Response;
use GoPay\Payments;

class GoPayPaymentsMock extends Payments
{
    private ?int $lastPaymentId = null;
    private ?int $lastAmount = null;

    public function __construct()
    {
        // to disable the constructor of the parent class
    }

    public function refundPayment(
        $id,
        $data,
    ): Response {
        assert(is_int($id), 'Expected int, got ' . gettype($id));
        assert(is_int($data), 'Expected int, got ' . gettype($data));

        $this->lastPaymentId = $id;
        $this->lastAmount = $data;

        /**
         * @see \ThreeBRS\SyliusGoPayPlugin\Payum\Action\GoPayAction::processRefund
         * for expected response
         */
        $data = ['id' => 3276091767, 'result' => 'FINISHED'];
        $json = json_encode($data);
        $response = new Response($json);
        $response->json = $data;
        $response->statusCode = 200;

        return $response;
    }

    public function getStatus(
        $id,
    ): Response {
        assert(is_int($id), 'Expected int, got ' . gettype($id));

        $this->lastPaymentId = $id;

        /**
         * @see \ThreeBRS\SyliusGoPayPlugin\Payum\Action\GoPayAction::processCapture()
         * for expected response
         */
        $data = ['orderId' => 1234, 'externalPaymentId' => 4567, 'state' => 'REFUNDED'];
        $json = json_encode($data);
        $response = new Response($json);
        $response->json = $data;
        $response->statusCode = 200;

        return $response;
    }

    public function getLastPaymentId(): ?int
    {
        return $this->lastPaymentId;
    }

    public function getLastAmount(): ?int
    {
        return $this->lastAmount;
    }
}
