/**
 * Configuration centralisée pour MemoriaEventia
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
    BASE_PATH: '/tfeHistoire',
    FRONTEND_URL: 'http://localhost/tfeHistoire'
  },
  
  // ==============================================
  // PRODUCTION (à modifier avant la mise en ligne)
  // ==============================================
  production: {
    // TODO: Remplacer par votre domaine réel
    // Exemples:
    // API_URL: 'https://memoriaeventia.com/BackEnd/Api'
    // BASE_PATH: ''
    // FRONTEND_URL: 'https://memoriaeventia.com'
    
    API_URL: 'https://votre-domaine.com/BackEnd/Api',
    BASE_PATH: '',
    FRONTEND_URL: 'https://votre-domaine.com'
  }
};

// Export de la configuration active selon l'environnement
const config = isProduction ? CONFIG.production : CONFIG.development;

// Helper functions pour construire les URLs
export const getApiUrl = (endpoint = '') => {
  const baseUrl = config.API_URL;
  return endpoint ? `${baseUrl}/${endpoint}` : baseUrl;
};

export const getImageUrl = (imageName) => {
  if (!imageName) return null;
  return `${config.API_URL}/imageApi.php?name=${encodeURIComponent(imageName)}`;
};

export const getFrontendUrl = (path = '') => {
  return `${config.FRONTEND_URL}${path}`;
};

// Export de la config complète
export default config;

// Debug: afficher la config active (à retirer en production)
if (!isProduction) {
  console.log('🔧 Configuration active:', config);
  console.log('🌍 Environnement:', isProduction ? 'PRODUCTION' : 'DEVELOPMENT');
}
