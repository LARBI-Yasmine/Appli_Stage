<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('Firstname',TextType::class, [
            'label' => 'Prénom',
            'required' => true,
            'attr' => [
                'placeholder' => "Votre Prénom",
                'class' => 'form-control',
                
            ],
            ])
        ->add('Lastname',TextType::class, [
            'label' => 'Nom',
            'required' => true,
            'attr' => [
                'placeholder' => "Votre Nom",
                'class' => 'form-control',
                
            ],
            ])
            ->add('adressePostale',TextType::class, [
                'label' => 'Adresse postale',
                'required' => true,
                'attr' => [
                    'placeholder' => "Votre Adresse postale",
                    'class' => 'form-control',
                    
                ],
                ])
                ->add('numtelephone',TextType::class, [
                    'label' => 'Numéro Télèphone',
                    'required' => true,
                    'attr' => [
                        'placeholder' => "Votre Numéro de Télèphone",
                        'class' => 'form-control',
                        
                    ],
                    ])
                    ->add('profession',TextType::class, [
                        'label' => 'Profession',
                        'required' => true,
                        'attr' => [
                            'placeholder' => "Votre profession",
                            'class' => 'form-control',
                            
                        ],
                        ])
        ->add('email',EmailType::class,[
            'label' => 'Email',
           
            'attr' => [
                'placeholder' => 'Votre Email',
                'class' => 'form-control',
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter an email',
                ]),
                new Callback([$this, 'validateEmailDomain']),


            ]
        ])

        
        ->add('password', PasswordType::class, [
                           
            'label' => 'Mot de Passe',
            'attr' => [
                
                'placeholder' => 'Votre Mot de Passe',
                'autocomplete' => 'new-password',
                'class' => 'form-control',
                
            ],
            
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer un mot de passe',
                ]),
                new Length([
                    'min' => 10,
                    'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
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
    public function validateEmailDomain($Email, ExecutionContextInterface $context): void
    {
        if (!str_ends_with($Email, '@myges.fr')) {
            $context->buildViolation('Veuillez utiliser une adresse email se terminant par @myges.fr')
                ->atPath('email')
                ->addViolation();
        }
    }
}