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

// Correspondance nom de pays (FR) -> code ISO 3166-1 alpha-2, pour le géocodage structuré
export const countryCodes = {
  'Albanie': 'al',
  'Allemagne': 'de',
  'Andorre': 'ad',
  'Autriche': 'at',
  'Belgique': 'be',
  'Biélorussie': 'by',
  'Bosnie-Herzégovine': 'ba',
  'Bulgarie': 'bg',
  'Chypre': 'cy',
  'Croatie': 'hr',
  'Danemark': 'dk',
  'Espagne': 'es',
  'Estonie': 'ee',
  'Finlande': 'fi',
  'France': 'fr',
  'Grèce': 'gr',
  'Hongrie': 'hu',
  'Irlande': 'ie',
  'Islande': 'is',
  'Italie': 'it',
  'Kosovo': 'xk',
  'Lettonie': 'lv',
  'Liechtenstein': 'li',
  'Lituanie': 'lt',
  'Luxembourg': 'lu',
  'Macédoine du Nord': 'mk',
  'Malte': 'mt',
  'Moldavie': 'md',
  'Monaco': 'mc',
  'Monténégro': 'me',
  'Norvège': 'no',
  'Pays-Bas': 'nl',
  'Pologne': 'pl',
  'Portugal': 'pt',
  'République Tchèque': 'cz',
  'Roumanie': 'ro',
  'Royaume-Uni': 'gb',
  'Russie': 'ru',
  'Saint-Marin': 'sm',
  'Serbie': 'rs',
  'Slovaquie': 'sk',
  'Slovénie': 'si',
  'Suède': 'se',
  'Suisse': 'ch',
  'Ukraine': 'ua',
  'Vatican': 'va'
}

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
