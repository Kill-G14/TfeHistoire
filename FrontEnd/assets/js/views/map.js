// Vue Map - Carte interactive des événements

// Métadonnées de la vue
export const meta = {
  title: 'Carte des événements - MemoriaEventia',
  description: 'Visualisez les événements historiques sur une carte interactive de l\'Europe'
}

// Template HTML
const templateObjects = {}

async function loadTemplate(path) {
  const response = await fetch(path)
  const htmlContent = await response.text()
  const parser = new DOMParser()
  const templateDoc = parser.parseFromString(htmlContent, 'text/html')
  const templates = templateDoc.querySelectorAll('template')

  templates.forEach((template) => {
    const templateId = template.id
    templateObjects[templateId] = template.content
  })
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Charger le template
  await loadTemplate('assets/templates/views/map.html')
  
  // Injecter le template
  const clone = templateObjects['mapView'].cloneNode(true)
  container.innerHTML = ''
  container.appendChild(clone)
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Pas de nettoyage nécessaire pour cette vue simple
}

// Export par défaut
export default { mount, unmount, meta }
