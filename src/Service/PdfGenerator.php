<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use TCPDF;

class PdfGenerator
{
    // Couleurs et configuration des templates
    private const TEMPLATES = [
        'modern' => [
            'primary' => [99, 102, 241],      // Violet (#6366f1)
            'secondary' => [248, 250, 252],   // Gris clair
            'accent' => [99, 102, 241],
            'name' => '🎨 Modern',
        ],
        'corporate' => [
            'primary' => [30, 58, 138],       // Bleu marine
            'secondary' => [243, 244, 246],
            'accent' => [59, 130, 246],
            'name' => '💼 Corporate',
        ],
        'creative' => [
            'primary' => [249, 115, 22],      // Orange
            'secondary' => [254, 252, 232],
            'accent' => [234, 88, 12],
            'name' => '🚀 Creative',
        ],
        'classic' => [
            'primary' => [31, 41, 55],        // Gris anthracite (#1f2937)
            'secondary' => [249, 250, 251],   // Gris très clair
            'accent' => [75, 85, 99],         // Gris moyen
            'name' => '📄 Classic',
        ],
        'elegant' => [
            'primary' => [127, 29, 29],       // Bordeaux (#7f1d1d)
            'secondary' => [254, 252, 232],   // Beige doré
            'accent' => [180, 83, 9],         // Or/bronze
            'name' => '✨ Elegant',
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

        // Générer selon le template choisi
        match ($template) {
            'classic' => $this->generateClassicTemplate($pdf, $quote, $colors, $isPro),
            'elegant' => $this->generateElegantTemplate($pdf, $quote, $colors, $isPro),
            default => $this->generateModernTemplate($pdf, $quote, $colors, $isPro)
        };

        // Sauvegarder
        $filename = $this->tmpDir . '/devis_' . $quote->getQuoteNumber() . '.pdf';
        $pdf->Output($filename, 'F');

        return $filename;
    }

    // ============================================================================
    // TEMPLATE MODERN / CORPORATE / CREATIVE (style existant avec header coloré)
    // ============================================================================

    private function generateModernTemplate(TCPDF $pdf, Quote $quote, array $colors, bool $isPro): void
    {
        $this->addModernHeader($pdf, $quote, $colors);
        $this->addCompanyAndClientModern($pdf, $quote, $colors);
        $this->addDescription($pdf, $quote, $colors);
        $this->addModernItemsTable($pdf, $quote, $colors);
        $this->addModernTotals($pdf, $quote, $colors);
        $this->addPaymentTerms($pdf, $quote);
        $this->addModernFooter($pdf, $isPro, $colors);
    }

    private function addModernHeader(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // Rectangle de fond coloré
        $pdf->SetFillColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Rect(0, 0, 210, 40, 'F');

        // Logo à gauche si présent
        if ($quote->getCompanyLogo() && file_exists($quote->getCompanyLogo())) {
            try {
                $pdf->Image($quote->getCompanyLogo(), 15, 8, 25, 0, '', '', '', true, 300);
            } catch (\Exception $e) {
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 32);
                $pdf->SetXY(15, 10);
                $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');
            }
        } else {
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 32);
            $pdf->SetXY(15, 10);
            $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');
        }

