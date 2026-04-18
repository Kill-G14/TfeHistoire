// Utilitaires pour la gestion du stockage local
export const storage = {
  // Récupérer le token admin
  getToken() {
    return localStorage.getItem('adminToken')
  },

  // Sauvegarder le token admin
  setToken(token) {
    localStorage.setItem('adminToken', token)
  },

  // Supprimer le token admin
  removeToken() {
    localStorage.removeItem('adminToken')
  },

  // Vérifier si un token existe
  hasToken() {
    return !!this.getToken()
  }
}

// Utilitaires généraux
export const helpers = {
  // Formater une date
  formatDate(dateString) {
    const date = new Date(dateString)
    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return date.toLocaleDateString('fr-FR', options)
  },

  // Formater une date et heure
  formatDateTime(dateString) {
    const date = new Date(dateString)
    const options = {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
    return date.toLocaleDateString('fr-FR', options)
  },

  // Afficher un toast de notification (utilise AdminLTE Toasts)
  showToast(message, type = 'info') {
    const toastClass =
      type === 'success'
        ? 'bg-success'
        : type === 'error'
          ? 'bg-danger'
          : type === 'warning'
            ? 'bg-warning'
            : 'bg-info'

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
    `)

    // Créer le conteneur de toasts s'il n'existe pas
    if ($('#toastContainer').length === 0) {
      $('body').append(
        '<div id="toastContainer" class="position-fixed top-0 right-0 p-3" style="z-index: 9999; right: 0; top: 60px;"></div>'
      )
    }

    $('#toastContainer').append(toast)
    toast.toast({ delay: 3000 })
    toast.toast('show')

    // Retirer le toast après fermeture
    toast.on('hidden.bs.toast', function () {
      $(this).remove()
    })
  }
}
