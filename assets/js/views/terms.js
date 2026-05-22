// Vue Conditions d'utilisation

import { loadHTMLTemplate } from "../utils/templateLoader.js";

// Métadonnées de la vue
export const meta = {
  title: "Conditions d'utilisation - MemoriaEventia",
  description:
    "Consultez les conditions générales d'utilisation de MemoriaEventia. Règles, responsabilités et droits des utilisateurs et organisateurs.",
};

// Template HTML
let template = "";

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  template = await loadHTMLTemplate("assets/templates/views/terms.html");

  // Injecter le template
  container.innerHTML = template;

  // Scroll en haut de la page
  window.scrollTo(0, 0);
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire pour cette page statique
}

// Export par défaut
export default { mount, unmount, meta };