        // Informations à droite
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

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(45);
    }

    private function addCompanyAndClientModern(TCPDF $pdf, Quote $quote, array $colors): void
    {
        $startY = $pdf->GetY();

        // Bloc Émetteur
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

        // Bloc Client
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

    private function addModernItemsTable(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // En-tête du tableau
        $pdf->SetFillColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(90, 8, 'Désignation', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Quantité', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Prix unit. HT', 1, 0, 'R', true);
        $pdf->Cell(30, 8, 'Total HT', 1, 1, 'R', true);

        // Lignes alternées
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);

        $alternate = false;
        foreach ($quote->getItems() as $item) {
            $pdf->SetFillColor($alternate ? 249 : 255, $alternate ? 250 : 255, $alternate ? 251 : 255);

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
        $startY = $pdf->GetY();

        // Fond gris clair
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(125, $startY, 65, 28, 'F');

        $pdf->SetXY(125, $startY + 2);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Cell(35, 7, 'Total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->getTotalHt(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->SetX(125);
        $pdf->Cell(35, 7, 'TVA (' . number_format($quote->getVatRate(), 1) . '%)', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->getVatAmount(), 2, ',', ' ') . ' €', 0, 1, 'R');

        // Ligne de séparation
        $pdf->SetLineWidth(0.5);
        $pdf->SetDrawColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Line(127, $pdf->GetY() + 1, 188, $pdf->GetY() + 1);

        $pdf->Ln(3);
        $pdf->SetX(125);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(35, 9, 'Total TTC', 0, 0, 'L');
        $pdf->Cell(30, 9, number_format($quote->getTotalTtc(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
    }

    private function addModernFooter(TCPDF $pdf, bool $isPro, array $colors): void
    {
        $pdf->SetY(-20);
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

        $pdf->Ln(3);

        if (!$isPro && $this->watermarkEnabled) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 4, $this->watermarkText, 0, 1, 'C');
        }
    }

    // ============================================================================
    // TEMPLATE CLASSIC - Style traditionnel avec bordures noires
    // ============================================================================

    private function generateClassicTemplate(TCPDF $pdf, Quote $quote, array $colors, bool $isPro): void
    {
        $this->addClassicHeader($pdf, $quote);
        $this->addClassicCompanyAndClient($pdf, $quote);
        $this->addClassicDescription($pdf, $quote);
        $this->addClassicItemsTable($pdf, $quote);
        $this->addClassicTotals($pdf, $quote);
        $this->addClassicPaymentTerms($pdf, $quote);
        $this->addClassicFooter($pdf, $isPro);
    }

    private function addClassicHeader(TCPDF $pdf, Quote $quote): void
    {
        // Bordure du header
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(15, 15, 180, 35, 'D');

        // "DEVIS" en grand à gauche
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetXY(20, 22);
        $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');

        // Logo si présent (petit, dans le coin)
        if ($quote->getCompanyLogo() && file_exists($quote->getCompanyLogo())) {
            try {
                $pdf->Image($quote->getCompanyLogo(), 20, 35, 15, 0, '', '', '', true, 300);
            } catch (\Exception $e) {
                // Ignore si erreur
            }
        }

        // Informations à droite
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(120, 20);
        $pdf->Cell(70, 5, 'N° ' . $quote->getQuoteNumber(), 0, 1, 'R');
        $pdf->SetXY(120, 26);
        $pdf->Cell(70, 5, 'Date : ' . $quote->getQuoteDate()->format('d/m/Y'), 0, 1, 'R');
        if ($quote->getQuoteValidUntil()) {
            $pdf->SetXY(120, 32);
            $pdf->Cell(70, 5, 'Valable jusqu\'au : ' . $quote->getQuoteValidUntil()->format('d/m/Y'), 0, 1, 'R');
        }

        $pdf->SetY(55);
    }

    private function addClassicCompanyAndClient(TCPDF $pdf, Quote $quote): void
    {
        $startY = $pdf->GetY();

        // Émetteur avec bordure
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(15, $startY, 85, 40, 'D');
        $pdf->SetXY(18, $startY + 2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'ÉMETTEUR', 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(80, 4, $this->formatCompany($quote), 0, 'L');

        // Client avec bordure
        $pdf->Rect(105, $startY, 85, 40, 'D');
        $pdf->SetXY(108, $startY + 2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'CLIENT', 0, 1, 'L');

        $pdf->SetXY(108, $startY + 7);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(80, 4, $this->formatClient($quote), 0, 'L');

        $pdf->SetY($startY + 45);
    }

    private function addClassicDescription(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getQuoteDescription()) {
            return;
        }

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'OBJET DU DEVIS', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 4, $quote->getQuoteDescription(), 0, 'L');
        $pdf->Ln(3);
    }

    private function addClassicItemsTable(TCPDF $pdf, Quote $quote): void
    {
        // En-tête gris
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);

        $pdf->Cell(90, 7, 'Désignation', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Qté', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Prix unit. HT', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'Total HT', 1, 1, 'R', true);

        // Lignes
        $pdf->SetFont('helvetica', '', 9);
        foreach ($quote->getItems() as $item) {
            $pdf->Cell(90, 6, $item->getLabel(), 1, 0, 'L');
            $pdf->Cell(25, 6, (string)$item->getQuantity(), 1, 0, 'C');
            $pdf->Cell(35, 6, number_format($item->getUnitPriceHt(), 2, ',', ' ') . ' €', 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($item->getTotalHt(), 2, ',', ' ') . ' €', 1, 1, 'R');
        }
    }

    private function addClassicTotals(TCPDF $pdf, Quote $quote): void
    {
        $pdf->Ln(5);
        $startY = $pdf->GetY();

        // Rectangle pour les totaux
        $pdf->Rect(125, $startY, 65, 25, 'D');

        $pdf->SetXY(128, $startY + 3);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 6, 'Total HT', 0, 0, 'L');
        $pdf->Cell(30, 6, number_format($quote->getTotalHt(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->SetX(128);
        $pdf->Cell(30, 6, 'TVA (' . number_format($quote->getVatRate(), 1) . '%)', 0, 0, 'L');
        $pdf->Cell(30, 6, number_format($quote->getVatAmount(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->Line(128, $pdf->GetY() + 1, 188, $pdf->GetY() + 1);

        $pdf->Ln(2);
        $pdf->SetX(128);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(30, 6, 'Total TTC', 0, 0, 'L');
        $pdf->Cell(30, 6, number_format($quote->getTotalTtc(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->Ln(5);
    }

    private function addClassicPaymentTerms(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getPaymentTerms()) {
            return;
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'Conditions de règlement', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(0, 4, $quote->getPaymentTerms(), 0, 'L');
    }

    private function addClassicFooter(TCPDF $pdf, bool $isPro): void
    {
        $pdf->SetY(-20);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(3);

        if (!$isPro && $this->watermarkEnabled) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(0, 4, $this->watermarkText, 0, 1, 'C');
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    // ============================================================================
    // TEMPLATE ELEGANT - Style raffiné avec accents dorés/bordeaux
    // ============================================================================

    private function generateElegantTemplate(TCPDF $pdf, Quote $quote, array $colors, bool $isPro): void
    {
        $this->addElegantHeader($pdf, $quote, $colors);
        $this->addElegantCompanyAndClient($pdf, $quote, $colors);
        $this->addElegantDescription($pdf, $quote, $colors);
        $this->addElegantItemsTable($pdf, $quote, $colors);
        $this->addElegantTotals($pdf, $quote, $colors);
        $this->addElegantPaymentTerms($pdf, $quote);
        $this->addElegantFooter($pdf, $isPro);
    }

    private function addElegantHeader(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // Ligne dorée élégante en haut
        $pdf->SetDrawColor($colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, 20, 195, 20);

        // "DEVIS" en bordeaux, élégant
        $pdf->SetFont('times', 'B', 24);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->SetXY(15, 25);
        $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'L');

        // Logo si présent
        if ($quote->getCompanyLogo() && file_exists($quote->getCompanyLogo())) {
            try {
                $pdf->Image($quote->getCompanyLogo(), 170, 22, 20, 0, '', '', '', true, 300);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // Informations en petit à droite
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(120, 38);
        $pdf->Cell(70, 4, 'N° ' . $quote->getQuoteNumber(), 0, 1, 'R');
        $pdf->SetXY(120, 42);
        $pdf->Cell(70, 4, $quote->getQuoteDate()->format('d/m/Y'), 0, 1, 'R');
        if ($quote->getQuoteValidUntil()) {
            $pdf->SetXY(120, 46);
            $pdf->Cell(70, 4, 'Valable jusqu\'au : ' . $quote->getQuoteValidUntil()->format('d/m/Y'), 0, 1, 'R');
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY(55);
    }

    private function addElegantCompanyAndClient(TCPDF $pdf, Quote $quote, array $colors): void
    {
        $startY = $pdf->GetY();

        // Fond beige doux pour l'émetteur
        $pdf->SetFillColor($colors['secondary'][0], $colors['secondary'][1], $colors['secondary'][2]);
        $pdf->Rect(15, $startY, 85, 42, 'F');

        // Bordure dorée
        $pdf->SetDrawColor($colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(15, $startY, 85, 42, 'D');

        $pdf->SetXY(18, $startY + 3);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(0, 5, 'DE', 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(80, 3.5, $this->formatCompany($quote), 0, 'L');

        // Client avec bordure dorée
        $pdf->Rect(105, $startY, 85, 42, 'D');
        $pdf->SetXY(108, $startY + 3);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(0, 5, 'À', 0, 1, 'L');

        $pdf->SetXY(108, $startY + 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(80, 3.5, $this->formatClient($quote), 0, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($startY + 47);
    }

    private function addElegantDescription(TCPDF $pdf, Quote $quote, array $colors): void
    {
        if (!$quote->getQuoteDescription()) {
            return;
        }

        $pdf->SetFont('times', 'I', 10);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->MultiCell(0, 4, '"' . $quote->getQuoteDescription() . '"', 0, 'C');
        $pdf->Ln(3);
    }

    private function addElegantItemsTable(TCPDF $pdf, Quote $quote, array $colors): void
    {
        // En-tête bordeaux avec texte blanc
        $pdf->SetFillColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(90, 7, 'Désignation', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Qté', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Prix unit. HT', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'Total HT', 1, 1, 'R', true);

        // Lignes avec fond beige alternées
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);

        $alternate = false;
        foreach ($quote->getItems() as $item) {
            if ($alternate) {
                $pdf->SetFillColor($colors['secondary'][0], $colors['secondary'][1], $colors['secondary'][2]);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell(90, 6, $item->getLabel(), 1, 0, 'L', true);
            $pdf->Cell(25, 6, (string)$item->getQuantity(), 1, 0, 'C', true);
            $pdf->Cell(35, 6, number_format($item->getUnitPriceHt(), 2, ',', ' ') . ' €', 1, 0, 'R', true);
            $pdf->Cell(30, 6, number_format($item->getTotalHt(), 2, ',', ' ') . ' €', 1, 1, 'R', true);

            $alternate = !$alternate;
        }
    }

    private function addElegantTotals(TCPDF $pdf, Quote $quote, array $colors): void
    {
        $pdf->Ln(5);
        $startY = $pdf->GetY();

        // Fond beige avec bordure dorée
        $pdf->SetFillColor($colors['secondary'][0], $colors['secondary'][1], $colors['secondary'][2]);
        $pdf->Rect(125, $startY, 65, 26, 'F');

        $pdf->SetDrawColor($colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
        $pdf->Rect(125, $startY, 65, 26, 'D');

        $pdf->SetXY(128, $startY + 3);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->Cell(30, 5, 'Total HT', 0, 0, 'L');
        $pdf->Cell(30, 5, number_format($quote->getTotalHt(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->SetX(128);
        $pdf->Cell(30, 5, 'TVA (' . number_format($quote->getVatRate(), 1) . '%)', 0, 0, 'L');
        $pdf->Cell(30, 5, number_format($quote->getVatAmount(), 2, ',', ' ') . ' €', 0, 1, 'R');

        // Ligne dorée
        $pdf->SetDrawColor($colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
        $pdf->Line(128, $pdf->GetY() + 1, 188, $pdf->GetY() + 1);

        $pdf->Ln(2);
        $pdf->SetX(128);
        $pdf->SetFont('times', 'B', 11);
        $pdf->SetTextColor($colors['primary'][0], $colors['primary'][1], $colors['primary'][2]);
        $pdf->Cell(30, 6, 'Total TTC', 0, 0, 'L');
        $pdf->Cell(30, 6, number_format($quote->getTotalTtc(), 2, ',', ' ') . ' €', 0, 1, 'R');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
    }

    private function addElegantPaymentTerms(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getPaymentTerms()) {
            return;
        }

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 4, 'Conditions de règlement', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 3, $quote->getPaymentTerms(), 0, 'L');
    }

    private function addElegantFooter(TCPDF $pdf, bool $isPro): void
    {
        $pdf->SetY(-18);

        // Ligne dorée
        $pdf->SetDrawColor(180, 83, 9);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

        $pdf->Ln(3);

        if (!$isPro && $this->watermarkEnabled) {
            $pdf->SetFont('helvetica', 'I', 7);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 3, $this->watermarkText, 0, 1, 'C');
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    // ============================================================================
    // MÉTHODES UTILITAIRES (communes à tous les templates)
    // ============================================================================

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
        $lines = [$quote->getClientName()];

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
}