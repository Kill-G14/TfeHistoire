// Fonctions utilitaires

export const helpers = {
  // Formater une date
  formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const formatted = date.toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
    return formatted.replace(/\//g, "-");
  },

  // Formater une heure
  formatTime(timeStr) {
    if (!timeStr) return "";
    // Si le format contient des secondes (HH:MM:SS), les enlever
    if (timeStr.length === 8 && timeStr.split(":").length === 3) {
      return timeStr.substring(0, 5);
    }
    return timeStr;
  },

  // Formater le prix
  formatPrice(price) {
    return parseFloat(price).toFixed(2);
  },

  // Afficher le toast ( Visuel qui permet de notifier l'utilisateur d'une action ou d'un événement de manière non intrusive. )
  showToast(message, type = "success") {
    const toastContainer = document.getElementById("toastContainer");
    if (!toastContainer) return;

    const types = {
      success: { bg: "bg-success", icon: "bi-check-circle", title: "Succès" },
      error: { bg: "bg-danger", icon: "bi-exclamation-circle", title: "Erreur" },
      warning: {
        bg: "bg-warning text-dark",
        icon: "bi-exclamation-triangle",
        title: "Attention",
      },
      info: { bg: "bg-info", icon: "bi-info-circle", title: "Information" },
    };
    const style = types[type] || types.info;

    // Construit avec textContent : le message peut venir du serveur, il ne doit jamais être interprété comme du HTML
    const toastElement = document.createElement("div");
    toastElement.className = "toast show";
    toastElement.setAttribute("role", "alert");
    toastElement.setAttribute("aria-live", "assertive");
    toastElement.setAttribute("aria-atomic", "true");

    const header = document.createElement("div");
    header.className = `toast-header ${style.bg} text-white`;

    const title = document.createElement("strong");
    title.className = "me-auto";
    const icon = document.createElement("i");
    icon.className = `bi ${style.icon}`;
    title.append(icon, ` ${style.title}`);

    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "btn-close btn-close-white";
    closeButton.setAttribute("data-bs-dismiss", "toast");
    closeButton.setAttribute("aria-label", "Fermer");

    header.append(title, closeButton);

    const body = document.createElement("div");
    body.className = "toast-body";
    body.textContent = message;

    toastElement.append(header, body);
    toastContainer.appendChild(toastElement);

    const toast = new bootstrap.Toast(toastElement, {
      autohide: true,
      delay: 3000,
    });

    toast.show();

    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove();
    });
  },

  // Met un bouton en état de chargement (évite le double envoi) ; resetButton restaure l'état d'origine
  setButtonLoading(button, text = "Chargement...") {
    if (!button || button.dataset.originalHtml !== undefined) return;
    button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.textContent = "";
    const spinner = document.createElement("span");
    spinner.className = "spinner-border spinner-border-sm me-2";
    spinner.setAttribute("role", "status");
    spinner.setAttribute("aria-hidden", "true");
    button.append(spinner, text);
  },

  resetButton(button) {
    if (!button || button.dataset.originalHtml === undefined) return;
    button.innerHTML = button.dataset.originalHtml;
    delete button.dataset.originalHtml;
    button.disabled = false;
  },

  // Ferme un modal Bootstrap puis exécute la suite : évite les setTimeout approximatifs
  closeModalThen(modalInstance, callback) {
    const modalElement = modalInstance._element;
    if (!modalElement.classList.contains("show")) {
      callback();
      return;
    }
    modalElement.addEventListener("hidden.bs.modal", () => callback(), {
      once: true,
    });
    modalInstance.hide();
  },

  // Neutralise le HTML d'une donnée saisie par un utilisateur avant de l'insérer dans un gabarit innerHTML
  escapeHtml(value) {
    const replacements = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    };
    return String(value ?? "").replace(/[&<>"']/g, (char) => replacements[char]);
  },

  // Encoder pour URL
  encodeQueryParam(value) {
    return encodeURIComponent(value);
  },

  // Décoder depuis URL
  decodeQueryParam(value) {
    return decodeURIComponent(value);
  },

  // Récupérer un paramètre d'URL
  getUrlParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  },

  // Construire l'URL de l'image à partir du nom de fichier
  getImageUrl(imageName) {
    if (!imageName) return "/assets/images/default-event.jpg"; // Image par défaut
    // Importer config dynamiquement ou utiliser une variable globale
    const apiUrl =
      window.__APP_CONFIG__?.API_URL ||
      "https://memoriaeventia.com/BackEnd/Api";
    return `${apiUrl}/imageApi.php?name=${encodeURIComponent(imageName)}`;
  },

  // Transformer un événement pour ajouter l'URL de l'image et les infos de capacité
  transformEvent(event) {
    return {
      ...event,
      image: this.getImageUrl(event.image_event),
      price: "Gratuit",
      quantity: event.ticket_quantity || 0,
      availableTickets: event.ticket_quantity || 0,
    };
  },

  // Transformer une liste d'événements
  transformEvents(events) {
    return events.map((event) => this.transformEvent(event));
  },
};
