/**
 * Configuration centralisée pour AdminOffice
 * 
 * IMPORTANT POUR LA PRODUCTION :
 * Modifier les valeurs dans la section PRODUCTION ci-dessous
 * avec les URLs réelles de votre hébergement
 */

// Détection automatique de l'environnement
const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

// Configuration par environnement
const CONFIG = {
  // ==============================================
  // DÉVELOPPEMENT (localhost)
  // ==============================================
  development: {
    API_URL: 'http://localhost/tfeHistoire/BackEnd/Api',
    BASE_PATH: '/tfeHistoire/AdminOffice',
    FRONTEND_URL: 'http://localhost/tfeHistoire/AdminOffice'
  },
  
  // ==============================================
  // PRODUCTION (à modifier avant la mise en ligne)
  // ==============================================
  production: {
    // TODO: Remplacer par votre domaine réel
    // Exemples:
    // API_URL: 'https://memoriaeventia.com/BackEnd/Api'
    // BASE_PATH: '/AdminOffice'
    // FRONTEND_URL: 'https://memoriaeventia.com/AdminOffice'
    
    API_URL: 'https://votre-domaine.com/BackEnd/Api',
    BASE_PATH: '/AdminOffice',
    FRONTEND_URL: 'https://votre-domaine.com/AdminOffice'
  }
};

// Export de la configuration active selon l'environnement
const config = isProduction ? CONFIG.production : CONFIG.development;

// Helper function pour construire les URLs d'API
export const getApiUrl = (endpoint = '') => {
  const baseUrl = config.API_URL;
  return endpoint ? `${baseUrl}/${endpoint}` : baseUrl;
};

// Export de la config complète
export default config;

// Debug: afficher la config active (à retirer en production)
if (!isProduction) {
  console.log('🔧 AdminOffice Configuration active:', config);
  console.log('🌍 Environnement:', isProduction ? 'PRODUCTION' : 'DEVELOPMENT');
}
