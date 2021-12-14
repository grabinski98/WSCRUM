<?php


namespace App\Controller;


use App\Repository\ProjectRepository;
use App\Repository\ProjectUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    /**
     * @Route ("/", name = "homepage")
     */
    public function home()
    {
        return $this->render('home/index.html.twig', [
        ]);
    }
}