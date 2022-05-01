<?php

namespace App\Controller;

use App\Entity\ProductBacklog;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Form\AddUserStoryFormType;
use App\Form\ProjectFormType;
use App\Form\ProjectUserFormType;
use App\Repository\ProductBacklogRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route ("project")
 */
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

    public function checkRole(Project $project, string $role):bool
    {
        $array = $this->projectUserRepository->findBy(['user' => $this->security->getUser(), 'project' => $project]);
        if(in_array($role, $array[0]->getProjectRoles()))
        {
            return true;
        }
        return false;
    }

    public function listProjects(): array
    {
        //TODO napisać do tego qb, ponieważ na razie wyciągam wszystkie wartości z bazy
        $project = $this->projectRepository->findAll();
        $projectUser = $this->projectUserRepository->findBy(['user' => $this->security->getUser()]);
        return $projectUser;
    }

    public function listProjectsHome(): array
    {
        //TODO napisać do tego qb, ponieważ na razie wyciągam wszystkie wartości z bazy
        $project = $this->projectRepository->findAll();
        $projectUser = $this->projectUserRepository->findBy(['project' => $project]);
        return $project;
    }

    /**
     * @Route ("/get-single-project" , name="get_single_project_for_ajax")
     */
    public function getSingleProject(Request $request, SerializerInterface $serializer)
    {
        $id = $request->get('projectId');
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        /** @var ProjectUser $projectUser */
        $projectUser = $this->projectUserRepository->findBy(['project' => $project]);

        $jsonContent = json_encode($projectUser, JSON_PRETTY_PRINT);
        return new JsonResponse($jsonContent);

    }

    /**
     * @Route("/create", name="project_create")
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
            return $this->redirectToRoute('single_project', ['id' => $project->getId()]);
        }
        return $this->render('project/addProject.html.twig', [
            'addProjectForm' => $form->createView(),
        ]);
    }

    /**
     * @Route ("/{id}", name="single_project")
     */
    public function goToProject(string $id)
    {
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        return $this->render('project/project.html.twig', [
            'project' => $project,
            'owner' => $this->checkRole($project, 'owner')
        ]);
    }
    /**
     * @Route ("/{id}/addUser", name="add_user_to_project")
     */
    public function addUserToProject(string $id, Request $request,EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if(! $this->checkRole($project, 'owner'))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
        $addUserToProject = new ProjectUser();
        $form = $this->createForm(ProjectUserFormType::class, $addUserToProject, [
            'id' => $project->getId()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $addUserToProject->setProjectRoles($form->get('projectRoles')->getData());
            $addUserToProject->setUser($form->get('user')->getData());
            $addUserToProject->setProject($project);
            $this->entityManager->persist($addUserToProject);
            $this->entityManager->flush();
            return $this->redirectToRoute('single_project', ['id' => $id]);
        }
        return $this->render('project/addUserToProject.html.twig', [
            'addUserToProjectForm' => $form->createView(),
            'owner' => $this->checkRole($project, 'owner'),
            'project' => $project
        ]);
    }

    /**
     * @Route ("/delete/project", name="delete_project")
     */
    public function deleteProject(Request $request)
    {
        $id = $request->get('projectId');
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $id]);
        if(! $this->checkRole($project, 'owner'))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
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
        //TODO potwierdzenie usunięcia
        $this->addFlash('success', $projectUser->getProject()->getName().' został usunięty!');
        return new JsonResponse($project->getName());
    }
    /**
     * @Route ("/{projectId}/productBacklog", name="product_backlog")
     */
    public function productBacklog(string $projectId, ProductBacklogRepository $productBacklogRepository)
    {
        $project = $this->projectRepository->findOneBy(['id' => $projectId]);
        $productBacklog = $productBacklogRepository->findBy(['project' => $project]);
        return $this->render('product_backlog/index.html.twig',[
            'productsBacklog' => $productBacklog,
            'owner' => $this->checkRole($project, 'owner'),
            'project' => $project,
            'productOwner' => $this->checkRole($project, 'Product Owner')
        ]);
    }
    /**
     * @Route ("/{projectId}/productBacklog/addUserStory", name="add_user_story")
     */
    public function addUserStory(string $projectId, Request $request)
    {
        $project = $this->projectRepository->findOneBy(['id' => $projectId]);
        if(! $this->checkRole($project, 'Product Owner'))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
        $addUserStory = new ProductBacklog();
        $form = $this->createForm(AddUserStoryFormType::class, $addUserStory);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $addUserStory->setProject($project);
            $addUserStory->setName($form->get('name')->getData());
            $addUserStory->setDescription($form->get('description')->getData());
            $addUserStory->setPriority($form->get('priority')->getData());
            $addUserStory->setStatus("new");
            $this->entityManager->persist($addUserStory);
            $this->entityManager->flush();
            return $this->redirectToRoute('product_backlog', ['projectId' => $projectId]);
        }
        return $this->render('product_backlog/addUserStory.html.twig',[
            'addUserStory' => $form->createView(),
            'owner' => $this->checkRole($project, 'owner'),
            'project' => $project
        ]);
    }
    /**
     * @Route ("/{projectId}/productBacklog/{userStoryId}/deleteUserStory", name="delete_user_story")
     */
    public function deleteUserStory(string $projectId, string $userStoryId, ProductBacklogRepository $productBacklogRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $projectId]);
        if(! $this->checkRole($project, 'Product Owner'))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
        if(is_null($project))
        {
            throw $this->createNotFoundException("Nie znaleziono projektu o podanym id: ". $project);
        }
        $userStory = $productBacklogRepository->findOneBy(['id' => $userStoryId]);
        $this->entityManager->remove($userStory);
        $this->entityManager->flush();
        $this->addFlash('success', 'Historyjka o nazwie: '.$userStory->getName().' została usunięta!');
        return $this->redirectToRoute("product_backlog", ['projectId' => $projectId]);
    }

    /**
     * @Route ("/{projectId}/productBacklog/{userStoryId}/editUserStory", name="edit_user_story")
     */
    public function editUserStory(string $projectId, string $userStoryId, ProductBacklogRepository $productBacklogRepository, Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Użytkownik niezalogowany, próbował dostać się do tej strony');
        $project = $this->projectRepository->findOneBy(['id' => $projectId]);
        if(! $this->checkRole($project, 'Product Owner'))
        {
            //TODO stworzyć template do obsługi błędów
            return new Response("Nie jesteś uprawniony do tej czynności",403);
        }
        if(is_null($project))
        {
            throw $this->createNotFoundException("Nie znaleziono projektu o podanym id: ". $project);
        }
        $editUserStory = $productBacklogRepository->findOneBy(['id' => $userStoryId]);
        $form = $this->createForm(AddUserStoryFormType::class, $editUserStory);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $editUserStory->setName($form->get('name')->getData());
            $editUserStory->setDescription($form->get('description')->getData());
            $editUserStory->setPriority($form->get('priority')->getData());
            $this->entityManager->persist($editUserStory);
            $this->entityManager->flush();
            $this->addFlash('success', 'Historyjka o nazwie: ' . $editUserStory->getName() . ' została zedytowana!');
            return $this->redirectToRoute("product_backlog", ['projectId' => $projectId]);
        }
        return $this->render('product_backlog/addUserStory.html.twig',[
            'addUserStory' => $form->createView(),
            'owner' => $this->checkRole($project, 'owner'),
            'project' => $project
        ]);
    }
}
