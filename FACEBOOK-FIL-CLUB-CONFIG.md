# Configurer Facebook pour le fil d’actualités du club

L’erreur **#10** sur le fil signifie que Meta n’accorde pas encore la permission **pages_read_engagement** à votre app.  
**L’interface de Meta change souvent** : les noms de menus (Cas d’usage, Contrôle app, etc.) ne correspondent pas toujours à ce que vous voyez. Ce guide indique **ce qu’il faut faire** et **où trouver la doc officielle** à jour.

---

## Ce dont vous avez besoin

1. **WebApp2** : dans le `.env`, `FACEBOOK_APP_ID` et `FACEBOOK_APP_SECRET` renseignés.
2. **Fiche club** (tableau de bord du site) : l’**URL de la page Facebook** du club doit être renseignée (ex. `https://www.facebook.com/ArchersDeGemenos`).
3. **App Meta** : la permission **pages_read_engagement** doit être **ajoutée** à l’app et accordée au token (en mode Développement, pour les comptes qui ont un rôle sur l’app).
4. **Compte** : le compte qui clique sur « Connecter la page Facebook » doit être **administrateur ou testeur de l’app** et **administrateur de la page Facebook** du club.

---

## Page Plugin (fil sans API) : domaines obligatoires

Quand le fil ne peut pas être chargé via l’API (erreur #10), le site affiche le **Page Plugin** officiel Facebook. Pour que ce widget s’affiche, le **domaine** depuis lequel vous consultez la page doit être autorisé dans l’app :

- **Paramètres** (Settings) → **Basique** (Basic) → champ **Domaines de l’app** (App domains).
- En production : indiquez le domaine du site, par ex. `arctraining.fr` (sans `https://` ni `/`).
- Si vous testez en local : vous pouvez ajouter `localhost` ; le plugin peut ne pas s’afficher selon les restrictions Facebook.

**Identifiant de l’application** : dans la même page Basique, l’**Identifiant de l’application** (ex. `1640559626974623`) doit correspondre à la variable `FACEBOOK_APP_ID` dans le `.env` de WebApp2.

Si le fil du Page Plugin reste vide alors que le reste de la page s’affiche, vérifiez que vous consultez le site depuis un domaine listé dans **Domaines de l’app**.

---

## Où configurer l’app Meta (developers.facebook.com)

**Ouvrez d’abord le tableau de bord de votre app :**

- **Lien direct :** https://developers.facebook.com/apps  
- Cliquez sur **votre application**.

Meta propose **deux types d’interfaces** selon la façon dont l’app a été créée. Utilisez la **documentation officielle** pour suivre les étapes à jour :

### Si vous voyez « Cas d’utilisation » dans le menu (votre interface)

- Dans le **menu de gauche**, cliquez sur **Cas d’utilisation** (Use cases).
- Sur la page qui s’affiche, vous voyez la liste des cas d’usage (ex. « Tout gérer sur votre Page », « Intégrer du contenu Facebook, Instagram et Threads… »).
- À côté de **« Tout gérer sur votre Page »**, cliquez sur le bouton **Personnaliser** (icône crayon).
- Vous arrivez sur la personnalisation de ce cas d’usage : consultez la liste des **permissions** et **ajoutez** `pages_read_engagement` si elle n’y figure pas (bouton **Ajouter** / Add à côté de la permission).
- **Documentation officielle (anglais) :**  
  **https://developers.facebook.com/docs/development/create-an-app/pages-use-case/**

### Si vous voyez « Products » / « Produits » (ancienne interface)

- Dans le **menu de gauche**, cherchez **Products** (Produits) ou **Facebook Login**.
- Il faut que votre app ait **Facebook Login** (ou le produit qui gère la connexion) et que les **permissions** associées incluent **pages_read_engagement**.
- **Documentation des permissions :**  
  **https://developers.facebook.com/docs/permissions/**  
  → Cherchez `pages_read_engagement` pour le libellé exact et les conditions d’accès.
- En résumé : allez dans les paramètres de **Facebook Login** (ou équivalent) et ajoutez la permission **pages_read_engagement** dans la liste des permissions demandées.

### Rôles de l’app (obligatoire en mode Développement)

- Le compte qui va cliquer sur « Connecter la page Facebook » doit avoir un **rôle** sur l’app (Administrateur, Développeur ou Testeur).
- **Documentation officielle :**  
  **https://developers.facebook.com/docs/development/build-and-test/app-roles/**  
- Dans le tableau de bord : menu de gauche → **App roles** (ou **Rôles**, **Settings** puis **App roles**) et ajoutez votre compte si besoin.

---

## Après avoir ajouté la permission

1. **Révoquez l’app** dans votre compte Facebook pour forcer Meta à redemander les autorisations :  
   → https://www.facebook.com/settings?tab=applications  
   Retirez votre app de la liste.
2. Sur votre site : **Déconnecter la page Facebook** puis **Connecter la page Facebook**.
3. Autorisez à nouveau les permissions demandées par Facebook.

En **mode Développement**, si la permission est bien ajoutée à l’app et que le compte a un rôle, le fil devrait alors se charger.

---

## Soumettre pour révision (pour que tous les utilisateurs puissent utiliser le fil)

Quand le fil fonctionne pour vous (compte avec rôle), vous pouvez demander à Meta d’approuver la permission pour tout le monde (App Review) :

- **Documentation officielle (soumission) :**  
  **https://developers.facebook.com/docs/resp-plat-initiatives/individual-processes/app-review/submission-guide/**  
- Dans le tableau de bord : cherchez **App Review** (ou **Contrôle app**, **Submit for review**) dans le menu.
- Meta demandera notamment une **vidéo de démo** (connexion Facebook puis affichage du fil sur la page Actualités du club), une description d’usage et une politique de confidentialité.

---

## Liens utiles (à garder sous la main)

| Ce que vous voulez faire | Lien officiel Meta |
|--------------------------|--------------------|
| Ouvrir le tableau de bord des apps | https://developers.facebook.com/apps |
| Cas d’usage « Pages » (ajouter permissions) | https://developers.facebook.com/docs/development/create-an-app/pages-use-case/ |
| App Dashboard (description des menus) | https://developers.facebook.com/docs/development/create-an-app/app-dashboard |
| Rôles de l’app | https://developers.facebook.com/docs/development/build-and-test/app-roles/ |
| Permissions (référence) | https://developers.facebook.com/docs/permissions/ |
| Soumission App Review | https://developers.facebook.com/docs/resp-plat-initiatives/individual-processes/app-review/submission-guide/ |
| Révoquer l’app (paramètres Facebook) | https://www.facebook.com/settings?tab=applications |

Si un menu ou un libellé ne correspond pas à ce document, suivez la **documentation officielle** (liens ci-dessus) : elle reflète l’interface actuelle de Meta.
