// Gestion d'état centralisée
class AppState {
  constructor() {
    this.state = {
      user: null,
      cart: [],
      events: [],
      favorites: [],
      isAuthenticated: false
    }
    this.subscribers = {}
  }

  // Obtenir une valeur
  get(key) {
    return this.state[key]
  }

  // Définir une valeur
  set(key, value) {
    this.state[key] = value
    this.notify(key, value)
  }

  // S'abonner aux changements
  subscribe(key, callback) {
    if (!this.subscribers[key]) {
      this.subscribers[key] = []
    }
    this.subscribers[key].push(callback)

    // Retourner une fonction de désabonnement
    return () => {
      this.subscribers[key] = this.subscribers[key].filter(cb => cb !== callback)
    }
  }

  // Notifier les abonnés
  notify(key, value) {
    if (this.subscribers[key]) {
      this.subscribers[key].forEach(callback => callback(value))
    }
  }

  // Réinitialiser l'état
  reset() {
    this.state = {
      user: null,
      cart: [],
      events: [],
      favorites: [],
      isAuthenticated: false
    }
    Object.keys(this.subscribers).forEach(key => {
      this.notify(key, this.state[key])
    })
  }
}

// Export d'une instance singleton
export const appState = new AppState()
