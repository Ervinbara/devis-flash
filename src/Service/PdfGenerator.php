<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use TCPDF;

class PdfGenerator
{
    // Couleurs des templates
    private const TEMPLATES = [
        'modern' => [
            'primary' => [99, 102, 241],      // Violet (#6366f1)
            'secondary' => [248, 250, 252],   // Gris clair
            'accent' => [99, 102, 241],
        ],
        'corporate' => [
            'primary' => [30, 58, 138],       // Bleu marine
            'secondary' => [243, 244, 246],   
            'accent' => [59, 130, 246],
        ],
        'creative' => [
            'primary' => [249, 115, 22],      // Orange
            'secondary' => [254, 252, 232],   
            'accent' => [234, 88, 12],
        ],
    ];

    public function __construct(
        private readonly string $tmpDir,
        private readonly bool $watermarkEnabled,
        private readonly string $watermarkText
    ) {
    }

    public function generate(Quote $quote, bool $isPro = false, string $template = 'modern'): string
    {
        // Créer le répertoire tmp si nécessaire
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }

        // Générer le numéro si vide
        if (!$quote->getQuoteNumber()) {
            $quote->setQuoteNumber($quote->generateQuoteNumber());
        }

        // Initialiser TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Métadonnées
        $pdf->SetCreator('DevisFlash');
        $pdf->SetAuthor($quote->getCompanyName());
        $pdf->SetTitle('Devis ' . $quote->getQuoteNumber());
        
        // Supprimer header/footer par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Police par défaut
        $pdf->SetFont('helvetica', '', 10);
        
        // Récupérer les couleurs du template
        $colors = self::TEMPLATES[$template] ?? self::TEMPLATES['modern'];
        
        // Contenu avec le template
        $this->addModernHeader($pdf, $quote, $colors);
        $this->addCompanyAndClientModern($pdf, $quote, $colors);
        $this->addDescription($pdf, $quote, $colors);
        $this->addModernItemsTable($pdf, $quote, $colors);
        $this->addModernTotals($pdf, $quote, $colors);
        $this->addPaymentTerms($pdf, $quote);
        
        // Footer avec watermark discret
        $this->addModernFooter($pdf, $isPro, $colors);
        
        // Sauvegarder
        $filename = $this->tmpDir . '/devis_' . $quote->getQuoteNumber() . '.pdf';
        $pdf->Output($filename, 'F');
        
