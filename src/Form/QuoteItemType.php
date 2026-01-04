<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\QuoteItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Désignation',
                'attr' => [
                    'placeholder' => 'Ex: Développement site web',
                    'class' => 'form-input'
                ]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'min' => 1,
                    'value' => 1,
                    'class' => 'form-input'
                ]
            ])
            ->add('unitPriceHt', NumberType::class, [
                'label' => 'Prix unitaire HT (€)',
                'scale' => 2,
                'attr' => [
                    'step' => '0.01',
                    'min' => 0,
                    'class' => 'form-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteItem::class,
        ]);
    }
}
