<?php

namespace App\Form;

use App\Entity\Objet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ObjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
      

            ->add('nom',TextType::class, [
                'label' => 'Nom Objet:',
                'attr' => [
                    'placeholder' => "Saisir nom de l'objet",
                    'class' => 'form-control',
                    
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de l\'objet ne peut pas être vide.',
                    ]),
                ],
                ])


            ->add('num_serie',TextType::class, [
                'label' => 'Numéro de Série:',
                'attr' => [
                    'placeholder' => "Saisir numéro de série",
                    'class' => 'form-control',
                    
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le numéro de série ne peut pas être vide.',
                    ]),
                new Length([
                    'min' => 5,
                    'minMessage' => 'Le numéro de série doit comporter au moins {{ limit }} caractères',
                    'max' => 40,
                ])
            ],
            ])


           
            ->add('etat_usure',TextType::class, [
                'label' => 'Etat Usure:',
                'attr' => [
                    'placeholder' => "Saisir Etat d'usure",
                    'class' => 'form-control',
                    
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'état d\'usure ne peut pas être vide.',
                    ]),
                ],
                ])

            ->add('categorie', ChoiceType::class, [
                'label' => 'Categorie:',
                'choices' => [
                            '' => '',
                            'Sur Place' => 'Sur Place',
                            'Pour Emprunt' => 'Pour Emprunt',
                            
                        ],
                        'attr' => [
                            'class' => 'form-control',
                            
                        ],
                        'constraints' => [
                            new NotNull([
                                'message' => 'Veuillez choisir une catégorie.',
                            ]),
                        ],
                    ])

            ->add('disponibilite', ChoiceType::class, [
                'label' => 'Disponibilité:',
                'choices' => [
                            'Disponible' => 'Disponible',
                            'Réservé' => 'Réservé',
                            'Endommagé' => 'Endommagé',
                            'En réparation' => 'En réparation',
                            'Perdu' => 'Perdu',
                            
                        ],
                        'attr' => [
                       
                            'class' => 'form-control',
                            
                        ],
                        'constraints' => [
                            new NotNull([
                                'message' => 'Veuillez choisir une disponibilité.',
                            ]),
                        ],
                    ])

            ->add('photo_url', FileType::class, [
                'label' => 'Photo :',
                'mapped' => false, 
                'attr' => [
                  
                    'class' => 'form-control',
                    
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier image valide (JPEG/PNG)',
                    ])
                ],
            ])
        
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'label' => 'Enregistré le:',
                'attr' => [
                    'class' => 'form-control',
                    
                ],
                
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objet::class,
        ]);
    }
}