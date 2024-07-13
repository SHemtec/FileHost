<?php

namespace App\Controller;

use App\Entity\Link;
use App\Entity\Upload;
use App\Repository\LinkRepository;
use App\Repository\UploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShareFileController extends AbstractController
{
    #[Route('/link/{id}', name: 'app_dl_link')]
    public function share(Upload $upload, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Générer un lien unique (peut être basé sur l'ID de l'upload par exemple)
        $uniqueLink = $this->generateRandomString(48);

        // Créer une nouvelle instance de Link
        $link = new Link();
        $link->setSlug($uniqueLink); // Assurez-vous que vous avez une méthode setUrl() dans votre entité Link

        // Associer le Link à l'Upload
        $upload->setLink($link);

        // Enregistrer l'entité Upload et sa relation Link
        $entityManager->persist($link);
        $entityManager->persist($upload);
        $entityManager->flush();

        //redirige vers app_download avec le slug
        return $this->redirectToRoute('app_download', ['slug' => $uniqueLink]);

    }

    private function generateRandomString($length) : string
    {
        return bin2hex(random_bytes($length));
    }

    #[Route('/download/{slug}', name: 'app_download')]
    public function download(string $slug, LinkRepository $linkRepository): Response
    {
        // Récupérer l'entité Upload associée au Link
        $link = $linkRepository->findOneBy(['slug' => $slug]);
        $upload = $link->getUpload();

        if (!$upload) {
            throw $this->createNotFoundException('Aucun fichier trouvé pour ce lien.');
        }

        return $this->render('share_file/index.html.twig', [
            'upload' => $upload,
        ]);
    }

    #[Route('/download/file/{slug}', name: 'app_dl_file')]
    public function downloadFile(string $slug, LinkRepository $linkRepository): Response
    {
        // Récupérer l'entité Upload associée au Link
        $link = $linkRepository->findOneBy(['slug' => $slug]);
        $upload = $link->getUpload();

        if (!$upload) {
            throw $this->createNotFoundException('Aucun fichier trouvé pour ce lien.');
        }

        // Récupérer le chemin absolu du fichier
        $filePath = $this->getParameter('upload_directory') . '/' . $upload->getFileName();

        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }

        // Créer une réponse pour le fichier
        $response = new Response();
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');
        $response->headers->set('Content-Length', filesize($filePath));
        $response->setContent(file_get_contents($filePath));

        return $response;
    }

}
