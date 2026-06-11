import { loadTemplate } from "../utils/templateLoader.js";

const templateObjects = {};

export async function loadMobileNav() {
  try {
    Object.assign(
      templateObjects,
      await loadTemplate("assets/components/mobileNav.html"),
    );

    const container = document.getElementById("mobileNav");
    if (!container) {
      console.error("Container #mobileNav not found");
      return;
    }

    const template = templateObjects["mobileNavTemplate"];
    if (!template) {
      console.error("Template mobileNavTemplate not found");
      return;
    }

    const clone = template.cloneNode(true);
    container.innerHTML = "";
    container.appendChild(clone);

    // Mettre à jour l'élément actif selon la page
    updateActiveNav();

    // Écouter les changements de route
    window.addEventListener("popstate", updateActiveNav);
  } catch (error) {
    console.error("Error loading mobile nav:", error);
  }
}

function updateActiveNav() {
  const currentPath = window.location.pathname;
  const navItems = document.querySelectorAll(".mobile-nav-item");

  navItems.forEach((item) => {
    item.classList.remove("active");
    const href = item.getAttribute("href");

    if (
      (currentPath === "/" || currentPath === "/index.html") &&
      href === "./"
    ) {
      item.classList.add("active");
    } else if (href !== "./" && currentPath.includes(href)) {
      item.classList.add("active");
    }
  });
}

export { updateActiveNav };
