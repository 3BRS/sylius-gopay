<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusGoPayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class GoPayGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isProductionMode', CheckboxType::class, [
                'label' => 'threebrs.gopay_plugin.is_production_mode',
                'required' => false,
            ])
            ->add('useAuthorize', CheckboxType::class, [
                'label' => 'threebrs.gopay_plugin.use_authorize',
                'help' => 'threebrs.gopay_plugin.use_authorize_help',
                'required' => false,
            ])
            ->add('goid', TextType::class, [
                'label' => 'threebrs.gopay_plugin.goid',
                'constraints' => [
                    new NotBlank([
                        'message' => 'threebrs.gopay_plugin.gateway_configuration.goid.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('clientId', TextType::class, [
                'label' => 'threebrs.gopay_plugin.client_id',
                'constraints' => [
                    new NotBlank([
                        'message' => 'threebrs.gopay_plugin.gateway_configuration.client_id.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('clientSecret', TextType::class, [
                'label' => 'threebrs.gopay_plugin.client_secret',
                'constraints' => [
                    new NotBlank([
                        'message' => 'threebrs.gopay_plugin.gateway_configuration.client_secret.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ]);
    }
}
