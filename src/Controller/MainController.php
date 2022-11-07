<?php

namespace App\Controller;

use App\Entity\DossierAgrement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $dossiers = $em->getRepository(DossierAgrement::class)->findAll();
        return $this->render('main/index.html.twig', [
            'dossiers' => $dossiers,
        ]);
    }
}
