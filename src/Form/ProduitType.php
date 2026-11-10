<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'produit.nom',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('description', null, ['label' => 'produit.description'])
            ->add('prix', null, [
                'label' => 'produit.prix',
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                ],
            ])
            ->add('stock', null, [
                'label' => 'produit.stock',
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(),
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'produit.photo',
                // le fichier n'est pas lié directement au produit, il est traité dans le contrôleur
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(maxSize: '2M'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
