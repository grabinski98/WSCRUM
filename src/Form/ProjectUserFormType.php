<?php

namespace App\Form;

use App\Entity\ProjectUser;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $usersInProject = $options['usersInProject'];
        $usersInSystem = $options['usersInSystem'];
        $builder
            ->add('projectRoles', ChoiceType::class, [
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
            ->add('user', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er){
                return $er->createQueryBuilder('u')
                    ->where('u.email != :email')
                    ->setParameter('email', 'tester@wp.pl');
                },
                'choice_label' => 'username'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectUser::class,
        ]);
        $resolver->setRequired([
            'usersInProject',
            'usersInSystem'
        ]);
    }
}
