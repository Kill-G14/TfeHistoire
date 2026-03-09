// Composant Event Card

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

export async function renderEventCards(events, containerId, onEventClick) {
  await loadTemplate('./assets/components/eventCard.html')

  const container = document.getElementById(containerId)
  if (!container) return

  container.innerHTML = ''

  if (events.length === 0) {
    container.innerHTML = `
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-calendar-x fs-1 mb-3 d-block"></i>
          <p class="fs-5">Aucun événement trouvé</p>
        </div>
      </div>
    `
    return
  }

  events.forEach(event => {
    const clone = templateObjects['eventCardTemplate'].cloneNode(true)

    // Remplir les données
    const img = clone.querySelector('.eventCard-image')
    const category = clone.querySelector('.eventCard-category')
    const title = clone.querySelector('.eventCard-title')
    const description = clone.querySelector('.eventCard-description')
    const location = clone.querySelector('.eventCard-location')
    const date = clone.querySelector('.eventCard-date')
    const time = clone.querySelector('.eventCard-time')
    const price = clone.querySelector('.eventCard-price')
    const btn = clone.querySelector('.eventCard-btn')
    const card = clone.querySelector('.eventCard')

    if (img) {
      img.src = event.image
      img.alt = event.title
    }
    if (category) category.textContent = event.category
    if (title) title.textContent = event.title
    if (description) description.textContent = event.description
    if (location) location.textContent = `${event.city}, ${event.country}`
    if (date) date.textContent = event.date
    if (time) time.textContent = event.time
    if (price) price.textContent = event.price

    // Événement de clic
    if (card) {
      card.addEventListener('click', (e) => {
        if (e.target !== btn && !btn.contains(e.target)) {
          onEventClick(event)
        }
      })
    }

    if (btn) {
      btn.addEventListener('click', (e) => {
        e.stopPropagation()
        onEventClick(event)
      })
    }

    container.appendChild(clone)
  })
}
