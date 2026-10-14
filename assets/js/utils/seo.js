// Balises de référencement et de partage mises à jour à chaque changement de page
// (la SPA ne recharge jamais le document : le routeur et les vues passent par ici).

const DEFAULT_TITLE = "MemoriaEventia";
const DEFAULT_DESCRIPTION =
  "Découvrez les événements historiques d'Europe";

// Retrouve une balise <meta> ou la crée si elle n'existe pas encore
function getMetaTag(attribute, name) {
  let tag = document.head.querySelector(`meta[${attribute}="${name}"]`);
  if (!tag) {
    tag = document.createElement("meta");
    tag.setAttribute(attribute, name);
    document.head.appendChild(tag);
  }
  return tag;
}

// Adresse absolue d'une image (les robots de partage n'acceptent pas les chemins relatifs)
function toAbsoluteUrl(url) {
  if (!url) return null;
  try {
    return new URL(url, document.baseURI).href;
  } catch (error) {
    return null;
  }
}

/**
 * Met à jour le titre, la description, les balises de partage et le lien canonique.
 * @param {Object} meta - { title, description, image, noindex }
 */
export function setPageMetadata(meta = {}) {
  const title = meta.title || DEFAULT_TITLE;
  const description = meta.description || DEFAULT_DESCRIPTION;
  const pageUrl = window.location.origin + window.location.pathname;

  document.title = title;
  getMetaTag("name", "description").content = description;

  // Balises de partage (Facebook, WhatsApp, LinkedIn...)
  getMetaTag("property", "og:title").content = title;
  getMetaTag("property", "og:description").content = description;
  getMetaTag("property", "og:url").content = pageUrl;

  const image = toAbsoluteUrl(meta.image);
  if (image) {
    getMetaTag("property", "og:image").content = image;
  }

  // Lien canonique : une seule adresse de référence par page
  let canonical = document.head.querySelector('link[rel="canonical"]');
  if (!canonical) {
    canonical = document.createElement("link");
    canonical.rel = "canonical";
    document.head.appendChild(canonical);
  }
  canonical.href = pageUrl;

  // Pages privées (profil, paiement...) : à ne pas indexer
  const robots = document.head.querySelector('meta[name="robots"]');
  if (meta.noindex) {
    getMetaTag("name", "robots").content = "noindex, nofollow";
  } else if (robots) {
    robots.remove();
  }
}

// Données structurées schema.org (JSON-LD) : aide les moteurs à comprendre une page d'événement
export function setStructuredData(data) {
  clearStructuredData();

  if (!data) return;

  const script = document.createElement("script");
  script.type = "application/ld+json";
  script.id = "structuredData";
  // JSON.stringify échappe les guillemets, textContent évite toute injection HTML
  script.textContent = JSON.stringify(data);
  document.head.appendChild(script);
}

export function clearStructuredData() {
  document.getElementById("structuredData")?.remove();
}
