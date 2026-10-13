// Sommaire automatique pour les pages longues (conditions, confidentialité)

// Construit un sommaire à partir des titres h2 de la page et l'insère avant la première section.
// Les liens défilent avec scrollIntoView : un lien "#ancre" déclencherait le router (popstate).
export function buildTableOfContents(container) {
  const headings = Array.from(container.querySelectorAll("h2"));
  if (headings.length < 3) return;

  const firstSection = headings[0].closest(".card");
  if (!firstSection) return;

  const nav = document.createElement("nav");
  nav.className = "card shadow-sm mb-4 toc-card";
  nav.setAttribute("aria-label", "Sommaire");

  const body = document.createElement("div");
  body.className = "card-body p-4";

  const title = document.createElement("h2");
  title.className = "h5 text-primary mb-3";
  title.textContent = "Sommaire";

  const list = document.createElement("ol");
  list.className = "toc-list mb-0";

  headings.forEach((heading, index) => {
    // Chaque section (carte) devient la cible du lien
    const target = heading.closest(".card") || heading;
    target.id = `toc-section-${index + 1}`;

    const item = document.createElement("li");
    const link = document.createElement("button");
    link.type = "button";
    link.className = "btn btn-link p-0 text-start";
    link.textContent = heading.textContent.trim();
    link.addEventListener("click", () => {
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    item.appendChild(link);
    list.appendChild(item);
  });

  body.append(title, list);
  nav.appendChild(body);
  firstSection.parentNode.insertBefore(nav, firstSection);
}
