/**
 * Script de migration localStorage
 * Migre les anciennes clés eurofetes_* vers memoriaeventia_*
 * À exécuter une seule fois lors du démarrage de l'application
 */

export function migrateLocalStorage() {
  // Vérifier si la migration a déjà été effectuée
  if (localStorage.getItem("memoriaeventia_migrated")) {
    return;
  }

  // Migrer les clés du site public
  const oldUser = localStorage.getItem("eurofetes_user");
  const oldToken = localStorage.getItem("eurofetes_token");

  if (oldUser) {
    localStorage.setItem("memoriaeventia_user", oldUser);
    localStorage.removeItem("eurofetes_user");
  }

  if (oldToken) {
    localStorage.setItem("memoriaeventia_token", oldToken);
    localStorage.removeItem("eurofetes_token");
  }

  // Migrer les clés admin (localStorage)
  const oldAdminToken = localStorage.getItem("admin_auth_token");
  const oldAdminUser = localStorage.getItem("admin_user");
  const oldAdminTokenAlt = localStorage.getItem("adminToken");

  if (oldAdminToken) {
    localStorage.setItem("memoriaeventia_admin_token", oldAdminToken);
    localStorage.removeItem("admin_auth_token");
  }

  if (oldAdminUser) {
    localStorage.setItem("memoriaeventia_admin_user", oldAdminUser);
    localStorage.removeItem("admin_user");
  }

  if (oldAdminTokenAlt) {
    localStorage.setItem("memoriaeventia_admin_token", oldAdminTokenAlt);
    localStorage.removeItem("adminToken");
  }

  // Migrer les clés admin (sessionStorage)
  const oldSessionAdminToken = sessionStorage.getItem("admin_auth_token");
  const oldSessionAdminUser = sessionStorage.getItem("admin_user");

  if (oldSessionAdminToken) {
    sessionStorage.setItem("memoriaeventia_admin_token", oldSessionAdminToken);
    sessionStorage.removeItem("admin_auth_token");
  }

  if (oldSessionAdminUser) {
    sessionStorage.setItem("memoriaeventia_admin_user", oldSessionAdminUser);
    sessionStorage.removeItem("admin_user");
  }

  // Marquer la migration comme effectuée
  localStorage.setItem("memoriaeventia_migrated", "true");
}
