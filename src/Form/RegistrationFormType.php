<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('roles', ChoiceType::class, [
                'multiple' =>true,
                'label' => 'Role :',
                'choices' =>[
                    'Product Owner' => 'Product Owner',
                    'Scrum Master' => 'Scrum Master',
                    'Front-end Developer' => 'Front-end Developer',
                    'Back-end Developer' => 'Back-end Developer',
                    'Full Stack Developer' => 'Full Stack Developer',
                    'Tester' => 'Tester',
                    'Software Engineer' => 'Software Engineer',
                    'Analityk' => 'Analityk'
                ]
            ])
            ->add('name')
            ->add('surname')
            ->add('phoneNumber', TelType::class, [
                'constraints' => [
                  new NotBlank(),
                  new Length(['min' => 8, 'max' => 20, 'minMessage' => "Minimalna długość powinna wynieść 8", 'maxMessage' => "Maksymalna długośc powinna wynieść 20"]),
                  new Regex(['pattern' => "/^([0-9]+)$/", 'message'=>"Powinienieś podać wartość liczbową"])
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'label' => 'Hasło :',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Proszę podaj swoje hasło',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Twoje hasło powinno zawierać przynajmniej {{ limit }} znaków',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
