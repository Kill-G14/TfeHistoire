<?php

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Utils\Logger;

class PasswordResetService
{
    private PasswordResetRepository $passwordResetRepository;
    private UserRepository $userRepository;
    private EmailService $emailService;

    public function __construct(
        PasswordResetRepository $passwordResetRepository,
        UserRepository $userRepository,
        EmailService $emailService
    ) {
        $this->passwordResetRepository = $passwordResetRepository;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * Demander une réinitialisation de mot de passe
     */
    public function requestPasswordReset(string $email): array
    {
        try {
            // Vérifier si l'utilisateur existe
            $user = $this->userRepository->getUserByEmail($email);
            
            if (!$user) {
                // Par sécurité, on ne révèle pas si l'email existe ou non
                Logger::info("Password reset requested for non-existent email: $email");
                return [
                    'success' => true,
                    'message' => 'Si cet email existe, un code de réinitialisation a été envoyé.'
                ];
            }

            // Supprimer les anciennes demandes
            $this->passwordResetRepository->deleteByUserId($user->id);

            // Générer un code à 6 chiffres
            $code = $this->generateCode();

            // Créer la demande (expiration gérée par MySQL avec DATE_ADD)
            $created = $this->passwordResetRepository->create($user->id, $code);

            if (!$created) {
                Logger::error("Failed to create password reset for user ID: {$user->id}");
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création de la demande'
                ];
            }

            // Envoyer l'email
            $emailSent = $this->emailService->sendPasswordResetEmail($user, $code);

            if (!$emailSent) {
                Logger::error("Failed to send password reset email", ['email' => $user->email]);
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email'
                ];
            }

            Logger::info("Password reset email sent successfully", ['email' => $user->email]);

            return [
                'success' => true,
                'message' => 'Un code de réinitialisation a été envoyé à votre adresse email.'
            ];

        } catch (\Exception $e) {
            Logger::error("Error in requestPasswordReset: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue'
            ];
        }
    }

    /**
     * Réinitialiser le mot de passe avec le code
     */
    public function resetPassword(string $email, string $code, string $newPassword): array
    {
        try {
            // Vérifier si l'utilisateur existe
            $user = $this->userRepository->getUserByEmail($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email invalide'
                ];
            }

            // Récupérer la demande
            $reset = $this->passwordResetRepository->findByUserAndCode($user->id, $code);

            if (!$reset) {
                Logger::info("Invalid or expired code", ['email' => $user->email]);
                return [
                    'success' => false,
                    'message' => 'Code invalide ou expiré'
                ];
            }

            // Vérifier le nombre de tentatives (max 5)
            if ($reset->attempts >= 5) {
                Logger::warning("Too many attempts for password reset", ['email' => $user->email]);
                $this->passwordResetRepository->deleteByUserId($user->id);
                return [
                    'success' => false,
                    'message' => 'Trop de tentatives. Veuillez faire une nouvelle demande.'
                ];
            }

            // Incrémenter les tentatives
            $this->passwordResetRepository->incrementAttempts($reset->id);

            // Valider le nouveau mot de passe
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Le mot de passe doit contenir au moins 8 caractères'
                ];
            }

            // Hash le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe
            $updated = $this->userRepository->updatePassword($user->id, $hashedPassword);

            if (!$updated) {
                Logger::error("Failed to update password", ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du mot de passe'
                ];
            }

            // Supprimer toutes les demandes de l'utilisateur
            $this->passwordResetRepository->deleteByUserId($user->id);

            Logger::info("Password reset successful", ['email' => $user->email]);

            return [
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès'
            ];

        } catch (\Exception $e) {
            Logger::error("Error in resetPassword: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue'
            ];
        }
    }

    /**
     * Générer un code à 6 chiffres
     */
    private function generateCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
