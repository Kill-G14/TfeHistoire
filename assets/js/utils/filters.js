// Module de gestion des filtres pour les événements

import { europeanCountries } from "./countries.js";

export const filters = {
  // État actuel des filtres
  state: {
    searchTerm: "",
    country: "all",
    category: "all",
    dateFrom: null,
    dateTo: null,
    priceMin: null,
    priceMax: null,
  },

  // Réinitialiser tous les filtres
  reset() {
    this.state = {
      searchTerm: "",
      country: "all",
      category: "all",
      dateFrom: null,
      dateTo: null,
      priceMin: null,
      priceMax: null,
    };
  },

  // Mettre à jour un filtre spécifique
  updateFilter(filterName, value) {
    if (this.state.hasOwnProperty(filterName)) {
      this.state[filterName] = value;
    }
  },

  // Mettre à jour plusieurs filtres à la fois
  updateFilters(filtersObject) {
    Object.keys(filtersObject).forEach((key) => {
      if (this.state.hasOwnProperty(key)) {
        this.state[key] = filtersObject[key];
      }
    });
  },

  // Filtrer les événements selon les critères actuels
  filterEvents(events) {
    return events.filter((event) => {
      // Filtre par recherche textuelle
      const matchesSearch =
        !this.state.searchTerm ||
        event.title
          .toLowerCase()
          .includes(this.state.searchTerm.toLowerCase()) ||
        event.description
          .toLowerCase()
          .includes(this.state.searchTerm.toLowerCase()) ||
        event.city.toLowerCase().includes(this.state.searchTerm.toLowerCase());

      // Filtre par pays
      const matchesCountry =
        !this.state.country ||
        this.state.country === "all" ||
        event.country === this.state.country;

      // Filtre par catégorie
      const matchesCategory =
        !this.state.category ||
        this.state.category === "all" ||
        event.category === this.state.category;

      // Filtre par prix minimum
      const matchesPriceMin =
        !this.state.priceMin ||
        event.priceValue >= parseFloat(this.state.priceMin);

      // Filtre par prix maximum
      const matchesPriceMax =
        !this.state.priceMax ||
        event.priceValue <= parseFloat(this.state.priceMax);

      // Filtre par date (si implémenté plus tard)
      let matchesDateFrom = true;
      let matchesDateTo = true;

      if (this.state.dateFrom && event.date) {
        const eventDate = this.parseDate(event.date);
        const fromDate = new Date(this.state.dateFrom);
        matchesDateFrom = eventDate >= fromDate;
      }

      if (this.state.dateTo && event.date) {
        const eventDate = this.parseDate(event.date);
        const toDate = new Date(this.state.dateTo);
        matchesDateTo = eventDate <= toDate;
      }

      return (
        matchesSearch &&
        matchesCountry &&
        matchesCategory &&
        matchesPriceMin &&
        matchesPriceMax &&
        matchesDateFrom &&
        matchesDateTo
      );
    });
  },

  // Filtrer avec des critères personnalisés (pour compatibilité)
  filterEventsCustom(events, searchTerm, country, category) {
    return events.filter((event) => {
      const matchesSearch =
        !searchTerm ||
        event.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        event.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
        event.city.toLowerCase().includes(searchTerm.toLowerCase());

      const matchesCountry =
        !country || country === "all" || event.country === country;
      const matchesCategory =
        !category || category === "all" || event.category === category;

      return matchesSearch && matchesCountry && matchesCategory;
    });
  },

  // Obtenir les pays uniques depuis une liste d'événements
  // Obtenir les pays uniques depuis une liste d'événements
  // Note: Non utilisé pour le filtre principal (qui utilise europeanCountries)
  // Conservé pour d'autres usages potentiels (statistiques, analytics, etc.)
  getUniqueCountries(events) {
    return [...new Set(events.map((e) => e.country))].sort();
  },

  // Obtenir les catégories uniques depuis une liste d'événements
  getUniqueCategories(events) {
    return [...new Set(events.map((e) => e.category))].sort();
  },

  // Obtenir les villes uniques depuis une liste d'événements
  getUniqueCities(events) {
    return [...new Set(events.map((e) => e.city))].sort();
  },

  // Populer un select avec des options
  populateSelect(selectId, options, defaultText = "Tous") {
    const select = document.getElementById(selectId);
    if (!select) return;

    // Vider le select sauf la première option
    select.innerHTML = `<option value="all">${defaultText}</option>`;

    // Ajouter les nouvelles options
    options.forEach((option) => {
      const optionElement = document.createElement("option");
      optionElement.value = option;
      optionElement.textContent = option;
      select.appendChild(optionElement);
    });
  },

  // Populer tous les filtres à partir d'une liste d'événements
  populateAllFilters(events, config = {}) {
    const defaultConfig = {
      countrySelectId: "countrySelect",
      categorySelectId: "categorySelect",
      countryText: "Tous les pays",
      categoryText: "Toutes les catégories",
    };

    const finalConfig = { ...defaultConfig, ...config };

    // Debug: Vérifier que europeanCountries est bien chargé
    console.log(
      "Nombre de pays européens disponibles:",
      europeanCountries.length,
    );

    // Populer le select des pays avec TOUS les pays européens
    if (document.getElementById(finalConfig.countrySelectId)) {
      this.populateSelect(
        finalConfig.countrySelectId,
        europeanCountries,
        finalConfig.countryText,
      );
    }

    // Populer le select des catégories
    if (document.getElementById(finalConfig.categorySelectId)) {
      const categories = this.getUniqueCategories(events);
      this.populateSelect(
        finalConfig.categorySelectId,
        categories,
        finalConfig.categoryText,
      );
    }
  },

  // Attacher les event listeners aux éléments de filtres
  attachFilterListeners(config = {}, onFilterChange = null) {
    const defaultConfig = {
      searchInputId: "searchInput",
      countrySelectId: "countrySelect",
      categorySelectId: "categorySelect",
      priceMinId: "priceMin",
      priceMaxId: "priceMax",
      dateFromId: "dateFrom",
      dateToId: "dateTo",
    };

    const finalConfig = { ...defaultConfig, ...config };
    const callback = onFilterChange || (() => {});

    // Event listener pour la recherche
    const searchInput = document.getElementById(finalConfig.searchInputId);
    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        this.updateFilter("searchTerm", e.target.value);
        callback();
      });
    }

    // Event listener pour le pays
    const countrySelect = document.getElementById(finalConfig.countrySelectId);
    if (countrySelect) {
      countrySelect.addEventListener("change", (e) => {
        this.updateFilter("country", e.target.value);
        callback();
      });
    }

    // Event listener pour la catégorie
    const categorySelect = document.getElementById(
      finalConfig.categorySelectId,
    );
    if (categorySelect) {
      categorySelect.addEventListener("change", (e) => {
        this.updateFilter("category", e.target.value);
        callback();
      });
    }

    // Event listener pour le prix minimum
    const priceMin = document.getElementById(finalConfig.priceMinId);
    if (priceMin) {
      priceMin.addEventListener("input", (e) => {
        this.updateFilter("priceMin", e.target.value);
        callback();
      });
    }

    // Event listener pour le prix maximum
    const priceMax = document.getElementById(finalConfig.priceMaxId);
    if (priceMax) {
      priceMax.addEventListener("input", (e) => {
        this.updateFilter("priceMax", e.target.value);
        callback();
      });
    }

    // Event listener pour la date de début
    const dateFrom = document.getElementById(finalConfig.dateFromId);
    if (dateFrom) {
      dateFrom.addEventListener("change", (e) => {
        this.updateFilter("dateFrom", e.target.value);
        callback();
      });
    }

    // Event listener pour la date de fin
    const dateTo = document.getElementById(finalConfig.dateToId);
    if (dateTo) {
      dateTo.addEventListener("change", (e) => {
        this.updateFilter("dateTo", e.target.value);
        callback();
      });
    }
  },

  // Obtenir le nombre de filtres actifs
  getActiveFiltersCount() {
    let count = 0;

    if (this.state.searchTerm) count++;
    if (this.state.country && this.state.country !== "all") count++;
    if (this.state.category && this.state.category !== "all") count++;
    if (this.state.priceMin) count++;
    if (this.state.priceMax) count++;
    if (this.state.dateFrom) count++;
    if (this.state.dateTo) count++;

    return count;
  },

  // Vérifier si des filtres sont actifs
  hasActiveFilters() {
    return this.getActiveFiltersCount() > 0;
  },

  // Parser une date au format DD/MM/YYYY
  parseDate(dateString) {
    const parts = dateString.split("/");
    if (parts.length === 3) {
      return new Date(parts[2], parts[1] - 1, parts[0]);
    }
    return new Date(dateString);
  },

  // Obtenir les valeurs actuelles des filtres
  getCurrentFilters() {
    return { ...this.state };
  },

  // Appliquer des filtres sauvegardés
  applyFilters(savedFilters) {
    this.state = { ...this.state, ...savedFilters };
  },
};
