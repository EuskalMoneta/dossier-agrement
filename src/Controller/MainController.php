<?php

namespace App\Controller;

use App\Entity\DossierAgrement;
use App\Repository\DossierAgrementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index( Request $request, EntityManagerInterface $em): Response
    {
        $dossiersRecherche = [];
        $dossiers = $em->getRepository(DossierAgrement::class)->findBy(['utilisateur'=> $this->getUser()], ['id' => 'DESC'], 4);

        $method = $request->isMethod('POST');
        $term = $request->get('term');
        if($method){
            $dossiersRecherche = $em->getRepository(DossierAgrement::class)->findByNom($term);
        }


        return $this->render('main/index.html.twig', [
            'dossiers' => $dossiers,
            'method' => $method,
            'term' => $term,
            'dossiersRecherche' => $dossiersRecherche,
        ]);
    }


    #[Route('/mes-dossiers', name: 'app_mes_dossiers')]
    public function mesDossiers(Request $request, PaginatorInterface $paginator, EntityManagerInterface $em): Response
    {
        $dossiers = $em->getRepository(DossierAgrement::class)->findBy(['utilisateur'=> $this->getUser()], ['id' => 'DESC']);

        $dossiersPaginate = $paginator->paginate(
            $dossiers, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            12 // Nombre de résultats par page
        );

        return $this->render('main/mesDossiers.html.twig', [
            'dossiers' => $dossiersPaginate,
        ]);
    }

    #[Route('/ordre-du-jour', name: 'app_odre_du_jour')]
    public function ordreDuJour(Request $request, PaginatorInterface $paginator, EntityManagerInterface $em): Response
    {
        $dossiers = $em->getRepository(DossierAgrement::class)->findBy([], ['id' => 'DESC']);

        $dossiersPaginate = $paginator->paginate(
            $dossiers,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('main/ordreDuJour.html.twig', [
            'dossiers' => $dossiersPaginate,
        ]);
    }

    #[Route('/ordre-du-jour/generer-compte-rendu', name: 'app_odre_du_jour_generer', methods: ['POST'])]
    public function genererCompteRendu(Request $request, DossierAgrementRepository $repository, GestionController $gestionController): Response
    {
        $selectedIds = $request->request->all('selected_dossiers');

        if (empty($selectedIds)) {
            $this->addFlash('warning', 'Veuillez sélectionner au moins un dossier.');
            return $this->redirectToRoute('app_odre_du_jour');
        }

        $selectedModels = $repository->findBy(['id' => $selectedIds]);

        return $gestionController->generateOrdreDuJour($selectedModels);
    }
}
