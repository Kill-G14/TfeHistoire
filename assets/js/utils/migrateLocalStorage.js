/**
 * Script de migration localStorage
 * Migre les anciennes clés eurofetes_* vers memoriaeventia_*
 * À exécuter une seule fois lors du démarrage de l'application
 */

export function migrateLocalStorage() {
  // Vérifier si la migration a déjà été effectuée
  if (localStorage.getItem('memoriaeventia_migrated')) {
    return
  }

  console.log('🔄 Migration du localStorage en cours...')

  // Migrer les clés du site public
  const oldUser = localStorage.getItem('eurofetes_user')
  const oldToken = localStorage.getItem('eurofetes_token')

  if (oldUser) {
    localStorage.setItem('memoriaeventia_user', oldUser)
    localStorage.removeItem('eurofetes_user')
    console.log('✅ Clé eurofetes_user migrée')
  }

  if (oldToken) {
    localStorage.setItem('memoriaeventia_token', oldToken)
    localStorage.removeItem('eurofetes_token')
    console.log('✅ Clé eurofetes_token migrée')
  }

  // Migrer les clés admin (localStorage)
  const oldAdminToken = localStorage.getItem('admin_auth_token')
  const oldAdminUser = localStorage.getItem('admin_user')
  const oldAdminTokenAlt = localStorage.getItem('adminToken')

  if (oldAdminToken) {
    localStorage.setItem('memoriaeventia_admin_token', oldAdminToken)
    localStorage.removeItem('admin_auth_token')
    console.log('✅ Clé admin_auth_token migrée')
  }

  if (oldAdminUser) {
    localStorage.setItem('memoriaeventia_admin_user', oldAdminUser)
    localStorage.removeItem('admin_user')
    console.log('✅ Clé admin_user migrée')
  }

  if (oldAdminTokenAlt) {
    localStorage.setItem('memoriaeventia_admin_token', oldAdminTokenAlt)
    localStorage.removeItem('adminToken')
    console.log('✅ Clé adminToken migrée')
  }

  // Migrer les clés admin (sessionStorage)
  const oldSessionAdminToken = sessionStorage.getItem('admin_auth_token')
  const oldSessionAdminUser = sessionStorage.getItem('admin_user')

  if (oldSessionAdminToken) {
    sessionStorage.setItem('memoriaeventia_admin_token', oldSessionAdminToken)
    sessionStorage.removeItem('admin_auth_token')
    console.log('✅ Clé session admin_auth_token migrée')
  }

  if (oldSessionAdminUser) {
    sessionStorage.setItem('memoriaeventia_admin_user', oldSessionAdminUser)
    sessionStorage.removeItem('admin_user')
    console.log('✅ Clé session admin_user migrée')
  }

  // Marquer la migration comme effectuée
  localStorage.setItem('memoriaeventia_migrated', 'true')
  console.log('✅ Migration localStorage terminée')
}
