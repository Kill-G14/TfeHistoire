// Métadonnées de la vue
export const meta = {
  title: "Paiement réussi - MemoriaEventia",
  description: "Votre paiement a été effectué avec succès",
};

// Template HTML
const template = `
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">
        <div class="card shadow-sm">
          <div class="card-body py-5">
            <div class="mb-4">
              <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            </div>
            <h1 class="text-success mb-3">Paiement réussi !</h1>
            <p class="lead mb-4">Votre commande a été confirmée</p>
            
            <div class="alert alert-info" id="orderInfo">
              <div class="spinner-border spinner-border-sm me-2" role="status"></div>
              Chargement des informations de paiement...
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
              <a href="/account/orders" data-link class="btn btn-primary btn-lg">
                <i class="bi bi-receipt"></i> Voir mes commandes
              </a>
              <a href="/" data-link class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-house"></i> Retour à l'accueil
              </a>
            </div>

            <div class="mt-4">
              <h5 class="mb-3">Prochaines étapes</h5>
              <div class="row g-3 text-start">
                <div class="col-md-6">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-envelope-check text-primary me-2" style="font-size: 1.5rem;"></i>
                    <div>
                      <strong>Email de confirmation</strong>
                      <p class="text-muted small mb-0">Un email de confirmation vous a été envoyé</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-file-pdf text-danger me-2" style="font-size: 1.5rem;"></i>
                    <div>
                      <strong>Facture PDF</strong>
                      <p class="text-muted small mb-0">Votre facture est disponible dans vos commandes</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-qr-code text-success me-2" style="font-size: 1.5rem;"></i>
                    <div>
                      <strong>Billets électroniques</strong>
                      <p class="text-muted small mb-0">Vos billets avec QR code sont prêts</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-calendar-event text-info me-2" style="font-size: 1.5rem;"></i>
                    <div>
                      <strong>Événement</strong>
                      <p class="text-muted small mb-0">Présentez vos billets le jour de l'événement</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
`;

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  // Injecter le template
  container.innerHTML = template;

  // Récupérer le session_id depuis l'URL
  const urlParams = new URLSearchParams(window.location.search);
  const sessionId = urlParams.get("session_id");

  const orderInfoDiv = document.getElementById("orderInfo");

  if (sessionId) {
    // Afficher le session ID
    orderInfoDiv.innerHTML = `
      <p class="mb-1"><strong>ID de session Stripe :</strong></p>
      <p class="font-monospace small mb-0">${sessionId}</p>
      <p class="text-muted small mt-2 mb-0">
        <i class="bi bi-info-circle"></i> 
        Votre paiement est en cours de traitement par Stripe. 
        Vous recevrez un email de confirmation dans quelques instants.
      </p>
    `;
  } else {
    orderInfoDiv.innerHTML = `
      <p class="mb-0">
        <i class="bi bi-check-circle"></i> 
        Votre paiement a été effectué avec succès
      </p>
    `;
  }

  // Nettoyer l'URL (enlever le session_id)
  if (sessionId) {
    window.history.replaceState(
      {},
      document.title,
      window.location.pathname + "#/payment/success",
    );
  }
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Rien à nettoyer pour cette vue
}

// Export par défaut
export default { mount, unmount, meta };
