<?php
/**
 * Script de debug pour tester la connexion à la base de données
 * À SUPPRIMER après les tests en production !
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Test de connexion - MemoriaEventia</h1>";
echo "<hr>";

// 1. Vérifier que le fichier .env existe
echo "<h2>1. Fichier .env</h2>";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    echo "✅ Fichier .env trouvé : <code>$envPath</code><br>";
} else {
    echo "❌ Fichier .env INTROUVABLE : <code>$envPath</code><br>";
    echo "⚠️ Créez le fichier .env à partir de .env.example<br>";
}
echo "<hr>";

// 2. Charger les variables d'environnement
echo "<h2>2. Variables d'environnement</h2>";
try {
    require __DIR__ . '/../vendor/autoload.php';
    \App\Utils\EnvLoader::load();
    echo "✅ EnvLoader chargé avec succès<br>";
    
    // Afficher les variables (masquer les mots de passe)
    $dbHost = getenv('DB_HOST') ?: 'NON DÉFINI';
    $dbName = getenv('DB_NAME') ?: 'NON DÉFINI';
    $dbUser = getenv('DB_USER') ?: 'NON DÉFINI';
    $dbPassword = getenv('DB_PASSWORD');
    $dbCharset = getenv('DB_CHARSET') ?: 'NON DÉFINI';
    
    echo "<ul>";
    echo "<li><strong>DB_HOST:</strong> $dbHost</li>";
    echo "<li><strong>DB_NAME:</strong> $dbName</li>";
    echo "<li><strong>DB_USER:</strong> $dbUser</li>";
    echo "<li><strong>DB_PASSWORD:</strong> " . ($dbPassword ? str_repeat('*', strlen($dbPassword)) : 'NON DÉFINI') . "</li>";
    echo "<li><strong>DB_CHARSET:</strong> $dbCharset</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement : " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 3. Tester la connexion PDO
echo "<h2>3. Connexion à la base de données</h2>";
try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=" . getenv('DB_CHARSET');
    $pdo = new PDO(
        $dsn,
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong style='color: green;'>Connexion PDO réussie !</strong><br>";
    echo "DSN: <code>$dsn</code><br>";
    
} catch (PDOException $e) {
    echo "❌ <strong style='color: red;'>Erreur de connexion PDO</strong><br>";
    echo "Message : " . $e->getMessage() . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    exit;
}
echo "<hr>";

// 4. Tester une requête simple
echo "<h2>4. Test de requête</h2>";
try {
    // Lister les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ <strong>Tables trouvées (" . count($tables) . ") :</strong><br>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Compter les événements si la table existe
    if (in_array('events', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
        $result = $stmt->fetch();
        echo "<br>📊 Nombre d'événements dans la table <code>events</code> : <strong>{$result['total']}</strong><br>";
    } else {
        echo "<br>⚠️ Table <code>events</code> non trouvée<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur lors de la requête : " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 5. Informations serveur
echo "<h2>5. Informations serveur</h2>";
echo "<ul>";
echo "<li><strong>Version PHP:</strong> " . phpversion() . "</li>";
echo "<li><strong>Extensions PDO:</strong> " . (extension_loaded('pdo') ? '✅ Chargée' : '❌ Non chargée') . "</li>";
echo "<li><strong>Driver MySQL:</strong> " . (extension_loaded('pdo_mysql') ? '✅ Chargé' : '❌ Non chargé') . "</li>";
echo "<li><strong>Date/Heure serveur:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT : Supprimez ce fichier après les tests !</strong></p>";
echo "<p>Pour des raisons de sécurité, ne laissez pas ce fichier accessible en production.</p>";
