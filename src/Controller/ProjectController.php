<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Form\ProjectFormType;
use App\Form\ProjectUserFormType;
use App\Repository\ProjectRepository;
use App\Repository\ProjectUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route ("project")
 */
//TODO zmienić routki z camelCase na podłogi
class ProjectController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;
    /**
     * @var ProjectUserRepository
     */
    private $projectUserRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(Security $security, ProjectUserRepository $projectUserRepository, EntityManagerInterface $entityManager, ProjectRepository $projectRepository)
    {
        $this->security = $security;
        $this->projectUserRepository = $projectUserRepository;
        $this->entityManager = $entityManager;
        $this->projectRepository = $projectRepository;
    }
    public function checkOwner(Project $project):bool
    {
        $owner = $this->projectUserRepository->findBy(['user' => $this->security->getUser(), 'project' => $project]);
        if(in_array('owner', $owner[0]->getProjectRoles()))
        {
            return true;
        }
        return false;
    }

    public function listProjects(): array
    {
        //TODO napisać do tego selecta, ponieważ na razie wyciągam wszystkie wartości z bazy
        $projectUser = $this->projectRepository->findAll();
        $project = $this->projectUserRepository->findBy(['user' => $this->security->getUser()]);
        return $project;
    }

    /**
     * @Route("/create", name="projectCreate")
     */
    public function createProject(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $project->setName($form->get('name')->getData());
            $project->setDescription($form->get('description')->getData());
            $project->setShare($form->get('share')->getData());
            $projectUser = new ProjectUser();
            $projectUser->setProjectRoles(['owner']);
            $projectUser->setUser($this->security->getUser());
            $projectUser->setProject($project);
            $this->entityManager->persist($projectUser);
            $this->entityManager->flush();
            return $this->redirectToRoute('singleProject', ['id' => $project->getId()]);
        }
        return $this->render('project/addProject.html.twig', [
            'addProjectForm' => $form->createView(),
        ]);
    }

    /**
     * @Route ("/{id}", name="singleProject")
     */
    public function goToProject(string $id)
    {
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        return $this->render('project/project.html.twig', [
            'project' => $project,
            'owner' => $this->checkOwner($project)
        ]);
    }
    /**
     * @Route ("/{id}/addUser", name="addUserToProject")
     */
    public function addUserToProject(string $id, Request $request,EntityManagerInterface $entityManager)
    {
        //TODO wykluczyć użytkowników, którzy są już w projekcie
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if(! $this->checkOwner($project))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
        $addUserToProject = new ProjectUser();
        $form = $this->createForm(ProjectUserFormType::class, $addUserToProject);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $addUserToProject->setProjectRoles($form->get('projectRoles')->getData());
            $addUserToProject->setUser($form->get('user')->getData());
            $addUserToProject->setProject($project);
            $this->entityManager->persist($addUserToProject);
            $this->entityManager->flush();
            return $this->redirectToRoute('singleProject', ['id' => $id]);
        }
        return $this->render('project/addUserToProject.html.twig', [
            'addUserToProjectForm' => $form->createView(),
        ]);
    }

    /**
     * @Route ("/{id}/deleteProject", name="deleteProject")
     */
    public function deleteProject(string $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if(is_null($project))
        {
            throw $this->createNotFoundException("Nie znaleziono projektu o podanym id: ". $project);
        }
        $projectUsers = $this->projectUserRepository->findBy(['project' => $project]);
        $this->entityManager->remove($project);
        foreach ($projectUsers as $projectUser)
        {
            $this->entityManager->remove($projectUser);
        }
        $this->entityManager->flush();
        //TODO Dodać flash message i potwierdzenie usunięcia
        return $this->redirectToRoute("homepage");
    }
}