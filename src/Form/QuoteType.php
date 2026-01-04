<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Quote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Section Entreprise
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise *',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: ACME SARL']
            ])
            ->add('companyContact', TextType::class, [
                'label' => 'Contact *',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: Jean Dupont']
            ])
            ->add('companyAddress', TextareaType::class, [
                'label' => 'Adresse *',
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => '12 rue de la Paix, 75000 Paris']
            ])
            ->add('companyEmail', EmailType::class, [
                'label' => 'Email *',
                'attr' => ['class' => 'form-input', 'placeholder' => 'contact@acme.fr']
            ])
            ->add('companyPhone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '01 23 45 67 89']
            ])
            ->add('companySiret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => '123 456 789 00010']
            ])
            ->add('companyLogo', FileType::class, [
                'label' => 'Logo (optionnel)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Formats acceptés: JPG, PNG (max 2Mo)',
                    ])
                ],
                'attr' => ['class' => 'form-input', 'accept' => 'image/jpeg,image/png,image/jpg']
            ])

            // Section Client
            ->add('clientName', TextType::class, [
                'label' => 'Nom du client *',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: Marie Martin']
            ])
            ->add('clientCompany', TextType::class, [
                'label' => 'Société du client',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: TechCorp']
            ])
            ->add('clientAddress', TextareaType::class, [
                'label' => 'Adresse du client *',
                'attr' => ['class' => 'form-input', 'rows' => 3, 'placeholder' => '45 avenue des Champs, 69000 Lyon']
            ])
            ->add('clientEmail', EmailType::class, [
                'label' => 'Email du client',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'marie@techcorp.fr']
            ])

            // Section Devis
            ->add('quoteNumber', TextType::class, [
                'label' => 'Numéro de devis',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Laissez vide pour génération auto']
            ])
            ->add('quoteDate', DateType::class, [
                'label' => 'Date du devis *',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input']
            ])
            ->add('quoteValidUntil', DateType::class, [
                'label' => 'Valide jusqu\'au',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('quoteDescription', TextareaType::class, [
                'label' => 'Description / Objet',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 2, 'placeholder' => 'Prestations de développement web...']
            ])

            // Lignes de devis
            ->add('items', CollectionType::class, [
                'entry_type' => QuoteItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'attr' => ['class' => 'items-collection']
            ])

            // TVA et conditions
            ->add('vatRate', ChoiceType::class, [
                'label' => 'Taux de TVA *',
                'choices' => [
                    'Non assujetti (0%)' => 0.0,
                    'Réduit (5,5%)' => 5.5,
                    'Intermédiaire (10%)' => 10.0,
                    'Normal (20%)' => 20.0,
                ],
                'attr' => ['class' => 'form-input']
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'Conditions de paiement',
                'required' => false,
                'attr' => ['class' => 'form-input', 'rows' => 3]
            ])

            ->add('submit', SubmitType::class, [
                'label' => '📄 Télécharger le PDF',
                'attr' => ['class' => 'btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}