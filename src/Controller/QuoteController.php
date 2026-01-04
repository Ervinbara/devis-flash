<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Form\QuoteType;
use App\Service\PdfGenerator;
use App\Service\QuoteLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class QuoteController extends AbstractController
{
    #[Route('/quote/new', name: 'quote_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        PdfGenerator $pdfGenerator,
        QuoteLimiter $quoteLimiter
    ): Response {
        // Créer une quote avec un item par défaut
        $quote = new Quote();
        $quote->addItem(new QuoteItem());

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier la limite gratuite
            if (!$quoteLimiter->canGenerate()) {
                $this->addFlash('error', 'Limite gratuite atteinte ! Passez au plan Pro pour continuer.');
                return $this->redirectToRoute('pricing');
            }

            // Générer le PDF
            $pdfPath = $pdfGenerator->generate($quote, false); // false = version gratuite

            // Incrémenter le compteur
            $quoteLimiter->increment();

            // Télécharger le PDF
            $response = new BinaryFileResponse($pdfPath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'devis_' . $quote->getQuoteNumber() . '.pdf'
            );

            // Supprimer le fichier après envoi
            $response->deleteFileAfterSend(true);

            return $response;
        }

        return $this->render('quote/new.html.twig', [
            'form' => $form->createView(),
            'remaining' => $quoteLimiter->getRemainingQuotes(),
            'limit' => $quoteLimiter->getFreeLimit(),
        ]);
    }
}
