// Composant Footer

// Objet pour stocker les templates
const templateObjects = {};

// Chargement du template HTML
async function loadTemplate(path) {
  const response = await fetch(path);
  const htmlContent = await response.text();
  const parser = new DOMParser();
  const templateDoc = parser.parseFromString(htmlContent, "text/html");
  const templates = templateDoc.querySelectorAll("template");

  templates.forEach((template) => {
    const templateId = template.id;
    templateObjects[templateId] = template.content;
  });
}

// Export de la fonction de rendu
export async function renderFooter() {
  await loadTemplate("assets/components/footer.html");

  const element = document.getElementById("footer");
  if (!element) return;

  const clone = templateObjects["footerTemplate"].cloneNode(true);
  element.appendChild(clone);

  // Les événements sont gérés par le routeur global (router.js)
  // Pas besoin d'attacher d'événements ici
}
