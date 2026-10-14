// Vue de vérification d'un billet : /ticket/verify?code=MEM-000018-1-XXXXXXXXXXXX
// Page publique, ouverte en scannant le QR code d'un billet.

import TicketManager from "../managers/TicketManager.js";
import { helpers } from "../utils/helpers.js";

// Métadonnées de la vue
export const meta = {
  title: "Vérification d'un billet - MemoriaEventia",
  description: "Vérifiez la validité d'un billet MemoriaEventia",
  noindex: true,
};

// Crée un élément avec un texte (textContent : aucune donnée n'est injectée en HTML)
function createElement(tag, className, text = "") {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text) element.textContent = text;
  return element;
}

// Ligne « libellé : valeur » du récapitulatif d'un billet valide
function createDetailRow(label, value) {
  const row = createElement("div", "d-flex justify-content-between border-bottom py-2");
  row.append(
    createElement("span", "text-muted", label),
    createElement("span", "fw-bold text-end", value),
  );
  return row;
}

// Résultat de la vérification (valide ou non)
function renderResult(resultContainer, result) {
  resultContainer.replaceChildren();

  if (!result.success) {
    const alert = createElement(
      "div",
      "alert alert-warning mb-0",
      result.message || "Vérification impossible, réessayez plus tard.",
    );
    alert.setAttribute("role", "alert");
    resultContainer.appendChild(alert);
    return;
  }

  const data = result.data;
  const card = createElement(
    "div",
    `card border-2 ${data.valid ? "border-success" : "border-danger"}`,
  );
  card.setAttribute("role", "status");

  const body = createElement("div", "card-body text-center p-4");

  const icon = createElement(
    "i",
    `bi ${data.valid ? "bi-patch-check-fill text-success" : "bi-x-octagon-fill text-danger"} d-block mb-2`,
  );
  icon.style.fontSize = "4rem";
  icon.setAttribute("aria-hidden", "true");

  body.append(
    icon,
    createElement(
      "h2",
      `h3 mb-1 ${data.valid ? "text-success" : "text-danger"}`,
      data.valid ? "Billet valide" : "Billet invalide",
    ),
  );

  if (!data.valid) {
    body.appendChild(createElement("p", "mb-0", data.reason));
    card.appendChild(body);
    resultContainer.appendChild(card);
    return;
  }

  body.appendChild(
    createElement("p", "text-muted mb-3", `Place ${data.seat_number} sur ${data.seat_total}`),
  );

  const details = createElement("div", "text-start");
  details.append(
    createDetailRow("Événement", data.event_title),
    createDetailRow(
      "Date",
      `${helpers.formatDate(data.event_date)} à ${data.event_time}`,
    ),
    createDetailRow("Ville", data.event_city),
    createDetailRow("Organisateur", data.organizer_name || "-"),
    createDetailRow("Titulaire", data.holder_initials),
    createDetailRow("Référence", data.reference),
  );

  body.appendChild(details);
  card.appendChild(body);
  resultContainer.appendChild(card);
}

// Lance la vérification d'un code et affiche le résultat
async function verify(code, resultContainer, button) {
  if (!code.trim()) {
    renderResult(resultContainer, {
      success: true,
      data: { valid: false, reason: "Saisissez le code inscrit sous le QR code." },
    });
    return;
  }

  if (button) button.disabled = true;
  resultContainer.replaceChildren(createElement("p", "text-muted text-center", "Vérification en cours…"));

  const result = await TicketManager.verifyTicket(code.trim());

  if (button) button.disabled = false;
  renderResult(resultContainer, result);
}

// Fonction mount (appelée lors du chargement de la vue)
export async function mount(container, params) {
  container.replaceChildren();

  const wrapper = createElement("div", "container py-5");
  wrapper.style.maxWidth = "560px";

  wrapper.append(
    createElement("h1", "page-title text-center mb-2", "Vérifier un billet"),
    createElement(
      "p",
      "text-center text-muted mb-4",
      "Scannez le QR code d'un billet ou saisissez le code inscrit en dessous.",
    ),
  );

  // Saisie manuelle du code
  const form = createElement("form", "d-flex gap-2 mb-4");
  form.setAttribute("novalidate", "");

  const input = createElement("input", "form-control");
  input.type = "text";
  input.id = "ticketCodeInput";
  input.placeholder = "MEM-000018-1-XXXXXXXXXXXX";
  input.maxLength = 120;
  input.setAttribute("aria-label", "Code du billet");
  input.autocomplete = "off";

  const button = createElement("button", "btn btn-primary");
  button.type = "submit";
  button.textContent = "Vérifier";

  form.append(input, button);
  wrapper.appendChild(form);

  const resultContainer = createElement("div");
  resultContainer.setAttribute("aria-live", "polite");
  wrapper.appendChild(resultContainer);

  container.appendChild(wrapper);

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    verify(input.value, resultContainer, button);
  });

  // Arrivée par scan du QR : le code est dans le lien, on vérifie tout de suite
  const codeFromUrl = new URLSearchParams(window.location.search).get("code");
  if (codeFromUrl) {
    input.value = codeFromUrl;
    await verify(codeFromUrl, resultContainer, button);
  }
}

// Fonction unmount (appelée avant de quitter la vue)
export async function unmount() {}

// Export par défaut
export default { mount, unmount, meta };
