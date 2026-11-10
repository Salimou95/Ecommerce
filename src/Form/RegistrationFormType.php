<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'inscription.nom',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 100),
                ],
            ])
            ->add('prenom', null, [
                'label' => 'inscription.prenom',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'inscription.email',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'inscription.mot_de_passe',
                // Non lié à l'entité : le mot de passe est hashé dans le contrôleur
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'inscription.mot_de_passe_vide',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'inscription.mot_de_passe_court',
                        // Longueur max autorisée par Symfony pour des raisons de sécurité
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
