/**
 * Configuration centralisée de l'application MemoriaEventia
 *
 * Détection automatique de l'environnement selon le hostname
 * Aucune modification manuelle nécessaire entre local et production
 */

// Détection de l'environnement
const hostname = window.location.hostname;
const isProduction =
  hostname === "memoriaeventia.com" || hostname === "www.memoriaeventia.com";
const isLocal = hostname === "localhost" || hostname === "127.0.0.1";

// Configuration selon l'environnement
export const config = {
  // Environnement actuel
  ENVIRONMENT: isProduction ? "production" : "development",

  // Debug mode (activé uniquement en développement)
  DEBUG: !isProduction,

  // URLs de l'API
  API_URL: isProduction
    ? "https://memoriaeventia.com/BackEnd/Api"
    : "http://localhost/tfeHistoire/BackEnd/Api",

  // Base path pour le routing
  BASE_PATH: isProduction ? "/" : "/tfeHistoire/",

  // URL du frontend
  FRONTEND_URL: isProduction
    ? "https://memoriaeventia.com"
    : "http://localhost/tfeHistoire",

  // Paramètres de l'application
  APP: {
    NAME: "MemoriaEventia",
    VERSION: "1.0.0",
    DESCRIPTION: "Événements historiques d'Europe",
  },

  // Timeouts et limites
  TIMEOUTS: {
    API_REQUEST: 30000, // 30 secondes
    IMAGE_UPLOAD: 60000, // 60 secondes
  },

  // Validations
  VALIDATION: {
    MIN_PASSWORD_LENGTH: 6,
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5 MB
    ALLOWED_IMAGE_TYPES: ["image/jpeg", "image/jpg", "image/png", "image/webp"],
  },

  // Logs (uniquement en développement)
  log: function (...args) {
    if (this.DEBUG) {
      console.log("[MemoriaEventia]", ...args);
    }
  },

  error: function (...args) {
    if (this.DEBUG) {
      console.error("[MemoriaEventia ERROR]", ...args);
    }
  },

  warn: function (...args) {
    if (this.DEBUG) {
      console.warn("[MemoriaEventia WARN]", ...args);
    }
  },
};

// Afficher la configuration au chargement (en développement uniquement)
if (config.DEBUG) {
  console.log("🚀 MemoriaEventia - Configuration chargée");
  console.log("   Environnement:", config.ENVIRONMENT);
  console.log("   API URL:", config.API_URL);
  console.log("   Base Path:", config.BASE_PATH);
}

// Exporter aussi les helpers de configuration
export const isProduction = () => config.ENVIRONMENT === "production";
export const isDevelopment = () => config.ENVIRONMENT === "development";
export const getApiUrl = (endpoint = "") => `${config.API_URL}${endpoint}`;
export const getBasePath = () => config.BASE_PATH;
