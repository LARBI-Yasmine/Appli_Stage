<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;

class CompleteProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
     
            ->add('adressePostale', TextType::class, [
                'label' => 'Adresse postale',
                'required' => true,
            ])
            ->add('numtelephone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}