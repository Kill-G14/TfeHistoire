// Fonctions utilitaires

export const helpers = {
  // Formater une date
  formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  },

  // Formater une heure
  formatTime(timeStr) {
    return timeStr || "";
  },

  // Formater le prix
  formatPrice(price) {
    return parseFloat(price).toFixed(2);
  },

  // Afficher le toast ( Visuel qui permet de notifier l'utilisateur d'une action ou d'un événement de manière non intrusive. )
  showToast(message, type = "success") {
    const toastContainer = document.getElementById("toastContainer");
    if (!toastContainer) return;

    const toastId = "toast_" + Date.now();
    const bgClass =
      type === "success"
        ? "bg-success"
        : type === "error"
          ? "bg-danger"
          : "bg-info";

    const toastHTML = `
      <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header ${bgClass} text-white">
          <strong class="me-auto">
            <i class="bi ${type === "success" ? "bi-check-circle" : type === "error" ? "bi-exclamation-circle" : "bi-info-circle"}"></i>
            ${type === "success" ? "Succès" : type === "error" ? "Erreur" : "Information"}
          </strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `;

    toastContainer.insertAdjacentHTML("beforeend", toastHTML);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
      autohide: true,
      delay: 3000,
    });

    toast.show();

    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove();
    });
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
    return `https://memoriaeventia.com/BackEnd/Api/imageApi.php?name=${encodeURIComponent(imageName)}`;
  },

  // Transformer un événement pour ajouter l'URL de l'image et les infos de billetterie
  transformEvent(event) {
    // Déterminer le prix à afficher
    let displayPrice = "Gratuit";
    let priceValue = 0;

    if (
      !event.is_free &&
      event.ticket_price !== null &&
      event.ticket_price !== undefined
    ) {
      priceValue = parseFloat(event.ticket_price);
      displayPrice = this.formatPrice(priceValue) + " €";
    }

    return {
      ...event,
      image: this.getImageUrl(event.image_event),
      price: displayPrice,
      priceValue: priceValue, // Valeur numérique pour les calculs
      quantity: event.ticket_quantity || 0,
      availableTickets: event.ticket_quantity || 0,
    };
  },

  // Transformer une liste d'événements
  transformEvents(events) {
    return events.map((event) => this.transformEvent(event));
  },
};
