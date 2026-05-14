// Routeur SPA avec History API
export class Router {
  constructor(routes, appSelector) {
    this.routes = routes;
    this.appElement = document.querySelector(appSelector);
    this.currentView = null;
    this.params = {};
    // Détecter le base path depuis la balise <base>
    const baseTag = document.querySelector("base");
    this.basePath = baseTag ? new URL(baseTag.href).pathname : "/";
    // Enlever le slash final du basePath
    if (this.basePath.endsWith("/") && this.basePath.length > 1) {
      this.basePath = this.basePath.slice(0, -1);
    }
  }

  init() {
    // Écouter les changements d'URL
    window.addEventListener("popstate", () => this.handleRoute());

    // Intercepter les clics sur les liens
    document.addEventListener("click", (e) => {
      if (e.target.matches("[data-link]") || e.target.closest("[data-link]")) {
        e.preventDefault();
        const link = e.target.matches("[data-link]")
          ? e.target
          : e.target.closest("[data-link]");
        const href = link.getAttribute("href");
        this.navigate(href);
      }
    });

    // Charger la route initiale
    this.handleRoute();
  }

  async navigate(url) {
    // Normaliser l'URL pour qu'elle soit toujours absolue avec le basePath
    let fullUrl = url;

    // Si l'URL est "./", c'est la racine
    if (url === "./") {
      fullUrl = this.basePath || "/";
    }
    // Si l'URL ne commence pas par "/" ni par le basePath, c'est une URL relative
    else if (!url.startsWith("/") && !url.startsWith(this.basePath)) {
      // Ajouter le basePath et un slash
      fullUrl = this.basePath + "/" + url;
    }
    // Si l'URL commence par "/" mais pas par le basePath
    else if (url.startsWith("/") && !url.startsWith(this.basePath)) {
      fullUrl = this.basePath + url;
    }

    // Nettoyer les doubles slashes (sauf après http://)
    fullUrl = fullUrl.replace(/([^:]\/)\/+/g, "$1");

    history.pushState(null, null, fullUrl);
    await this.handleRoute();
  }

  async handleRoute() {
    const path = window.location.pathname;

    // Trouver la route correspondante
    const route = this.matchRoute(path);

    if (route) {
      // Démonter la vue précédente
      if (this.currentView && this.currentView.unmount) {
        await this.currentView.unmount();
      }

      // Ajouter classe de chargement
      this.appElement.classList.add("loading");

      try {
        // Charger et monter la nouvelle vue
        const viewModule = await route.handler();
        this.currentView = viewModule.default || viewModule;

        // Mettre à jour les métadonnées
        this.updateMetadata(this.currentView.meta);

        // Monter la vue
        await this.currentView.mount(this.appElement, this.params);
      } catch (error) {
        this.show404();
      } finally {
        // Retirer classe de chargement
        this.appElement.classList.remove("loading");
      }
    } else {
      // Route 404
      this.show404();
    }
  }

  matchRoute(path) {
    // Nettoyer le path en enlevant le base path
    let cleanPath = path;

    if (cleanPath.startsWith(this.basePath)) {
      cleanPath = cleanPath.slice(this.basePath.length);
    }

    // S'assurer qu'on a au moins un /
    if (!cleanPath.startsWith("/")) {
      cleanPath = "/" + cleanPath;
    }

    // Enlever le / final si présent (sauf pour la racine)
    if (cleanPath.length > 1 && cleanPath.endsWith("/")) {
      cleanPath = cleanPath.slice(0, -1);
    }

    for (const [pattern, handler] of Object.entries(this.routes)) {
      const match = this.match(pattern, cleanPath);
      if (match) {
        this.params = match.params;
        return { handler, params: match.params };
      }
    }
    return null;
  }

  match(pattern, path) {
    // Convertir le pattern en regex (ex: /event/:id -> /event/([^/]+))
    const paramNames = [];
    const regexPattern = pattern
      .replace(/:[^/]+/g, (match) => {
        paramNames.push(match.slice(1));
        return "([^/]+)";
      })
      .replace(/\//g, "\\/");

    const regex = new RegExp(`^${regexPattern}$`);
    const matches = path.match(regex);

    if (matches) {
      const params = {};
      paramNames.forEach((name, index) => {
        params[name] = matches[index + 1];
      });
      return { params };
    }

    return null;
  }

  updateMetadata(meta = {}) {
    document.title = meta.title || "MemoriaEventia";

    const description = document.getElementById("pageDescription");
    if (description) {
      description.content =
        meta.description || "Découvrez les événements historiques d'Europe";
    }
  }

  show404() {
    this.appElement.innerHTML = `
      <div class="container text-center py-5">
        <h1 class="display-1">404</h1>
        <p class="lead">Page non trouvée</p>
        <a href="./" data-link class="btn btn-primary">Retour à l'accueil</a>
      </div>
    `;
  }
}
