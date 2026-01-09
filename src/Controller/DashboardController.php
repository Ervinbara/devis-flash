<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Form\QuoteType;
use App\Repository\QuoteRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(QuoteRepository $quoteRepository): Response
    {
        $user = $this->getUser();

        // Récupérer tous les devis de l'utilisateur
        $quotes = $quoteRepository->findByUserOrderedByDate($user);

        // Statistiques
        $totalQuotes = $quoteRepository->countByUser($user);
        $totalHt = $quoteRepository->getTotalHtByUser($user);

        return $this->render('dashboard/index.html.twig', [
            'quotes' => $quotes,
            'stats' => [
                'totalQuotes' => $totalQuotes,
                'totalHt' => $totalHt,
            ],
        ]);
    }

    #[Route('/dashboard/quote/{id}/download', name: 'dashboard_quote_download')]
    public function downloadQuote(
        int $id,
        QuoteRepository $quoteRepository,
        PdfGenerator $pdfGenerator
    ): Response {
        $quote = $quoteRepository->find($id);

        if (!$quote) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        // Vérifier que le devis appartient à l'utilisateur
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Générer le PDF
        $isPro = $quote->getUser()->isPro();
        $template = $quote->getPdfTemplate() ?? 'modern';
        $pdfPath = $pdfGenerator->generate($quote, $isPro, $template);

        // Télécharger
        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'devis_' . $quote->getQuoteNumber() . '.pdf'
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/dashboard/quote/{id}/duplicate', name: 'dashboard_quote_duplicate')]
    public function duplicateQuote(
        int $id,
        QuoteRepository $quoteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $originalQuote = $quoteRepository->find($id);

        if (!$originalQuote) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        // Vérifier que le devis appartient à l'utilisateur
        if ($originalQuote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Créer une copie
        $newQuote = clone $originalQuote;
        $newQuote->setQuoteNumber(null); // Générer un nouveau numéro
        $newQuote->setQuoteDate(new \DateTime());
        $newQuote->setQuoteValidUntil((new \DateTime())->modify('+30 days'));
        $newQuote->setCreatedAt(new \DateTimeImmutable());
        $newQuote->setUpdatedAt(null);

        // Copier les items
        foreach ($originalQuote->getItems() as $item) {
            $newItem = clone $item;
            $newQuote->addItem($newItem);
        }

        // Recalculer les totaux
        $newQuote->calculateTotals();

        // Sauvegarder
        $entityManager->persist($newQuote);
        $entityManager->flush();

        $this->addFlash('success', 'Le devis a été dupliqué avec succès !');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/dashboard/quote/{id}/delete', name: 'dashboard_quote_delete', methods: ['POST'])]
    public function deleteQuote(
        int $id,
        QuoteRepository $quoteRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $quote = $quoteRepository->find($id);

        if (!$quote) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        // Vérifier que le devis appartient à l'utilisateur
        if ($quote->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete' . $quote->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quote);
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/dashboard/quote/{id}/email-data', name: 'dashboard_quote_email_data', methods: ['GET'])]
    public function getEmailData(int $id, QuoteRepository $quoteRepository): JsonResponse
    {
        $quote = $quoteRepository->find($id);

        if (!$quote) {
            return new JsonResponse(['error' => 'Devis non trouvé'], 404);
        }

        // Vérifier que le devis appartient à l'utilisateur
        if ($quote->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Préparer les données pour l'email
        $data = [
            'clientEmail' => $quote->getClientEmail() ?? '',
            'clientName' => $quote->getClientName() ?? 'Client',
            'quoteNumber' => $quote->getQuoteNumber() ?? 'N/A',
            'companyName' => $quote->getCompanyName() ?? 'Votre entreprise',
            'downloadUrl' => $this->generateUrl('dashboard_quote_download', ['id' => $quote->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new JsonResponse($data);
    }
}