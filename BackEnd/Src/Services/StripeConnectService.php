<?php

namespace App\Services;

use App\Repositories\UserRepository;

/**
 * Service de gestion de Stripe Connect
 * Gère la connexion des comptes Stripe pour les créateurs d'événements
 */
class StripeConnectService {
    private UserRepository $userRepository;
    private string $secretKey;
    
    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
        
        $config = require __DIR__ . '/../../config.php';
        $this->secretKey = $config['stripe']['secret_key'];
        
        \Stripe\Stripe::setApiKey($this->secretKey);
    }
    
    /**
     * Créer un compte Stripe Connect pour un utilisateur
     */
    public function createConnectAccount(int $userId, string $email): array {
        try {
            // Vérifier si l'utilisateur a déjà un compte
            $existingAccount = $this->userRepository->getStripeAccountStatus($userId);
            if ($existingAccount && !empty($existingAccount['stripe_account_id'])) {
                // Recréer le lien d'onboarding
                return $this->createAccountLink($existingAccount['stripe_account_id']);
            }
            
            // Créer un nouveau compte Stripe Express (recommandé pour marketplaces)
            $account = \Stripe\Account::create([
                'type' => 'express',
                'email' => $email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
            
            // Enregistrer dans la BDD
            $this->userRepository->updateStripeAccount(
                $userId,
                $account->id,
                'pending',
                false
            );
            
            // Créer le lien d'onboarding
            return $this->createAccountLink($account->id);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur Stripe : ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Créer un lien d'onboarding Stripe
     */
    private function createAccountLink(string $accountId): array {
        try {
            $config = require __DIR__ . '/../../config.php';
            
            $accountLink = \Stripe\AccountLink::create([
                'account' => $accountId,
                'refresh_url' => $config['stripe']['refresh_url'] ?? 'http://localhost/tfeHistoire/#/profile?stripe=refresh',
                'return_url' => $config['stripe']['return_url'] ?? 'http://localhost/tfeHistoire/#/profile?stripe=success',
                'type' => 'account_onboarding',
            ]);
            
            return [
                'success' => true,
                'message' => 'Lien d\'onboarding créé',
                'data' => [
                    'account_id' => $accountId,
                    'onboarding_url' => $accountLink->url
                ]
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du lien : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Vérifier si le compte Stripe est complet et activé
     */
    public function checkAccountStatus(string $stripeAccountId): array {
        try {
            $account = \Stripe\Account::retrieve($stripeAccountId);
            
            $isComplete = $account->charges_enabled && $account->payouts_enabled;
            
            return [
                'success' => true,
                'data' => [
                    'is_complete' => $isComplete,
                    'charges_enabled' => $account->charges_enabled,
                    'payouts_enabled' => $account->payouts_enabled,
                    'details_submitted' => $account->details_submitted ?? false,
                ]
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification : ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Créer un dashboard login link pour gérer le compte Stripe
     */
    public function createDashboardLink(string $stripeAccountId): array {
        try {
            $loginLink = \Stripe\Account::createLoginLink($stripeAccountId);
            
            return [
                'success' => true,
                'data' => [
                    'url' => $loginLink->url
                ]
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du lien : ' . $e->getMessage()
            ];
        }
    }
}
