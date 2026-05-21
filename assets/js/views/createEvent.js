// Vue CreateEvent - Création d'événement

import { auth } from "../utils/auth.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import EventManager from "../managers/EventManager.js";
import { populateCountrySelect } from "../utils/countries.js";
import {
  validateImageFile,
  createImagePreview,
} from "../validators/imageValidator.js";

// Métadonnées de la vue
export const meta = {
  title: "Créer un événement - MemoriaEventia",
  description:
    "Créez votre événement historique et partagez-le avec toute l'Europe",
};

// Template HTML
const templateObjects = {};

async function loadTemplate(path) {
  try {
    const response = await fetch(path);
    if (!response.ok) {
      throw new Error(`Erreur ${response.status}: ${response.statusText}`);
    }

    const htmlContent = await response.text();
    const parser = new DOMParser();
    const templateDoc = parser.parseFromString(htmlContent, "text/html");
    const templates = templateDoc.querySelectorAll("template");

    if (templates.length === 0) {
      throw new Error("Aucun template trouvé dans le fichier");
    }

    // Vider l'objet templateObjects avant de le remplir
    Object.keys(templateObjects).forEach((key) => delete templateObjects[key]);

    templates.forEach((template) => {
      const templateId = template.id;
      templateObjects[templateId] = template.content;
    });
  } catch (error) {
    throw error;
  }
}

// Variables locales
let createEventForm = null;
let selectedImageFile = null;
let uploadedImageFilename = null;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier si l'utilisateur est connecté
  if (!appState.get("isAuthenticated")) {
    helpers.showToast(
      "Vous devez être connecté pour créer un événement",
      "error",
    );
    setTimeout(() => {
      window.router.navigate("./");
    }, 1500);
    return;
  }

  // Charger le template
  await loadTemplate("assets/templates/views/createEvent.html");

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
  uploadedImageFilename = null;
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

// Fonction pour uploader l'image
async function uploadImage(file) {
  try {
    const formData = new FormData();
    formData.append("image", file);

    const response = await fetch(
      "https://memoriaeventia.com/BackEnd/Api/uploadImageApi.php",
      {
        method: "POST",
        body: formData,
      },
    );

    return await response.json();
  } catch (error) {
    return {
      success: false,
      message: "Erreur de connexion au serveur",
    };
  }
}

// Fonction pour géocoder une adresse (Nominatim OpenStreetMap)
async function geocodeAddress(address, city, postalCode, country) {
  try {
    // Construire l'adresse complète pour le géocodage
    const addressParts = [];
    if (address) addressParts.push(address);
    if (postalCode) addressParts.push(postalCode);
    if (city) addressParts.push(city);
    if (country) addressParts.push(country);

    const fullAddress = addressParts.join(", ");

    // Encoder l'adresse pour l'URL
    const encodedAddress = encodeURIComponent(fullAddress);

    // Appeler l'API Nominatim (avec User-Agent requis)
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodedAddress}&limit=1`,
      {
        headers: {
          "User-Agent": "MemoriaEventia/1.0",
        },
      },
    );

    const data = await response.json();

    // Si on a des résultats, retourner les coordonnées
    if (data && data.length > 0) {
      return {
        success: true,
        latitude: parseFloat(data[0].lat),
        longitude: parseFloat(data[0].lon),
      };
    } else {
      // Pas de résultats trouvés, mais on continue sans erreur
      return {
        success: false,
        latitude: null,
        longitude: null,
      };
    }
  } catch (error) {
    // En cas d'erreur, on continue sans coordonnées
    return {
      success: false,
      latitude: null,
      longitude: null,
    };
  }
}

// Gérer la suppression d'image
function handleRemoveImage() {
  selectedImageFile = null;
  uploadedImageFilename = null;

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
}

// Gérer l'annulation
function handleCancel() {
  window.router.navigate("./");
}

// Gérer la sélection d'image
async function handleImageSelect(e) {
  const file = e.target.files[0];

  if (!file) return;

  // Valider le fichier image avec le validator dédié
  const validation = await validateImageFile(file);

  if (!validation.valid) {
    helpers.showToast(validation.error, "error");
    e.target.value = "";
    return;
  }

  // Stocker le fichier
  selectedImageFile = file;

  // Afficher l'aperçu avec la fonction utilitaire
  try {
    const previewUrl = await createImagePreview(file);

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

  // Vérifier qu'une image a été sélectionnée
  if (!selectedImageFile) {
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
      '<i class="bi bi-hourglass-split"></i> Upload de l\'image...';
  }

  // Étape 1 : Upload de l'image
  const uploadResult = await uploadImage(selectedImageFile);

  if (!uploadResult.success) {
    helpers.showToast(
      uploadResult.message || "Erreur lors de l'upload de l'image",
      "error",
    );

    // Réactiver le bouton
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML =
        '<i class="bi bi-check-circle"></i> Créer l\'événement';
    }
    return;
  }

  uploadedImageFilename = uploadResult.data.filename;

  // Étape 2 : Géocoder l'adresse pour obtenir latitude/longitude
  if (submitBtn) {
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Géolocalisation de l\'adresse...';
  }

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

  // Étape 3 : Créer l'événement avec le nom de l'image et les coordonnées
  if (submitBtn) {
    submitBtn.innerHTML =
      '<i class="bi bi-hourglass-split"></i> Création de l\'événement...';
  }

  // Tous les événements sont gratuits (système de réservations sans paiement)
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
    is_free: true,
    image_event: uploadedImageFilename,
    ticket_price: 0,
    ticket_quantity: 0,
  };

  // Appel API pour créer l'événement
  const token = auth.getToken ? auth.getToken() : null;
  const result = await EventManager.create(eventData, token);

  if (result.success) {
    helpers.showToast("Événement créé avec succès !", "success");
    setTimeout(() => {
      window.router.navigate("./");
    }, 1000);
  } else {
    helpers.showToast(
      result.message || "Erreur lors de la création de l'événement",
      "error",
    );
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Créer l\'événement';
    }
  }
}

// Export par défaut
export default { mount, unmount, meta };
