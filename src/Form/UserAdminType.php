<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserAdminType extends AbstractType
{
public function buildForm(FormBuilderInterface $builder, array $options): void
{
$builder
->add('Firstname',TextType::class, [
    'label' => 'Prénom',
    'required' => true,
    'attr' => [
        'placeholder' => "Le prénom",
        'class' => 'form-control',
        
    ],
    ])
    ->add('lastname',TextType::class, [
        'label' => 'nom',
        'required' => true,
        'attr' => [
            'placeholder' => "Le nom",
            'class' => 'form-control',
            
        ],
        ])
    
->add('email', EmailType::class, [
'attr' => [
'placeholder' => "L'email",
'class' => 'form-control',
],
'constraints' => [
new NotBlank([
'message' => 'Veuillez entrer un email',
]),
],
])
->add('roles', ChoiceType::class, [
'label' => 'Rôles:',
'choices' => [
'Utilisateur' => 'ROLE_USER',
'Administrateur' => 'ROLE_ADMIN',
],
'attr' => [
'class' => 'form-control',
],
'expanded' => false,
'multiple' => true,
]);
}

public function configureOptions(OptionsResolver $resolver): void
{
$resolver->setDefaults([
'data_class' => User::class,
]);
}
}