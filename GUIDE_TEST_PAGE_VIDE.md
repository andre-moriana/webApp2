# Guide de Test - Problème Page Vide

## 🔍 Étapes de Diagnostic

### 1. Accéder à la page de debug

**URL:** http://localhost/test-session-debug.php (ou votre domaine)

Cette page vous montre:
- ✅ État de la session PHP
- ✅ Présence et validité du token JWT
- ✅ Date d'expiration du token
- ✅ Test de l'API backend

### 2. Vérifier les logs du navigateur

1. Ouvrir Safari
2. Aller sur le Dashboard
3. Ouvrir la Console Web (⌥⌘C ou Option+Cmd+C)
4. Recharger la page (⌘R)
5. Chercher les messages:
   - 🔒 "API Interceptor activé"
   - 🔍 "Vérification de session au chargement..."
   - ✅ "Token valide" OU ❌ "Token invalide"

### 3. Vérifier les logs PHP

```bash
# Dans le terminal
tail -f /Users/andremoriana/webApp2/logs/*.log

# OU si les logs sont ailleurs
tail -f /var/log/apache2/error.log
```

Chercher les messages:
- "DashboardController::index() - Début"
- "SessionGuard: Token JWT expiré"
- "verify.php - Token VALIDE"

### 4. Tester manuellement l'endpoint verify

```bash
# Dans le terminal
curl -v -b cookies.txt http://localhost/api/auth/verify

# Si vous avez jq installé pour formater le JSON
curl -b cookies.txt http://localhost/api/auth/verify | jq
```

Résultat attendu:
- Status 200 + `{"success":true}` = Token valide
- Status 401 + `{"success":false}` = Token expiré

## 🧪 Scénarios de Test

### Scénario A: Page vide après Cmd+R

**Ce que vous voyez:**
- Dashboard s'affiche mais vide (pas de données)
- Pas de redirection vers login

**Actions à faire:**

1. Ouvrir la Console Safari (⌥⌘C)
2. Recharger (⌘R)
3. Regarder les logs console - qu'est-ce qui s'affiche?

**Résultats possibles:**

| Message Console | Signification | Solution |
|----------------|---------------|----------|
| "Token invalide (401)" | Token expiré détecté | Devrait rediriger - si non, voir ci-dessous |
| "Token valide" | Token OK | Le problème est ailleurs (API backend) |
| Aucun message | Interceptor pas chargé | Vérifier header.php |
| Erreur 404 sur verify | Endpoint manquant | Vérifier fichier verify.php |

### Scénario B: Token expiré mais pas de redirection

**Si les logs montrent "Token invalide" mais pas de redirection:**

1. Vérifier si `alert()` apparaît
2. Si oui mais pas de redirection → problème avec `window.location.replace()`
3. Si non → interceptor ne fonctionne pas

**Fix manuel temporaire:**

```javascript
// Dans la console Safari, taper:
sessionStorage.clear();
window.location.href = '/login?expired=1';
```

### Scénario C: Redirection en boucle

**Si vous êtes redirigé constamment vers login:**

1. Vérifier la durée de vie du token JWT côté backend
2. Le token doit avoir au moins 24h de validité
3. Vérifier que le login stocke bien le token en session

## 🔧 Commandes de Debug Utiles

### Vérifier la configuration PHP session

```bash
php -i | grep session
```

### Nettoyer toutes les sessions

```bash
# Trouver le dossier de sessions PHP
php -r "echo session_save_path();"

# Supprimer toutes les sessions (ATTENTION: déconnecte tous les utilisateurs)
rm -rf /tmp/sessions/*
```

### Tester le token JWT manuellement

```php
<?php
// Créer test-token.php
session_start();
echo "Token: " . ($_SESSION['token'] ?? 'AUCUN') . "\n";

if (isset($_SESSION['token'])) {
    $parts = explode('.', $_SESSION['token']);
    $payload = json_decode(base64_decode($parts[1]), true);
    echo "Payload:\n";
    print_r($payload);
    echo "\nExpire: " . date('Y-m-d H:i:s', $payload['exp']);
    echo "\nMaintenant: " . date('Y-m-d H:i:s', time());
    echo "\nValide: " . (time() < $payload['exp'] ? 'OUI' : 'NON');
}
```

## 📊 Checklist Complète

Cochez au fur et à mesure:

- [ ] Page debug accessible (test-session-debug.php)
- [ ] Session PHP active
- [ ] Token présent en session
- [ ] Token JWT valide (pas expiré)
- [ ] Endpoint /api/auth/verify répond 200
- [ ] Console Safari montre "API Interceptor activé"
- [ ] Console Safari montre "Token valide"
- [ ] Logs PHP montrent "SessionGuard: Token JWT valide"
- [ ] Dashboard charge les données correctement

## ❌ Si Rien ne Fonctionne

### Solution d'urgence: Forcer la vérification

Ajouter en début de `/webApp2/app/Views/layouts/header.php`:

```php
<?php
// VÉRIFICATION FORCÉE
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header('Location: /login?expired=1');
    exit;
}

// Vérifier expiration token
try {
    $parts = explode('.', $_SESSION['token']);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        if (time() >= $payload['exp']) {
            session_destroy();
            header('Location: /login?expired=1');
            exit;
        }
    }
} catch (Exception $e) {
    header('Location: /login?expired=1');
    exit;
}
?>
```

## 📞 Informations à Fournir pour Debug

Si le problème persiste, fournir:

1. **Sortie de test-session-debug.php** (capture d'écran)
2. **Console Safari** (copier les logs)
3. **Logs PHP** (dernières 50 lignes):
   ```bash
   tail -50 /chemin/vers/logs/error.log
   ```
4. **Durée du token** (depuis le backend)
5. **Version de Safari**

## 🎯 Prochaines Étapes

1. ✅ Accéder à test-session-debug.php
2. ✅ Noter ce qui s'affiche (token valide ou non)
3. ✅ Vérifier la console Safari
4. ✅ Me communiquer les résultats

Avec ces informations, on pourra identifier précisément le problème !
