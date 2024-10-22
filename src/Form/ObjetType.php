<?php
namespace App\Form;

use App\Entity\Objet;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
    ->add('nom', TextType::class, [
    'label' => 'Nom Objet:',
    'attr' => ['class' => 'form-control'],
    'constraints' => [new NotBlank(['message' => 'Le nom ne peut pas être vide.'])]
    ])

    ->add('description', TextType::class, [
    'label' => 'Description:',
    'attr' => ['class' => 'form-control']
    ])

    ->add('num_serie', TextType::class, [
    'label' => 'Numéro de Série:',
    'attr' => ['class' => 'form-control'],
    'constraints' => [
    new NotBlank(['message' => 'Le numéro de série ne peut pas être vide.']),
    new Length(['min' => 5, 'max' => 40])
    ]
    ])

    ->add('etat_usure', TextType::class, [
    'label' => 'État Usure:',
    'attr' => ['class' => 'form-control']
    ])

    ->add('categorie', ChoiceType::class, [
    'label' => 'Catégorie:',
    'choices' => [
    'Sur Place' => 'Sur Place',
    'Pour Emprunt' => 'Pour Emprunt'
    ],
    'attr' => ['class' => 'form-control']
    ])

    ->add('disponibilite', ChoiceType::class, [
    'label' => 'Disponibilité:',
    'choices' => [
    'Disponible' => 'Disponible',
    'Réservé' => 'Réservé',
    'Endommagé' => 'Endommagé',
    'En réparation' => 'En réparation',
    'Perdu' => 'Perdu'
    ],
    'attr' => ['class' => 'form-control']
    ])

    ->add('photo_url', FileType::class, [
    'label' => 'Photo:',
    'mapped' => false, // non mappé car géré manuellement
    'required' => false, // pas obligatoire
    'constraints' => [
    new File([
   
    'mimeTypes' => ['image/jpeg', 'image/png'],
    'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG/PNG).'
    ])
],
'attr' => ['class' => 'form-control']
]);

        // Event listener to handle unchanged fields
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
        $objet = $event->getData();
        $form = $event->getForm();

        if ($objet && $objet->getPhotoUrl()) {
        $form->add('photo_url', FileType::class, [
        'label' => 'Changer la photo (laissez vide pour conserver l\'actuelle)',
        'mapped' => false,
        'required' => false, // Facultatif, si aucune nouvelle image n'est fournie
        'attr' => ['class' => 'form-control']
        ]);
        }
        });
}

public function configureOptions(OptionsResolver $resolver): void
{
$resolver->setDefaults([
'data_class' => Objet::class,
]);
}
}