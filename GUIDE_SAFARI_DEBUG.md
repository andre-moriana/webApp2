# 🔍 Guide Debug Rapide - Safari

## Étape 1: Activer le Menu Développement dans Safari

Si vous ne voyez pas le menu "Développement" dans Safari:

1. Ouvrir Safari
2. Menu **Safari** > **Préférences** (ou **Réglages**)
3. Onglet **Avancées**
4. Cocher **"Afficher le menu Développement dans la barre des menus"**

## Étape 2: Ouvrir la Console Web

Maintenant vous pouvez ouvrir la console de 2 façons:

**Méthode 1 - Raccourci clavier:**
- Appuyer sur: **Option (⌥) + Command (⌘) + C**

**Méthode 2 - Menu:**
- Menu **Développement** > **Afficher la console JavaScript** (ou **Afficher la console web**)

## Étape 3: Accéder à la Page de Debug

Dans votre navigateur, aller sur:

```
http://localhost/test-session
```

ou

```
http://localhost/debug/session
```

**Note:** Remplacez `localhost` par votre domaine si différent (ex: `webapp2.local` ou `127.0.0.1:8080`)

## Étape 4: Tester le Dashboard

1. Aller sur: `http://localhost/dashboard`
2. Ouvrir la Console (⌥⌘C)
3. Recharger la page: **Command (⌘) + R**
4. Regarder les messages dans la console

### Messages à chercher:

✅ **Si token valide:**
```
🔒 API Interceptor activé
🔍 Vérification de session au chargement...
✅ Token valide
```

❌ **Si token expiré:**
```
🔒 API Interceptor activé
🔍 Vérification de session au chargement...
❌ Token invalide au chargement (401)
🔄 Redirection immédiate vers login...
```

## Étape 5: Voir les Logs PHP (Terminal)

Ouvrir un Terminal et taper:

```bash
# Si vous utilisez le serveur PHP intégré
tail -f /Users/andremoriana/webApp2/logs/*.log

# OU pour Apache/MAMP
tail -f /Applications/MAMP/logs/php_error.log

# OU pour logs système
tail -f /var/log/apache2/error_log
```

Chercher les messages:
- `"SessionGuard: Token JWT expiré"`
- `"verify.php - Token EXPIRÉ"`
- `"DashboardController::index() - Début"`

## ❓ Que Faire Ensuite

### Si la page de debug ne charge pas:

Vérifier que vous utilisez la bonne URL. Essayez:
1. `http://localhost/test-session`
2. `http://127.0.0.1/test-session`
3. `http://localhost:8080/test-session` (si vous utilisez un port différent)

### Si la console ne montre rien:

1. Vérifier que la console est bien ouverte (onglet "Console" actif)
2. Nettoyer la console (icône 🗑️ en haut à gauche)
3. Recharger la page (⌘R)

### Si vous voyez "Token expiré" mais pas de redirection:

Essayer de forcer la redirection manuellement dans la console:

```javascript
window.location.href = '/login?expired=1'
```

## 📸 Que Me Communiquer

Pour que je puisse vous aider, envoyez-moi:

1. **Capture d'écran** de la page `/test-session`
2. **Copie** des messages de la Console Safari
3. **URL** exacte que vous utilisez (ex: localhost, 127.0.0.1, etc.)

## 🆘 Commandes Rapides

### Forcer une déconnexion propre:
```
http://localhost/logout
```

### Vérifier si PHP fonctionne:
```
http://localhost/debug-routes.php
```

### Nettoyer le cache Safari:
Menu **Développement** > **Vider les caches**
