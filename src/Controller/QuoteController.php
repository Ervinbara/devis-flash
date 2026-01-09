<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Form\QuoteType;
use App\Service\PdfGenerator;
use App\Service\QuoteLimiter;
use Doctrine\ORM\EntityManagerInterface;
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
        QuoteLimiter $quoteLimiter,
        EntityManagerInterface $entityManager
    ): Response {
        // Créer une quote avec un item par défaut
        $quote = new Quote();
        $quote->addItem(new QuoteItem());

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ===== DÉSACTIVÉ POUR LES TESTS =====
            // Vérifier la limite gratuite
            // if (!$quoteLimiter->canGenerate()) {
            //     $this->addFlash('error', 'Limite gratuite atteinte ! Passez au plan Pro pour continuer.');
            //     return $this->redirectToRoute('pricing');
            // }
            // ===== FIN DÉSACTIVATION =====

            // Gérer l'upload du logo
            $logoFile = $form->get('companyLogo')->getData();
            if ($logoFile) {
                // Générer un nom unique
                $newFilename = uniqid('logo_') . '.' . $logoFile->guessExtension();

                // Créer le dossier uploads si nécessaire
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/var/tmp/logos';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0755, true);
                }

                try {
                    // Déplacer le fichier
                    $logoFile->move($uploadsDirectory, $newFilename);

                    // Stocker le chemin dans l'entité
                    $quote->setCompanyLogo($uploadsDirectory . '/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                }
            }

            // Récupérer le template choisi
            $template = $form->get('pdfTemplate')->getData() ?? 'modern';
            $quote->setPdfTemplate($template);

            // Calculer les totaux
            $quote->calculateTotals();

            // Générer le numéro de devis
            if (!$quote->getQuoteNumber()) {
                $quote->setQuoteNumber($quote->generateQuoteNumber());
            }

            // Si l'utilisateur est connecté, sauvegarder en BDD
            if ($this->getUser()) {
                $quote->setUser($this->getUser());
                $entityManager->persist($quote);
                $entityManager->flush();

                $this->addFlash('success', 'Devis créé et sauvegardé dans votre historique ! 🎉');
            }

            // Déterminer si Pro (sans watermark)
            $isPro = $this->getUser() && $this->getUser()->isPro();

            // Générer le PDF avec le template
            $pdfPath = $pdfGenerator->generate($quote, $isPro, $template);

            // ===== DÉSACTIVÉ POUR LES TESTS =====
            // Incrémenter le compteur
            // $quoteLimiter->increment();
            // ===== FIN DÉSACTIVATION =====

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