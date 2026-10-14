// ============================================
// Template Loader avec versioning automatique
// ============================================

// Version actuelle des templates (à incrémenter à chaque modification)
const TEMPLATE_VERSION = "1.0.2";

/**
 * Charge un template HTML avec versioning pour éviter les problèmes de cache
 * @param {string} path - Chemin vers le template (ex: "assets/templates/views/home.html")
 * @returns {Promise<Object>} Objet contenant les templates par ID
 */
// Gabarits déjà demandés : évite de refaire la même requête à chaque rendu
const templateCache = new Map();

export async function loadTemplate(path) {
  if (!templateCache.has(path)) {
    templateCache.set(path, fetchTemplates(path));
  }

  try {
    return await templateCache.get(path);
  } catch (error) {
    // Échec réseau : on ne garde pas l'erreur en cache, la prochaine tentative repart de zéro
    templateCache.delete(path);
    throw error;
  }
}

async function fetchTemplates(path) {
  // Ajouter le versioning à l'URL
  const versionedPath = `${path}?v=${TEMPLATE_VERSION}`;

  const response = await fetch(versionedPath);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  const templateObjects = {};
  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });

  return templateObjects;
}

/**
 * Charge un template HTML simple (sans balises <template>)
 * @param {string} path - Chemin vers le fichier HTML
 * @returns {Promise<string>} Contenu HTML du template
 */
export async function loadHTMLTemplate(path) {
  // Ajouter le versioning à l'URL
  const versionedPath = `${path}?v=${TEMPLATE_VERSION}`;

  const response = await fetch(versionedPath);
  return await response.text();
}

/**
 * Met à jour la version des templates (pour usage futur)
 * @returns {string} Version actuelle
 */
export function getTemplateVersion() {
  return TEMPLATE_VERSION;
}
