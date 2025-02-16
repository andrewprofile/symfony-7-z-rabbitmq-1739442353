<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\CustomerImportType;
use App\Message\Command\ImportCustomer;
use App\Service\FileUploaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

class CustomerImportController extends AbstractController
{
    #[Route('/', name: 'customer_import_index', methods: ['GET'])]
    public function index(): Response
    {
        $form = $this->createForm(CustomerImportType::class, null, [
            'action' => $this->generateUrl('customer_import_upload'),
            'method' => 'POST',
        ]);

        return $this->render('customer_import/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/upload', name: 'customer_import_upload', methods: ['POST'])]
    public function upload(Request $request, FileUploaderService $fileUploaderService): Response
    {
        $form = $this->createForm(CustomerImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csv_file')->getData();
            if ($csvFile) {
                $csvFileName = $fileUploaderService->upload($csvFile);

                return $this->redirectToRoute('customer_import_import', ['fileName' => $csvFileName]);
            }
        }

        return $this->redirectToRoute('customer_import_index');
    }

    #[Route('/import/{fileName}', name: 'customer_import_import', methods: ['GET'])]
    public function import(string $fileName, #[Autowire('%csv_directory%')] string $targetDirectory, MessageBusInterface $commandBus): Response
    {
        $commandBus->dispatch(new ImportCustomer($targetDirectory, $fileName));

        return $this->render('customer_import/import.html.twig', [
            'fileName' => $fileName,
        ]);
    }

    #[Route('/import/progress/{fileName}', name: 'customer_import_progress', methods: ['GET'])]
    public function progress(string $fileName, CacheInterface $cache): Response
    {
        $progress = $cache->getItem('progress_'.$fileName);

        return $this->json([
            'progress' => $progress->get() ?? 0,
        ]);
    }

    #[Route('/import/errors/{fileName}', name: 'customer_import_errors', methods: ['GET'])]
    public function errors(string $fileName, CacheInterface $cache): Response
    {
        $progress = $cache->getItem('errors_'.$fileName);

        return $this->json([
            'errors' => $progress->get() ?? 0,
        ]);
    }
}
