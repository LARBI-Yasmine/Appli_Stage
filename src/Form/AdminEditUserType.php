<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class AdminEditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', EmailType::class, [
            
            'required' => true,
            'attr' => [
                'class' => 'form-control mb-4'
            ],
            
        ])


        ->add('roles', ChoiceType::class, [
            'label' => 'Roles:',
            'choices' => [
                        '' => '',
                        'Utilisateur' => 'ROLE_USER',
                        'Administrateur' => 'ROLE_ADMIN',
                        
                    ],
                    'attr' => [
                        'class' => 'form-control',
                        
                    ],
                    'expanded' => false,
                    'multiple' => true,
                    'constraints' => [
                        new NotNull([
                            'message' => 'Veuillez choisir un role.',
                        ]),
                    ],
                ]);

       

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}