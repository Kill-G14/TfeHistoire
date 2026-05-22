// ============================================
// Template Loader avec versioning automatique
// ============================================

// Version actuelle des templates (à incrémenter à chaque modification)
const TEMPLATE_VERSION = "1.0.0";

/**
 * Charge un template HTML avec versioning pour éviter les problèmes de cache
 * @param {string} path - Chemin vers le template (ex: "assets/templates/views/home.html")
 * @returns {Promise<Object>} Objet contenant les templates par ID
 */
export async function loadTemplate(path) {
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
