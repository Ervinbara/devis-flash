<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class QuoteLimiter
{
    private const COOKIE_NAME = 'df_quota';
    private const COOKIE_DATE_NAME = 'df_quota_date';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int $freeLimit
    ) {
    }

    /**
     * Vérifie si l'utilisateur peut générer un nouveau devis
     */
    public function canGenerate(): bool
    {
        $count = $this->getCurrentCount();
        return $count < $this->freeLimit;
    }

    /**
     * Incrémente le compteur de devis du jour
     */
    public function increment(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $count = $this->getCurrentCount();
        $newCount = $count + 1;
        $today = date('Y-m-d');

        // Calculer l'expiration à minuit (début du jour suivant)
        $midnight = strtotime('tomorrow midnight');

        // Stocker le compteur avec la date du jour
        setcookie(
            self::COOKIE_NAME,
            (string)$newCount,
            $midnight,
            '/',
            '',
            false, // Pas besoin de HTTPS pour le dev
            true   // HttpOnly pour la sécurité
        );

        setcookie(
            self::COOKIE_DATE_NAME,
            $today,
            $midnight,
            '/',
            '',
            false,
            true
        );
    }

    /**
     * Récupère le nombre de devis créés aujourd'hui
     */
    public function getCurrentCount(): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return 0;
        }

        $today = date('Y-m-d');
        $cookieDate = $request->cookies->get(self::COOKIE_DATE_NAME);

        // Si la date du cookie est différente d'aujourd'hui, réinitialiser
        if ($cookieDate !== $today) {
            return 0;
        }

        $cookie = $request->cookies->get(self::COOKIE_NAME);
        return $cookie ? (int)$cookie : 0;
    }

    /**
     * Retourne le nombre de devis restants aujourd'hui
     */
    public function getRemainingQuotes(): int
    {
        return max(0, $this->freeLimit - $this->getCurrentCount());
    }

    /**
     * Retourne la limite gratuite quotidienne
     */
    public function getFreeLimit(): int
    {
        return $this->freeLimit;
    }

    /**
     * Réinitialise le compteur (utile pour les tests ou admin)
     */
    public function reset(): void
    {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
        setcookie(self::COOKIE_DATE_NAME, '', time() - 3600, '/');
    }
}