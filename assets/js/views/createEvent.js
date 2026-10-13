// Vue CreateEvent - Création d'événement

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import EventManager from "../managers/EventManager.js";
import { populateCountrySelect, countryCodes } from "../utils/countries.js";
import {
  validateImageFile,
  resizeImageForUpload,
  createImagePreview,
} from "../validators/imageValidator.js";
import {
  setFieldError,
  clearFieldValidation,
} from "../validators/formValidator.js";
import { loadTemplate } from "../utils/templateLoader.js";
import { renderLoginRequiredPage } from "../components/loginModal.js";

// Métadonnées de la vue
export const meta = {
  title: "Créer un événement - MemoriaEventia",
  description:
    "Créez votre événement historique et partagez-le avec toute l'Europe",
};

// Template HTML
const templateObjects = {};

// Variables locales
let createEventForm = null;
let selectedImageFile = null;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get("isAuthenticated")) {
    // Connexion puis retour automatique sur cette page (voir loginModal.js)
    renderLoginRequiredPage(
      container,
      "Connectez-vous pour créer un événement.",
    );
    return;
  }

  // Charger le template
  Object.assign(
    templateObjects,
    await loadTemplate("assets/templates/views/createEvent.html"),
  );

  // Vérifier que le template est chargé
  if (!templateObjects["createEventView"]) {
    helpers.showToast("Erreur de chargement de la page", "error");
    return;
  }

  // Injecter le template
  const clone = templateObjects["createEventView"].cloneNode(true);
  container.innerHTML = "";
  container.appendChild(clone);

  // Peupler le select des pays dynamiquement
  const countrySelect = document.getElementById("country");
  populateCountrySelect(countrySelect);

  // Attacher les événements
  attachEventListeners();

  // Indicateur d'étapes
  initSteps();
}

// Champs obligatoires de chaque étape : une étape est "complète" quand ils sont tous remplis
const stepRequiredFields = {
  "step-general": ["title", "description", "category", "imageEvent"],
  "step-location": ["country", "city", "postalCode", "address"],
  "step-datetime": ["date", "time"],
  // Places et tarif : tout est optionnel, pas de coche
  "step-places": [],
};

function isFieldFilled(fieldId) {
  const field = document.getElementById(fieldId);
  if (!field) return false;
  if (field.type === "file") return field.files.length > 0;
  return field.value.trim() !== "";
}

function updateSteps() {
  document.querySelectorAll(".create-step").forEach((stepButton) => {
    const fields = stepRequiredFields[stepButton.dataset.target] || [];
    const isComplete = fields.length > 0 && fields.every(isFieldFilled);
    const number = stepButton.querySelector(".create-step-number");

    stepButton.classList.toggle("is-complete", isComplete);
    if (number) {
      // ✓ quand l'étape est complète, sinon son numéro
      const index = Array.from(
        document.querySelectorAll(".create-step"),
      ).indexOf(stepButton);
      number.textContent = isComplete ? "✓" : String(index + 1);
    }
  });
}

