// Composant Footer

// Objet pour stocker les templates
const templateObjects = {}

// Chargement du template HTML
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

// Export de la fonction de rendu
export async function renderFooter() {
  await loadTemplate('./assets/components/footer.html')

  const element = document.getElementById('footer')
  if (!element) return

  const clone = templateObjects['footerTemplate'].cloneNode(true)
  element.appendChild(clone)

  // Attacher les événements pour les liens de navigation
  attachFooterEvents()
}

function attachFooterEvents() {
  const footer = document.getElementById('footer')
  if (!footer) return

  // Intercepter les liens pour la navigation SPA
  footer.addEventListener('click', (e) => {
    if (e.target.closest('a[data-link]')) {
      e.preventDefault()
      const link = e.target.closest('a[data-link]')
      const href = link.getAttribute('href')
      if (window.router) {
        window.router.navigate(href)
      }
    }
  })
}
