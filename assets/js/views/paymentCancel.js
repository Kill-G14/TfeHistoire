// Métadonnées de la vue
export const meta = {
  title: "Paiement annulé - MemoriaEventia",
  description: "Votre paiement a été annulé",
};

// Template HTML
const template = `
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">
        <div class="card shadow-sm">
          <div class="card-body py-5">
            <div class="mb-4">
              <i class="bi bi-x-circle-fill text-warning" style="font-size: 5rem;"></i>
            </div>
            <h1 class="text-warning mb-3">Paiement annulé</h1>
            <p class="lead mb-4">Votre paiement n'a pas été effectué</p>
            
            <div class="alert alert-warning">
              <i class="bi bi-info-circle"></i>
              Vous avez annulé le processus de paiement. Aucun montant n'a été débité.
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
              <a href="cart" data-link class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-left"></i> Retour au panier
              </a>
              <a href="./" data-link class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-house"></i> Retour à l'accueil
              </a>
            </div>

            <div class="mt-5 text-start">
              <h5 class="mb-3">Pourquoi mon paiement a-t-il été annulé ?</h5>
              <ul class="list-unstyled">
                <li class="mb-2">
                  <i class="bi bi-check-circle text-muted me-2"></i>
                  Vous avez cliqué sur "Annuler" pendant le processus de paiement
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle text-muted me-2"></i>
                  Vous avez fermé la fenêtre de paiement
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle text-muted me-2"></i>
                  La session de paiement a expiré
                </li>
              </ul>
            </div>

            <div class="mt-4 text-start">
              <h5 class="mb-3">Que faire maintenant ?</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="card border-primary">
                    <div class="card-body">
                      <h6 class="card-title">
                        <i class="bi bi-arrow-clockwise text-primary"></i> 
                        Réessayer le paiement
                      </h6>
                      <p class="card-text small text-muted">
                        Votre panier est toujours disponible. Vous pouvez réessayer le paiement quand vous le souhaitez.
                      </p>
                      <a href="cart" data-link class="btn btn-sm btn-primary">
                        Accéder au panier
                      </a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card border-secondary">
                    <div class="card-body">
                      <h6 class="card-title">
                        <i class="bi bi-question-circle text-secondary"></i> 
                        Besoin d'aide ?
                      </h6>
                      <p class="card-text small text-muted">
                        Vous rencontrez un problème ? Notre équipe est là pour vous aider.
                      </p>
                      <a href="contact" data-link class="btn btn-sm btn-outline-secondary">
                        Nous contacter
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 alert alert-info small text-start">
              <i class="bi bi-shield-check"></i>
              <strong>Paiement sécurisé</strong> - Tous nos paiements sont sécurisés par Stripe, 
              leader mondial du paiement en ligne. Vos données bancaires sont protégées et jamais stockées sur nos serveurs.
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
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {
  // Rien à nettoyer pour cette vue
}

// Export par défaut
export default { mount, unmount, meta };
