// Utilitaires pour la gestion du stockage local
export const storage = {
  // Récupérer le token admin
  getToken() {
    return localStorage.getItem("adminToken");
  },

  // Sauvegarder le token admin
  setToken(token) {
    localStorage.setItem("adminToken", token);
  },

  // Supprimer le token admin
  removeToken() {
    localStorage.removeItem("adminToken");
  },

  // Vérifier si un token existe
  hasToken() {
    return !!this.getToken();
  },
};

// Utilitaires généraux
export const helpers = {
  // Formater une date
  formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: "numeric", month: "long", day: "numeric" };
    return date.toLocaleDateString("fr-FR", options);
  },

  // Formater une date et heure
  formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    };
    return date.toLocaleDateString("fr-FR", options);
  },

  // Afficher un toast de notification (utilise AdminLTE Toasts)
  showToast(message, type = "info") {
    const toastClass =
      type === "success"
        ? "bg-success"
        : type === "error"
          ? "bg-danger"
          : type === "warning"
            ? "bg-warning"
            : "bg-info";

    const toast = $(`
      <div class="toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
          <strong class="mr-auto">Notification</strong>
          <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `);

    // Créer le conteneur de toasts s'il n'existe pas
    if ($("#toastContainer").length === 0) {
      $("body").append(
        '<div id="toastContainer" class="position-fixed top-0 right-0 p-3" style="z-index: 9999; right: 0; top: 60px;"></div>',
      );
    }

    $("#toastContainer").append(toast);
    toast.toast({ delay: 3000 });
    toast.toast("show");

    // Retirer le toast après fermeture
    toast.on("hidden.bs.toast", function () {
      $(this).remove();
    });
  },

  // Afficher une modale de confirmation
  showConfirm(
    title,
    message,
    confirmText = "Confirmer",
    cancelText = "Annuler",
    type = "warning",
  ) {
    return new Promise((resolve) => {
      // Supprimer les modales existantes
      $("#confirmModal").remove();

      const iconClass =
        type === "danger"
          ? "fa-exclamation-triangle"
          : type === "warning"
            ? "fa-exclamation-circle"
            : "fa-question-circle";
      const btnClass =
        type === "danger"
          ? "btn-danger"
          : type === "warning"
            ? "btn-warning"
            : "btn-primary";

      const modal = $(`
        <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas ${iconClass} mr-2"></i>${title}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p class="mb-0">${message}</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">${cancelText}</button>
                <button type="button" class="btn ${btnClass}" id="confirmBtn">${confirmText}</button>
              </div>
            </div>
          </div>
        </div>
      `);

      $("body").append(modal);

      // Gérer la confirmation
      modal.find("#confirmBtn").on("click", function () {
        modal.modal("hide");
        resolve(true);
      });

      // Gérer l'annulation
      modal.on("hidden.bs.modal", function () {
        const wasConfirmed = $(this).data("confirmed");
        if (!wasConfirmed) {
          resolve(false);
        }
        $(this).remove();
      });

      modal.find("#confirmBtn").on("click", function () {
        modal.data("confirmed", true);
      });

      modal.modal("show");
    });
  },

  // Afficher une modale avec champ de saisie (prompt)
  showPrompt(title, message, placeholder = "", defaultValue = "") {
    return new Promise((resolve) => {
      // Supprimer les modales existantes
      $("#promptModal").remove();

      const modal = $(`
        <div class="modal fade" id="promptModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas fa-edit mr-2"></i>${title}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p>${message}</p>
                <textarea class="form-control" id="promptInput" rows="3" placeholder="${placeholder}">${defaultValue}</textarea>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="promptConfirmBtn">Confirmer</button>
              </div>
            </div>
          </div>
        </div>
      `);

      $("body").append(modal);

      // Gérer la confirmation
      modal.find("#promptConfirmBtn").on("click", function () {
        const value = modal.find("#promptInput").val().trim();
        if (value) {
          modal.data("confirmed", true);
          modal.data("value", value);
          modal.modal("hide");
        } else {
          helpers.showToast("Veuillez saisir un texte", "warning");
        }
      });

      // Gérer l'annulation
      modal.on("hidden.bs.modal", function () {
        const wasConfirmed = $(this).data("confirmed");
        if (wasConfirmed) {
          resolve($(this).data("value"));
        } else {
          resolve(null);
        }
        $(this).remove();
      });

      modal.modal("show");

      // Focus sur le champ de saisie
      modal.on("shown.bs.modal", function () {
        modal.find("#promptInput").focus();
      });
    });
  },

  // Afficher une modale d'information (alert)
  showAlert(title, message, type = "info", buttonText = "OK") {
    return new Promise((resolve) => {
      // Supprimer les modales existantes
      $("#alertModal").remove();

      const iconClass =
        type === "success"
          ? "fa-check-circle text-success"
          : type === "error"
            ? "fa-exclamation-circle text-danger"
            : type === "warning"
              ? "fa-exclamation-triangle text-warning"
              : "fa-info-circle text-info";

      const modal = $(`
        <div class="modal fade" id="alertModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas ${iconClass} mr-2"></i>${title}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p class="mb-0" style="white-space: pre-wrap;">${message}</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">${buttonText}</button>
              </div>
            </div>
          </div>
        </div>
      `);

      $("body").append(modal);

      // Gérer la fermeture
      modal.on("hidden.bs.modal", function () {
        resolve(true);
        $(this).remove();
      });

      modal.modal("show");
    });
  },
};