        return $filename;
    }

    private function addModernHeader(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // Rectangle de fond coloré
        $pdf->SetFillColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Rect(0, 0, 210, 40, 'F');
        
        // Logo à gauche si présent
        if ($quote->getCompanyLogo() && file_exists($quote->getCompanyLogo())) {
            try {
                // Afficher le logo (max 30mm de largeur)
                $pdf->Image(
                    $quote->getCompanyLogo(),
                    15,      // X
                    8,       // Y
                    25,      // Largeur max
                    0,       // Hauteur auto
                    '',      // Type auto-détecté
                    '',
                    '',
                    true,    // Resize
                    300,     // DPI
                    '',
                    false,
                    false,
                    0,
                    false,
                    false,
                    false
                );
            } catch (\Exception $e) {
                // Si erreur, afficher le texte à la place
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 32);
                $pdf->SetXY(15, 10);
                $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');
            }
        } else {
            // Pas de logo : afficher "DEVIS" en grand à gauche
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 32);
            $pdf->SetXY(15, 10);
            $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');
        }
        
        // Informations à droite (toujours présentes)
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 11);
        
        $pdf->SetXY(120, 12);
        $pdf->Cell(70, 5, 'N° ' . $quote->getQuoteNumber(), 0, 1, 'R');
        
        $pdf->SetXY(120, 18);
        $pdf->Cell(70, 5, 'Date : ' . $quote->getQuoteDate()->format('d/m/Y'), 0, 1, 'R');
        
        if ($quote->getQuoteValidUntil()) {
            $pdf->SetXY(120, 24);
            $pdf->Cell(70, 5, 'Valable jusqu\'au : ' . $quote->getQuoteValidUntil()->format('d/m/Y'), 0, 1, 'R');
        }
        
        // Réinitialiser couleur texte
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(45);
    }

    private function addCompanyAndClientModern(TCPDF $pdf, Quote $quote, array $colors): void
    {
        $startY = $pdf->GetY();
        
        // Bloc Émetteur (fond coloré)
        $pdf->SetFillColor($colors['secondary'][0], $colors['secondary'][1], $colors['secondary'][2]);
        $pdf->Rect(15, $startY, 85, 45, 'F');
        
        $pdf->SetXY(15, $startY);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(85, 6, 'ÉMETTEUR', 0, 1, 'L');
        
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(85, 4, $this->formatCompany($quote), 0, 'L', false, 0);
        
        // Bloc Client (fond blanc)
        $pdf->SetXY(105, $startY);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(85, 6, 'CLIENT', 0, 1, 'L');
        
        $pdf->SetXY(105, $startY + 6);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(85, 4, $this->formatClient($quote), 0, 'L', false, 1);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($startY + 50);
    }

    private function formatCompany(Quote $quote): string
    {
        $lines = [
            $quote->getCompanyName(),
            $quote->getCompanyContact(),
            $quote->getCompanyAddress(),
            '✉ ' . $quote->getCompanyEmail(),
        ];
        
        if ($quote->getCompanyPhone()) {
            $lines[] = '☎ ' . $quote->getCompanyPhone();
        }
        if ($quote->getCompanySiret()) {
            $lines[] = 'SIRET : ' . $quote->getCompanySiret();
        }
        
        return implode("\n", array_filter($lines));
    }

    private function formatClient(Quote $quote): string
    {
        $lines = [
            $quote->getClientName(),
        ];
        
        if ($quote->getClientCompany()) {
            $lines[] = $quote->getClientCompany();
        }
        
        $lines[] = $quote->getClientAddress();
        
        if ($quote->getClientEmail()) {
            $lines[] = '✉ ' . $quote->getClientEmail();
        }
        
        return implode("\n", array_filter($lines));
    }

    private function addDescription(TCPDF $pdf, Quote $quote, array $colors): void
    {
        if (!$quote->getQuoteDescription()) {
            return;
        }
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(0, 6, 'OBJET', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 4, $quote->getQuoteDescription(), 0, 'L');
        $pdf->Ln(3);
    }

    private function addModernItemsTable(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // En-tête du tableau (fond coloré, texte blanc)
        $pdf->SetFillColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(90, 8, 'Désignation', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Quantité', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Prix unit. HT', 1, 0, 'R', true);
        $pdf->Cell(30, 8, 'Total HT', 1, 1, 'R', true);
        
        // Lignes alternées (gris clair / blanc)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        $alternate = false;
        foreach ($quote->getItems() as $item) {
            if ($alternate) {
                $pdf->SetFillColor(249, 250, 251);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            $pdf->Cell(90, 7, $item->getLabel(), 1, 0, 'L', true);
            $pdf->Cell(25, 7, (string)$item->getQuantity(), 1, 0, 'C', true);
            $pdf->Cell(35, 7, number_format($item->getUnitPriceHt(), 2, ',', ' ') . ' €', 1, 0, 'R', true);
            $pdf->Cell(30, 7, number_format($item->getTotalHt(), 2, ',', ' ') . ' €', 1, 1, 'R', true);
            
            $alternate = !$alternate;
        }
    }

    private function addModernTotals(TCPDF $pdf, Quote $quote, array $colors): void
    {
        $pdf->Ln(5);
        
        // Encadré pour les totaux
        $startY = $pdf->GetY();
        
        // Fond gris clair pour l'encadré
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(125, $startY, 65, 28, 'F');
        
        $pdf->SetXY(125, $startY + 2);
        $pdf->SetFont('helvetica', '', 10);
        
        // Total HT
        $pdf->Cell(35, 7, 'Total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->getTotalHt(), 2, ',', ' ') . ' €', 0, 1, 'R');
        
        // TVA
        $pdf->SetX(125);
        $pdf->Cell(35, 7, 'TVA (' . number_format($quote->getVatRate(), 1) . '%)', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->getVatAmount(), 2, ',', ' ') . ' €', 0, 1, 'R');
        
        // Ligne de séparation
        $pdf->SetLineWidth(0.5);
        $pdf->SetDrawColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Line(127, $pdf->GetY() + 1, 188, $pdf->GetY() + 1);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        
        $pdf->Ln(3);
        
        // Total TTC (en gras, plus grand)
        $pdf->SetX(125);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(35, 9, 'Total TTC', 0, 0, 'L');
        $pdf->Cell(30, 9, number_format($quote->getTotalTtc(), 2, ',', ' ') . ' €', 0, 1, 'R');
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
    }

    private function addPaymentTerms(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getPaymentTerms()) {
            return;
        }
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'Conditions de paiement', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(0, 4, $quote->getPaymentTerms(), 0, 'L');
    }

    private function addModernFooter(TCPDF $pdf, bool $isPro, array $colors): void
    {
        // Positionnement en bas de page
        $pdf->SetY(-20);
        
        // Ligne de séparation
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(0, 0, 0);
        
        $pdf->Ln(3);
        
        // Watermark discret si gratuit
        if (!$isPro && $this->watermarkEnabled) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 4, $this->watermarkText, 0, 1, 'C');
        } else {
            $pdf->Ln(4);
        }
        
        // Powered by TCPDF (très discret)
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->Cell(0, 3, 'Généré avec TCPDF', 0, 0, 'R');
        
        $pdf->SetTextColor(0, 0, 0);
    }
}