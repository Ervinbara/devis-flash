<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'legal_mentions')]
    public function mentions(): Response
    {
        return $this->render('legal/mentions.html.twig');
    }

    #[Route('/confidentialite', name: 'legal_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/cgv', name: 'legal_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }
}