// Liste complète des pays européens
export const europeanCountries = [
  'Albanie',
  'Allemagne',
  'Andorre',
  'Autriche',
  'Belgique',
  'Biélorussie',
  'Bosnie-Herzégovine',
  'Bulgarie',
  'Chypre',
  'Croatie',
  'Danemark',
  'Espagne',
  'Estonie',
  'Finlande',
  'France',
  'Grèce',
  'Hongrie',
  'Irlande',
  'Islande',
  'Italie',
  'Kosovo',
  'Lettonie',
  'Liechtenstein',
  'Lituanie',
  'Luxembourg',
  'Macédoine du Nord',
  'Malte',
  'Moldavie',
  'Monaco',
  'Monténégro',
  'Norvège',
  'Pays-Bas',
  'Pologne',
  'Portugal',
  'République Tchèque',
  'Roumanie',
  'Royaume-Uni',
  'Russie',
  'Saint-Marin',
  'Serbie',
  'Slovaquie',
  'Slovénie',
  'Suède',
  'Suisse',
  'Ukraine',
  'Vatican'
]

// Fonction pour remplir un select avec les pays
export function populateCountrySelect(selectElement) {
  if (!selectElement) return

  // Garder l'option par défaut
  const defaultOption = selectElement.querySelector('option[value=""]')
  
  // Vider le select sauf l'option par défaut
  selectElement.innerHTML = defaultOption ? defaultOption.outerHTML : '<option value="">Sélectionner un pays</option>'

  // Ajouter tous les pays
  europeanCountries.forEach(country => {
    const option = document.createElement('option')
    option.value = country
    option.textContent = country
    selectElement.appendChild(option)
  })
}
