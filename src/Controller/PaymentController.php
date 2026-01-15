<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PaymentController extends AbstractController
{
    private string $stripeSecretKey;
    private string $stripePublicKey;

    public function __construct(string $stripeSecretKey, string $stripePublicKey)
    {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripePublicKey = $stripePublicKey;
    }

    #[Route('/payment/create-checkout-session/{type}', name: 'payment_create_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createCheckoutSession(string $type): Response
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $successUrl = $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionData = [
                'customer_email' => $user->getEmail(),
                'client_reference_id' => (string) $user->getId(),
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
            ];

            // Pack 10 devis (5€ one-time)
            if ($type === 'pack') {
                $sessionData['mode'] = 'payment';
                $sessionData['line_items'] = [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Pack 10 devis',
                            'description' => '10 devis sans watermark valables 6 mois',
                        ],
                        'unit_amount' => 500, // 5€ en centimes
                    ],
                    'quantity' => 1,
                ]];
                $sessionData['metadata'] = [
                    'type' => 'pack',
                    'user_id' => $user->getId(),
                ];
            }
            // Abonnement Pro (9€/mois)
            elseif ($type === 'pro') {
                $sessionData['mode'] = 'subscription';
                $sessionData['line_items'] = [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Abonnement Pro',
                            'description' => 'Devis illimités + Support prioritaire',
                        ],
                        'unit_amount' => 900, // 9€ en centimes
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]];
                $sessionData['metadata'] = [
                    'type' => 'subscription',
                    'user_id' => $user->getId(),
                ];
            } else {
                throw $this->createNotFoundException('Type de paiement invalide');
            }

            $session = Session::create($sessionData);

            return $this->json(['url' => $session->url]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/payment/success', name: 'payment_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');

        if ($sessionId) {
            Stripe::setApiKey($this->stripeSecretKey);
            
            try {
                $session = Session::retrieve($sessionId);
                $metadata = $session->metadata;
                
                // Le webhook gérera la mise à jour du compte
                // Ici on affiche juste un message de confirmation
                
                return $this->render('payment/success.html.twig', [
                    'session' => $session,
                    'type' => $metadata['type'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la vérification du paiement');
            }
        }

        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé.');
        return $this->redirectToRoute('pricing');
    }

    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        
        // TODO: Configurer le webhook secret dans .env
        // $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            // Pour l'instant, on parse juste le payload sans vérifier la signature
            // En production, vous DEVEZ vérifier la signature !
            $event = json_decode($payload, true);

            // Gérer les événements Stripe
            switch ($event['type']) {
                case 'checkout.session.completed':
                    $session = $event['data']['object'];
                    $this->handleCheckoutComplete($session, $entityManager);
                    break;

                case 'customer.subscription.deleted':
                    $subscription = $event['data']['object'];
                    $this->handleSubscriptionCancelled($subscription, $entityManager);
                    break;

                default:
                    // Événement non géré
                    break;
            }

            return new Response('', 200);
        } catch (\Exception $e) {
            return new Response('Webhook error: ' . $e->getMessage(), 400);
        }
    }

    private function handleCheckoutComplete(array $session, EntityManagerInterface $entityManager): void
    {
        $userId = $session['client_reference_id'] ?? $session['metadata']['user_id'] ?? null;
        
        if (!$userId) {
            return;
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return;
        }

        $type = $session['metadata']['type'] ?? null;

        if ($type === 'pack') {
            // Créditer 10 devis au compte
            // TODO: Implémenter la logique des crédits de devis
            // Pour l'instant, on ne fait rien (à implémenter plus tard)
        } elseif ($type === 'subscription') {
            // Activer l'abonnement Pro
            $user->setSubscription('pro');
            $entityManager->flush();
        }
    }

    private function handleSubscriptionCancelled(array $subscription, EntityManagerInterface $entityManager): void
    {
        // Récupérer l'utilisateur et repasser en gratuit
        // TODO: Implémenter la logique de recherche par customer_id Stripe
    }
}
