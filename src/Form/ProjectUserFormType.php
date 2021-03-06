<?php

namespace App\Form;

use App\Entity\ProjectUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectUserFormType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {

        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $projectId = $options['id'];
        $builder
            ->add('projectRoles', ChoiceType::class, [
                'multiple' =>true,
                'label' => 'Role:',
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
                'label' => 'Wybierz użytkownika: ',
                'choices' => $this->getUsersWithoutUsersInProject($projectId),
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
            'id'
        ]);
    }

    public function getUsersWithoutUsersInProject(int $projectId)
    {
        $query = $this->em->createQuery(
            'SELECT u FROM App\Entity\User u 
             where u.id NOT IN (Select IDENTITY( pu.user) from App\Entity\ProjectUser as pu where pu.project = :projectId)'
        );
        return $query->setParameter('projectId', $projectId)->getResult();
    }
}
