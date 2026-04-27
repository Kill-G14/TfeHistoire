// Configuration de l'application

// Déterminer le base path automatiquement
const getBasePath = () => {
  const path = window.location.pathname;
  // Si on est dans /tfeHistoire/, on retourne ce chemin
  const match = path.match(/^(\/[^/]+)/);
  if (match) {
    return match[1];
  }
  // Sinon on est à la racine
  return "";
};

export const config = {
  BASE_PATH: getBasePath(),
  API_URL: "http://localhost/tfeHistoire/BackEnd/Api",
};

// Helper pour construire les chemins d'assets
export const getAssetPath = (path) => {
  // Enlever le / initial si présent
  const cleanPath = path.startsWith("/") ? path.slice(1) : path;
  return `${config.BASE_PATH}/${cleanPath}`;
};
