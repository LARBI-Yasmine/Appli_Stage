<?php

namespace App\Form;

use App\Entity\Retour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class RetourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $today = new \DateTime();
        $builder
        ->add('returnDate', DateTimeType::class, [
            'label' => 'Date de retour',
            'widget' => 'single_text',
           
            'attr' => [
                'class' => 'form-control',
              'min' => $today->format('Y-m-d\TH:i'),
              
            ],
            
          
        ])
        ->add('objectStatus', ChoiceType::class, [
            'label' => 'État de l\'objet',
            'choices' => [
                'Bon état' => 'Bon état',
                'Endommagé' => 'Endommagé',
                'Manquant' => 'Manquant'
            ]
            ]);
   
            }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Retour::class,
        ]);
    }
}