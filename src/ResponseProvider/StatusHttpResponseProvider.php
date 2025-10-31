<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\ResponseProvider;

use Sylius\Bundle\PaymentBundle\Provider\HttpResponseProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final readonly class StatusHttpResponseProvider implements HttpResponseProviderInterface
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function supports(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): bool {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_STATUS;
    }

    public function getResponse(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): Response {
        // After checking status, redirect to thank you page
        // Simply redirect to thank you page - the status was already checked by StatusPaymentRequestHandler
        $thankYouUrl = $this->router->generate('sylius_shop_order_thank_you');

        return new RedirectResponse($thankYouUrl);
    }
}
