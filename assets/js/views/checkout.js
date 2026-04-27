// Imports
import CheckoutManager from "../managers/CheckoutManager.js";
import OrderManager from "../managers/OrderManager.js";
import { helpers } from "../utils/helpers.js";
import { appState } from "../store/appState.js";
import { auth } from "../utils/auth.js";

// Métadonnées de la vue
export const meta = {
  title: "Paiement - MemoriaEventia",
  description: "Finaliser votre commande",
};

// Template HTML
const template = `
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <h1 class="mb-4">Finaliser votre commande</h1>

        <!-- Résumé de la commande -->
        <div id="orderSummary" class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Résumé de la commande</h5>
          </div>
          <div class="card-body">
            <div id="orderItems"></div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Total</h5>
              <h5 class="mb-0" id="orderTotal">0.00 €</h5>
            </div>
          </div>
        </div>

        <!-- Bouton de paiement -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Paiement sécurisé avec Stripe</h5>
            <p class="text-muted mb-4">
              <i class="bi bi-shield-check"></i> Paiement 100% sécurisé par Stripe
            </p>
            
            <button id="btnCheckout" class="btn btn-primary btn-lg w-100">
              <i class="bi bi-credit-card"></i> Procéder au paiement
            </button>
            
            <div id="checkoutError" class="alert alert-danger mt-3 d-none"></div>
            <div id="checkoutLoading" class="text-center mt-3 d-none">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
              </div>
              <p class="mt-2">Redirection vers le paiement sécurisé...</p>
            </div>
          </div>
        </div>

        <div class="text-center mt-4">
          <a href="/cart" data-link class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au panier
          </a>
        </div>
      </div>
    </div>
  </div>
`;

// Variables locales de la vue
let orderId = null;
let orderData = null;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Vérifier l'authentification
  const user = appState.get("user");
  if (!user) {
    window.location.hash = "#/login";
    return;
  }

  // Récupérer l'ID de commande depuis les paramètres ou le state
  orderId = params.orderId || appState.get("currentOrderId");

  if (!orderId) {
    helpers.showToast("Aucune commande à payer", "error");
    window.location.hash = "#/cart";
    return;
  }

  // Injecter le template
  container.innerHTML = template;

  // Charger les données de la commande
  await loadOrderData();

  // Attacher les événements
  attachEventListeners();
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  orderId = null;
  orderData = null;
}

// Charger les données de la commande
async function loadOrderData() {
  const token = auth.getToken();
  if (!token) return;

  const result = await OrderManager.getById(orderId, token);

  if (result.success && result.data) {
    orderData = result.data;
    renderOrderSummary();
  } else {
    helpers.showToast(
      result.message || "Erreur lors du chargement de la commande",
      "error",
    );
    window.location.hash = "#/cart";
  }
}

// Rendre le résumé de la commande
function renderOrderSummary() {
  if (!orderData) return;

  const itemsContainer = document.getElementById("orderItems");
  const totalElement = document.getElementById("orderTotal");

  if (!itemsContainer || !totalElement) return;

  // Afficher les articles
  itemsContainer.innerHTML =
    orderData.items
      ?.map(
        (item) => `
    <div class="d-flex justify-content-between mb-2">
      <div>
        <strong>${item.ticket_name}</strong>
        <br>
        <small class="text-muted">${item.event_title}</small>
        <br>
        <small>Quantité : ${item.quantity}</small>
      </div>
      <div class="text-end">
        <div>${parseFloat(item.subtotal).toFixed(2)} €</div>
        <small class="text-muted">${parseFloat(item.unit_price).toFixed(2)} € x ${item.quantity}</small>
      </div>
    </div>
  `,
      )
      .join("") || '<p class="text-muted">Aucun article</p>';

  // Afficher le total
  totalElement.textContent = `${parseFloat(orderData.total_price).toFixed(2)} €`;
}

// Attacher les event listeners
function attachEventListeners() {
  const btnCheckout = document.getElementById("btnCheckout");

  if (btnCheckout) {
    btnCheckout.addEventListener("click", handleCheckout);
  }
}

// Gérer le clic sur le bouton de paiement
async function handleCheckout() {
  const btnCheckout = document.getElementById("btnCheckout");
  const checkoutError = document.getElementById("checkoutError");
  const checkoutLoading = document.getElementById("checkoutLoading");

  if (!orderData) {
    showError("Erreur : données de commande manquantes");
    return;
  }

  // Désactiver le bouton et afficher le loading
  btnCheckout.disabled = true;
  checkoutLoading.classList.remove("d-none");
  checkoutError.classList.add("d-none");

  const token = auth.getToken();
  if (!token) {
    showError("Veuillez vous connecter");
    btnCheckout.disabled = false;
    checkoutLoading.classList.add("d-none");
    return;
  }

  // Préparer les items pour Stripe
  const orderItems =
    orderData.items?.map((item) => ({
      name: `${item.ticket_name} - ${item.event_title}`,
      description: `Billet pour ${item.event_title}`,
      price: item.unit_price,
      quantity: item.quantity,
    })) || [];

  // Créer la session de checkout
  const result = await CheckoutManager.createCheckoutSession(
    token,
    orderId,
    orderItems,
  );

  if (result.success && result.data) {
    // Si c'est une réservation gratuite, rediriger vers la page de succès directement
    if (result.data.is_free && result.data.redirect_url) {
      window.location.href = result.data.redirect_url;
    }
    // Sinon, rediriger vers Stripe Checkout
    else if (result.data.url) {
      window.location.href = result.data.url;
    } else {
      showError("URL de paiement manquante");
      btnCheckout.disabled = false;
      checkoutLoading.classList.add("d-none");
    }
  } else {
    showError(
      result.message || "Erreur lors de la création de la session de paiement",
    );
    btnCheckout.disabled = false;
    checkoutLoading.classList.add("d-none");
  }
}

// Afficher une erreur
function showError(message) {
  const checkoutError = document.getElementById("checkoutError");
  if (checkoutError) {
    checkoutError.textContent = message;
    checkoutError.classList.remove("d-none");
  }
  helpers.showToast(message, "error");
}

// Export par défaut
export default { mount, unmount, meta };