function initSteps() {
  // Clic sur une étape : défilement vers la section correspondante
  document.querySelectorAll(".create-step").forEach((stepButton) => {
    stepButton.addEventListener("click", () => {
      document
        .getElementById(stepButton.dataset.target)
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });

  const form = document.getElementById("createEventForm");
  if (form) {
    form.addEventListener("input", updateSteps);
    form.addEventListener("change", updateSteps);
  }

  updateSteps();
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Nettoyer les event listeners
  if (createEventForm) {
    createEventForm.removeEventListener("submit", handleSubmit);
  }

  const btnCancel = document.getElementById("btnCancel");
  if (btnCancel) {
    btnCancel.removeEventListener("click", handleCancel);
  }

  const imageInput = document.getElementById("imageEvent");
  if (imageInput) {
    imageInput.removeEventListener("change", handleImageSelect);
  }

  const removeImageBtn = document.getElementById("removeImage");
  if (removeImageBtn) {
    removeImageBtn.removeEventListener("click", handleRemoveImage);
  }

  // Réinitialiser les variables
  selectedImageFile = null;
}

// Attacher les event listeners
function attachEventListeners() {
  createEventForm = document.getElementById("createEventForm");
  const btnCancel = document.getElementById("btnCancel");
  const imageInput = document.getElementById("imageEvent");
  const removeImageBtn = document.getElementById("removeImage");

  if (createEventForm) {
    createEventForm.addEventListener("submit", handleSubmit);
  }

  if (btnCancel) {
    btnCancel.addEventListener("click", handleCancel);
  }

  if (imageInput) {
    imageInput.addEventListener("change", handleImageSelect);
  }

  if (removeImageBtn) {
    removeImageBtn.addEventListener("click", handleRemoveImage);
  }
}

// Mapping des clés d'erreur backend vers les IDs frontend
const errorFieldMapping = {
  title: "title",
  description: "description",
  category: "category",
  image_event: "imageEvent",
  country: "country",
  city: "city",
  postal_code: "postalCode",
  address: "address",
  date: "date",
  time: "time",
  ticket_quantity: "ticketQuantity",
};

// Afficher les erreurs de validation sur les champs
function displayEventErrors(errors) {
  // D'abord, nettoyer toutes les validations
  clearAllValidation();

  // Si pas d'erreurs, on s'arrête
  if (!errors || Object.keys(errors).length === 0) {
    return;
  }

  // Appliquer les erreurs sur les champs correspondants
  Object.keys(errors).forEach((backendKey) => {
    const frontendKey = errorFieldMapping[backendKey];
    if (frontendKey) {
      const input = document.getElementById(frontendKey);
      const errorDiv = document.getElementById(`${frontendKey}Error`);

      if (input && errorDiv) {
        setFieldError(input, errorDiv, errors[backendKey]);
      }
    }
  });
}

// Réinitialiser toutes les validations du formulaire
function clearAllValidation() {
  Object.values(errorFieldMapping).forEach((fieldId) => {
    const input = document.getElementById(fieldId);
    const errorDiv = document.getElementById(`${fieldId}Error`);

    if (input && errorDiv) {
      clearFieldValidation(input, errorDiv);
    }
  });
}

// Fonction pour uploader l'image
async function uploadImage(file, token) {
  try {
    const formData = new FormData();
    formData.append("image", file);
    // Le serveur n'accepte l'envoi que pour un utilisateur connecté
    formData.append("token", token);

    const apiUrl =
      window.__APP_CONFIG__?.API_URL ||
      "https://memoriaeventia.com/BackEnd/Api";
    const response = await fetch(`${apiUrl}/uploadImageApi.php`, {
      method: "POST",
      body: formData,
    });

    return await response.json();
  } catch (error) {
    return {
      success: false,
      message: "Erreur de connexion au serveur",
    };
  }
}

// Fonction pour géocoder une adresse (Nominatim OpenStreetMap)
// Cascade du plus précis au plus large : rue+cp+ville+pays -> cp+ville+pays -> ville+pays
async function geocodeAddress(address, city, postalCode, country) {
  const countrycodes = countryCodes[country] || "";

  const attempts = [
    { street: address, postalcode: postalCode, city, countrycodes },
    { postalcode: postalCode, city, countrycodes },
    { city, countrycodes },
  ];

  for (let i = 0; i < attempts.length; i++) {
    const coords = await tryGeocode(attempts[i]);
    if (coords) {
      return { success: true, precise: i === 0, ...coords };
    }
  }

  return { success: false, precise: false, latitude: null, longitude: null };
}

// Tente un géocodage structuré Nominatim avec les paramètres fournis
async function tryGeocode(params) {
  const query = new URLSearchParams({ format: "json", limit: "1" });
  if (params.street) query.set("street", params.street);
  if (params.city) query.set("city", params.city);
  if (params.postalcode) query.set("postalcode", params.postalcode);
  if (params.countrycodes) query.set("countrycodes", params.countrycodes);

  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?${query.toString()}`,
    );
    const data = await response.json();

    if (data && data.length > 0) {
      return {
        latitude: parseFloat(data[0].lat),
        longitude: parseFloat(data[0].lon),
      };
    }
  } catch (error) {
    // Échec réseau : on laisse la tentative suivante prendre le relais
  }

  return null;
}

// Gérer la suppression d'image
function handleRemoveImage() {
  selectedImageFile = null;

  const imageInput = document.getElementById("imageEvent");
  const previewContainer = document.getElementById("imagePreview");
  const previewImg = document.getElementById("previewImg");

  if (imageInput) {
    imageInput.value = "";
  }

  if (previewImg) {
    previewImg.src = "";
  }

  if (previewContainer) {
    previewContainer.style.display = "none";
  }

  // Vider le champ ne déclenche pas d'événement : on met l'indicateur d'étapes à jour
  updateSteps();
}

// Gérer l'annulation
function handleCancel() {
  window.router.navigate("./");
}

// Gérer la sélection d'image
async function handleImageSelect(e) {
  const file = e.target.files[0];

  if (!file) return;

  // Valider le fichier d'origine (type, signature) : il peut être lourd, il ne sera pas envoyé tel quel
  const validation = await validateImageFile(file, {
    maxSize: 25 * 1024 * 1024,
  });

  if (!validation.valid) {
    helpers.showToast(validation.error, "error");
    e.target.value = "";
    return;
  }

  // Réduire l'image dans le navigateur : c'est ce fichier léger qui sera envoyé
  const resized = await resizeImageForUpload(file);

  if (!resized.file) {
    helpers.showToast(resized.error, "error");
    e.target.value = "";
    return;
  }

  // Stocker le fichier réduit
  selectedImageFile = resized.file;

  // Afficher l'aperçu avec la fonction utilitaire
  try {
    const previewUrl = await createImagePreview(resized.file);

    const previewContainer = document.getElementById("imagePreview");
    const previewImg = document.getElementById("previewImg");

    if (previewImg) {
      previewImg.src = previewUrl;
    }

    if (previewContainer) {
      previewContainer.style.display = "block";
    }
  } catch (error) {
    helpers.showToast("Erreur lors de la création de l'aperçu", "error");
    e.target.value = "";
  }
}

// Gérer la soumission du formulaire
async function handleSubmit(e) {
  e.preventDefault();

  // Nettoyer toutes les erreurs de validation précédentes
  clearAllValidation();

  // Vérifier qu'une image a été sélectionnée
  if (!selectedImageFile) {
    const imageInput = document.getElementById("imageEvent");
    const imageError = document.getElementById("imageEventError");
    setFieldError(
      imageInput,
      imageError,
      "Veuillez sélectionner une image pour votre événement",
    );
    helpers.showToast(
      "Veuillez sélectionner une image pour votre événement",
      "error",
    );
    return;
  }

  // Désactiver le bouton de soumission
  const submitBtn = createEventForm.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Géolocalisation...';
  }

  // Étape 1 : Géocoder l'adresse pour obtenir latitude/longitude
  const address = document.getElementById("address")?.value || "";
  const city = document.getElementById("city").value;
  const postalCode = document.getElementById("postalCode")?.value || "";
  const country = document.getElementById("country").value;

  const geocodeResult = await geocodeAddress(
    address,
    city,
    postalCode,
    country,
  );

  if (!geocodeResult.success) {
    helpers.showToast(
      "Adresse non localisée : l'événement sera créé mais n'apparaîtra pas sur la carte. Vous pourrez la corriger plus tard.",
      "warning",
    );
  } else if (!geocodeResult.precise) {
    helpers.showToast(
      "Adresse localisée approximativement (ville). Vous pouvez préciser l'adresse plus tard si besoin.",
      "info",
    );
  }

  // Étape 2 : Créer l'événement SANS image
  if (submitBtn) {
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Création de l\'événement...';
  }

  // Tous les événements sont gratuits (système de réservations sans paiement)
  const ticketQuantityInput = document.getElementById("ticketQuantity");
  const eventData = {
    title: document.getElementById("title").value,
    description: document.getElementById("description").value,
    country: country,
    city: city,
    postal_code: postalCode,
    address: address,
    latitude: geocodeResult.latitude || null,
    longitude: geocodeResult.longitude || null,
    date: document.getElementById("date").value,
    time: document.getElementById("time").value,
    category: document.getElementById("category").value,
    image_event: null, // Pas d'image pour le moment
    ticket_quantity: ticketQuantityInput?.value || 0,
  };

  // Appel API pour créer l'événement
  const token = auth.getToken ? auth.getToken() : null;
  const createResult = await EventManager.create(eventData, token);

  // Si la création échoue, afficher les erreurs et s'arrêter
  if (!createResult.success) {
    // Afficher les erreurs par champ
    if (createResult.errors) {
      displayEventErrors(createResult.errors);
    }

    // Toast avec le message d'erreur général
    helpers.showToast(
      createResult.message || "Erreur lors de la création de l'événement",
      "error",
    );

    // Réactiver le bouton
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML =
        '<i class="bi bi-check-circle"></i> Créer l\'événement';
    }
    return; // STOP ICI - pas d'upload d'image
  }

  // Étape 3 : Upload de l'image (événement créé avec succès)
  if (submitBtn) {
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Upload de l\'image...';
  }

  const uploadResult = await uploadImage(selectedImageFile, token);

  if (!uploadResult.success) {
    // L'événement est créé mais sans image
    helpers.showToast(
      "Événement créé mais l'image n'a pas pu être uploadée. Vous pouvez la modifier plus tard.",
      "warning",
    );
    setTimeout(() => {
      window.router.navigate("/profile?section=events");
    }, 2000);
    return;
  }

  // Étape 4 : Mettre à jour l'événement avec l'image
  if (submitBtn) {
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Finalisation...';
  }

  const eventId = createResult.data?.id;
  if (eventId) {
    // Envoyer tous les champs + l'image pour passer la validation backend
    const updateData = {
      ...eventData, // Tous les champs originaux
      image_event: uploadResult.data.filename, // Ajout de l'image
    };

    const updateResult = await EventManager.update(eventId, updateData, token);

    if (!updateResult.success) {
      // L'événement est créé mais l'image n'a pas pu être associée
      helpers.showToast(
        "Événement créé mais l'image n'a pas pu être associée. Vous pouvez la modifier plus tard.",
        "warning",
      );
      setTimeout(() => {
        window.router.navigate("/profile?section=events");
      }, 2000);
      return;
    }
  }

  // Succès complet
  helpers.showToast("Événement créé avec succès !", "success");
  setTimeout(() => {
    window.router.navigate("/profile?section=events");
  }, 1000);
}

// Export par défaut
export default { mount, unmount, meta };
