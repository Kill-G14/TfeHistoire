// Utilitaires pour la gestion du localStorage

export const storage = {
  // Récupérer une valeur du localStorage
  get(key) {
    try {
      const item = localStorage.getItem(key)
      return item ? JSON.parse(item) : null
    } catch (error) {
      return null
    }
  },

  // Enregistrer une valeur dans le localStorage
  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value))
      return true
    } catch (error) {
      return false
    }
  },

  // Supprimer une valeur du localStorage
  remove(key) {
    try {
      localStorage.removeItem(key)
      return true
    } catch (error) {
      return false
    }
  },

  // Vider tout le localStorage
  clear() {
    try {
      localStorage.clear()
      return true
    } catch (error) {
      return false
    }
  }
}
