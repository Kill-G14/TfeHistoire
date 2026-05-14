// Vue FAQ (Foire aux questions)

// Métadonnées de la vue
export const meta = {
  title: "FAQ - Questions fréquentes - MemoriaEventia",
  description:
    "Trouvez rapidement des réponses à vos questions sur MemoriaEventia : réservation, paiement, compte, événements et plus encore.",
};

// Template HTML
let template = "";

// Variables locales
let currentCategory = "all";

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  const response = await fetch("assets/templates/views/faq.html");
  template = await response.text();

  // Injecter le template
  container.innerHTML = template;

  // Attacher les événements
  attachEventListeners();

  // Scroll en haut de la page
  window.scrollTo(0, 0);
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire
}

// Attacher les event listeners
function attachEventListeners() {
  // Recherche dans la FAQ
  const searchInput = document.getElementById("faqSearch");
  if (searchInput) {
    searchInput.addEventListener("input", handleSearch);
  }

  // Filtres par catégorie
  const categoryButtons = document.querySelectorAll("[data-category]");
  categoryButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const category = button.dataset.category;
      filterByCategory(category);

      // Update active state
      categoryButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
    });
  });
}

// Gestion de la recherche
function handleSearch(e) {
  const searchTerm = e.target.value.toLowerCase().trim();
  const faqItems = document.querySelectorAll(".faq-item");

  faqItems.forEach((item) => {
    const question = item
      .querySelector(".accordion-button")
      .textContent.toLowerCase();
    const answer = item
      .querySelector(".accordion-body")
      .textContent.toLowerCase();

    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
      item.style.display = "block";
    } else {
      item.style.display = "none";
    }
  });

  // Si recherche active, afficher toutes les catégories
  if (searchTerm) {
    currentCategory = "all";
    document.querySelectorAll('[data-category="all"]').forEach((btn) => {
      btn.classList.add("active");
    });
    document
      .querySelectorAll('[data-category]:not([data-category="all"])')
      .forEach((btn) => {
        btn.classList.remove("active");
      });
  }
}

// Filtrage par catégorie
function filterByCategory(category) {
  currentCategory = category;
  const faqItems = document.querySelectorAll(".faq-item");

  // Réinitialiser la recherche
  const searchInput = document.getElementById("faqSearch");
  if (searchInput) {
    searchInput.value = "";
  }

  faqItems.forEach((item) => {
    if (category === "all" || item.dataset.category === category) {
      item.style.display = "block";
    } else {
      item.style.display = "none";
    }
  });
}

// Export par défaut
export default { mount, unmount, meta };
