// Chargement de Leaflet à la demande : seule la page carte en a besoin,
// inutile d'alourdir toutes les autres pages (~150 Ko).

const LEAFLET_VERSION = "1.9.4";
const LEAFLET_CSS = {
  href: `https://unpkg.com/leaflet@${LEAFLET_VERSION}/dist/leaflet.css`,
  integrity: "sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=",
};
const LEAFLET_JS = {
  src: `https://unpkg.com/leaflet@${LEAFLET_VERSION}/dist/leaflet.js`,
  integrity: "sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=",
};

let leafletPromise = null;

// Retourne l'objet global L une fois Leaflet (CSS et JS) chargé
export function loadLeaflet() {
  if (window.L) return Promise.resolve(window.L);
  if (leafletPromise) return leafletPromise;

  leafletPromise = new Promise((resolve, reject) => {
    const style = document.createElement("link");
    style.rel = "stylesheet";
    style.href = LEAFLET_CSS.href;
    style.integrity = LEAFLET_CSS.integrity;
    style.crossOrigin = "";
    document.head.appendChild(style);

    const script = document.createElement("script");
    script.src = LEAFLET_JS.src;
    script.integrity = LEAFLET_JS.integrity;
    script.crossOrigin = "";
    script.onload = () => resolve(window.L);
    script.onerror = () => {
      // Permet de réessayer à la prochaine visite de la carte
      leafletPromise = null;
      reject(new Error("Impossible de charger la bibliothèque de carte"));
    };
    document.head.appendChild(script);
  });

  return leafletPromise;
}
