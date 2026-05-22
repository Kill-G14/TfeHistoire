// Composant Footer

import { loadTemplate } from "../utils/templateLoader.js";

// Objet pour stocker les templates
const templateObjects = {};

// Export de la fonction de rendu
export async function renderFooter() {
  Object.assign(
    templateObjects,
    await loadTemplate("assets/components/footer.html"),
  );

  const element = document.getElementById("footer");
  if (!element) return;

  const clone = templateObjects["footerTemplate"].cloneNode(true);
  element.appendChild(clone);

  // Les événements sont gérés par le routeur global (router.js)
  // Pas besoin d'attacher d'événements ici
}
