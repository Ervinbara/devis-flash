<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use TCPDF;

class PdfGenerator
{
    public function __construct(
        private readonly string $tmpDir,
        private readonly bool $watermarkEnabled,
        private readonly string $watermarkText
    ) {
    }

    public function generate(Quote $quote, bool $isPro = false): string
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
        $pdf->SetAutoPageBreak(true, 15);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Police
        $pdf->SetFont('helvetica', '', 10);
        
        // Contenu
        $this->addHeader($pdf, $quote);
        $this->addCompanyAndClient($pdf, $quote);
        $this->addDescription($pdf, $quote);
        $this->addItemsTable($pdf, $quote);
        $this->addTotals($pdf, $quote);
        $this->addPaymentTerms($pdf, $quote);
        
        // Watermark si gratuit
        if (!$isPro && $this->watermarkEnabled) {
            $this->addWatermark($pdf);
        }
        
        // Sauvegarder
        $filename = $this->tmpDir . '/' . uniqid('quote_') . '.pdf';
        $pdf->Output($filename, 'F');
        
        return $filename;
    }

    private function addHeader(TCPDF $pdf, Quote $quote): void
    {
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->Cell(0, 10, 'DEVIS', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Ln(2);
        
        $pdf->Cell(95, 6, 'N° : ' . $quote->getQuoteNumber(), 0, 0, 'L');
        $pdf->Cell(95, 6, 'Date : ' . $quote->getQuoteDate()->format('d/m/Y'), 0, 1, 'R');
        
        if ($quote->getQuoteValidUntil()) {
            $pdf->Cell(0, 6, 'Valable jusqu\'au : ' . $quote->getQuoteValidUntil()->format('d/m/Y'), 0, 1, 'R');
        }
        
        $pdf->Ln(5);
    }

    private function addCompanyAndClient(TCPDF $pdf, Quote $quote): void
    {
        $pdf->SetFont('helvetica', 'B', 11);
        
        // Émetteur (gauche)
        $y = $pdf->GetY();
        $pdf->Cell(95, 6, 'Émetteur', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(95, 5, $this->formatCompany($quote), 1, 'L', false, 0, '', '', true, 0, false, true, 0, 'T');
        
        // Client (droite)
        $pdf->SetXY(105, $y);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(95, 6, 'Client', 0, 1, 'L');
        $pdf->SetX(105);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(95, 5, $this->formatClient($quote), 1, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
        
        $pdf->Ln(5);
    }

    private function formatCompany(Quote $quote): string
    {
        $lines = [
            $quote->getCompanyName(),
            $quote->getCompanyContact(),
            $quote->getCompanyAddress(),
            $quote->getCompanyEmail(),
        ];
        
        if ($quote->getCompanyPhone()) {
            $lines[] = 'Tél : ' . $quote->getCompanyPhone();
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
            $lines[] = $quote->getClientEmail();
        }
        
        return implode("\n", array_filter($lines));
    }

    private function addDescription(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getQuoteDescription()) {
            return;
        }
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Objet', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, $quote->getQuoteDescription(), 0, 'L');
        $pdf->Ln(3);
    }

    private function addItemsTable(TCPDF $pdf, Quote $quote): void
    {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(230, 230, 230);
        
        // En-têtes
        $pdf->Cell(90, 7, 'Désignation', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Quantité', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Prix unit. HT', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'Total HT', 1, 1, 'R', true);
        
        // Lignes
        $pdf->SetFont('helvetica', '', 10);
        foreach ($quote->getItems() as $item) {
            $pdf->Cell(90, 7, $item->getLabel(), 1, 0, 'L');
            $pdf->Cell(25, 7, (string)$item->getQuantity(), 1, 0, 'C');
            $pdf->Cell(35, 7, number_format($item->getUnitPriceHt(), 2, ',', ' ') . ' €', 1, 0, 'R');
            $pdf->Cell(30, 7, number_format($item->getTotalHt(), 2, ',', ' ') . ' €', 1, 1, 'R');
        }
    }

    private function addTotals(TCPDF $pdf, Quote $quote): void
    {
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Total HT
        $pdf->Cell(150, 6, 'Total HT', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($quote->getTotalHt(), 2, ',', ' ') . ' €', 1, 1, 'R');
        
        // TVA
        $pdf->Cell(150, 6, 'TVA (' . $quote->getVatRate() . '%)', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($quote->getVatAmount(), 2, ',', ' ') . ' €', 1, 1, 'R');
        
        // Total TTC
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(150, 8, 'Total TTC', 0, 0, 'R');
        $pdf->Cell(30, 8, number_format($quote->getTotalTtc(), 2, ',', ' ') . ' €', 1, 1, 'R', true);
    }

    private function addPaymentTerms(TCPDF $pdf, Quote $quote): void
    {
        if (!$quote->getPaymentTerms()) {
            return;
        }
        
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Conditions de paiement', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 4, $quote->getPaymentTerms(), 0, 'L');
    }

    private function addWatermark(TCPDF $pdf): void
    {
        $pdf->SetAlpha(0.3);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(150, 150, 150);
        
        // Position en bas à droite
        $pdf->SetXY(15, 280);
        $pdf->Cell(0, 5, $this->watermarkText, 0, 0, 'R');
        
        // Réinitialiser
        $pdf->SetAlpha(1);
        $pdf->SetTextColor(0, 0, 0);
    }
}
