<?php

namespace App\Controller\Admin;

use App\Entity\Upload;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $em = $this->entityManager;

        // Fetch user data grouped by day
        $userData = $em->getRepository(User::class)->createQueryBuilder('u')
            ->select('DATE_FORMAT(u.createdAt, \'%Y-%m-%d\') AS date, COUNT(u.id) AS count')
            ->groupBy('date')
            ->getQuery()
            ->getResult();

        // Format user data dates
        $formattedUserData = array_map(function($data) {
            return [
                'date' => $data['date'],
                'count' => $data['count']
            ];
        }, $userData);

        // Fetch upload data grouped by day
        $uploadData = $em->getRepository(Upload::class)->createQueryBuilder('u')
            ->select('DATE_FORMAT(u.createdAt, \'%Y-%m-%d\') AS date, COUNT(u.id) AS count')
            ->groupBy('date')
            ->getQuery()
            ->getResult();

        // Format upload data dates
        $formattedUploadData = array_map(function($data) {
            return [
                'date' => $data['date'],
                'count' => $data['count']
            ];
        }, $uploadData);

        return $this->render('admin/dashboard.html.twig', [
            'userData' => json_encode($formattedUserData),
            'uploadData' => json_encode($formattedUploadData),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('FileHost');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-person', User::class);
        yield MenuItem::linkToCrud('Uploads', 'fas fa-arrow-up', Upload::class);
    }
}
