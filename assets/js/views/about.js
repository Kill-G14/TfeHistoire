// Vue À propos

import { loadHTMLTemplate } from "../utils/templateLoader.js";

// Métadonnées de la vue
export const meta = {
  title: "À propos - MemoriaEventia",
  description:
    "Découvrez MemoriaEventia, votre passerelle vers l'histoire vivante de l'Europe. Mission, valeurs et fonctionnement de notre plateforme.",
};

// Template HTML
let template = "";

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  template = await loadHTMLTemplate("assets/templates/views/about.html");

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
