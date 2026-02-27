# Configurer Facebook pour le fil d’actualités du club

**Important :** L’interface de Meta (developers.facebook.com) change souvent. Ce guide s’appuie sur la **documentation officielle Meta** (App Review, 2024). Si votre écran ne correspond pas, utilisez le **lien officiel** en bas.

---

## Checklist pour que le fil fonctionne vraiment

Tout se fait côté **WebApp2** et **app Facebook**. L’API (BackendPHP) n’a pas de configuration Facebook : elle utilise uniquement le token que WebApp2 enregistre après « Connecter la page Facebook ».

1. **WebApp2 .env** : `FACEBOOK_APP_ID` et `FACEBOOK_APP_SECRET` doivent être renseignés (pour le flux OAuth « Connecter la page Facebook »).
2. **Fiche club** (tableau de bord) : URL de la page Facebook du club renseignée (ex. https://www.facebook.com/ArchersDeGemenos).
3. **App Facebook** (developers.facebook.com) : permission **pages_read_engagement** ajoutée à l’app (Cas d’usage / Contrôle app).
4. **Compte qui clique sur « Connecter la page Facebook »** : Admin ou Testeur de l’app (Paramètres → Rôles) et administrateur de la page Facebook du club.

---

## Objectif

Obtenir l’accord de Meta pour la permission **pages_read_engagement** (lire les publications d’une page Facebook), sans quoi vous avez l’erreur #10 sur le fil du club.

---

## Tester l’app pour enregistrer la vidéo de démo (mode Développement)

Meta exige une **vidéo** montrant le fonctionnement. En **mode Développement**, la permission `pages_read_engagement` fonctionne **sans révision** pour les comptes qui ont un **rôle** sur l’app (Administrateur, Développeur ou Testeur) et qui connectent une **page dont ils sont administrateur**. Vous pouvez donc faire fonctionner le fil pour enregistrer la vidéo.

### Étapes à suivre

1. **Rôle sur l’app**
   - Allez sur **https://developers.facebook.com/apps** → votre app.
   - Menu **Paramètres** (ou **Settings**) → **Rôles** (ou **Roles**).
   - Vérifiez que le **compte Facebook** que vous utiliserez sur le site est bien **Administrateur**, **Développeur** ou **Testeur**. Ajoutez-le si besoin (adresse e-mail ou compte Facebook).

2. **Permission ajoutée à l’app**
   - Dans le menu, ouvrez **Vérifier** → **Contrôle app** (ou l’équivalent **App Review** / **Permissions and features**).
   - Vérifiez que **pages_read_engagement** (et si besoin **pages_show_list**) apparaît dans les permissions de l’app, même en « Non soumis ». Si la permission n’apparaît pas, ajoutez-la via « Demander » / « Request » pour qu’elle soit au moins en mode développement.

3. **Page Facebook**
   - La **page Facebook du club** que vous allez connecter doit avoir ce **même compte** comme **administrateur** (ou au moins éditeur). Vérifiez dans **Paramètres de la page** → **Rôles** sur facebook.com.

4. **Test sur le site**
   - Sur votre site : page **Actualités du club**.
   - Si une page est déjà connectée avec un autre compte : **Déconnecter la page Facebook**.
   - Cliquez sur **Connecter la page Facebook**.
   - Lors de la redirection vers Facebook, connectez-vous avec le **même compte** que celui qui a un rôle sur l’app (et qui est admin de la page).
   - Autorisez les permissions demandées.
   - Après le retour sur le site, le fil devrait se charger **sans erreur #10** (en mode Développement, pour ce compte).

5. **Enregistrement de la vidéo**
   - Une fois le fil affiché : lancez l’enregistrement (1080p si possible).
   - Montrez : visite de la page Actualités du club → **Connecter la page Facebook** → autorisation sur Facebook → retour sur le site → **affichage des posts** du fil.
   - Pas d’audio nécessaire. Vidéo courte (1–2 minutes).

Si après ces étapes vous avez **toujours l’erreur #10** : vérifiez que l’app est bien en **mode Développement** (pas en mode Live) et que le compte utilisé est bien dans **Rôles**. Vous pouvez aussi faire un appel de test dans le **Graph API Explorer** (developers.facebook.com) avec la même permission pour ce compte, afin que Meta enregistre un usage.

---

## Procédure officielle (documentation Meta actuelle)

### Où aller (d’après l’interface actuelle en français)

1. Ouvrez **https://developers.facebook.com/apps**
2. Cliquez sur votre application (ex. « Feed page club »)
3. Dans le **menu de gauche**, cliquez sur **Vérifier** (section qui peut avoir une flèche pour s’ouvrir).
4. Dans le sous-menu qui s’affiche, cliquez sur **Contrôle app**.
5. Vous arrivez sur la page **« Soumissions de Contrôle app »** avec :
   - le bloc **« Nouvelles requêtes »** qui liste les permissions (`pages_show_list`, `business_management`, `pages_read_engagement`, `public_profile`, etc.),
   - le bouton bleu **« Suivant »** en bas à droite.

C’est ici que vous soumettez les permissions à Meta. Il n’y a pas d’entrée de menu appelée « Autorisations et fonctionnalités » : tout se fait sur cette page **Contrôle app** (Soumissions de Contrôle app).

### Que faire sur cette page (statut « Non soumis »)

Si vous voyez **« Non soumis »** et la liste **« Nouvelles requêtes »** avec `pages_read_engagement` (et le bouton **Suivant**) : cliquez sur **Suivant**.

Vous arrivez sur la page **« Demander un Contrôle app »** avec plusieurs blocs à déplier et à remplir :

1. **Vérification** — Cliquez sur **« Accéder à la vérification »** et terminez la vérification demandée par Meta (identité ou entreprise).
2. **Paramètres de l’application** — Dépliez le bloc, vérifiez ou complétez (icône, politique de confidentialité, catégorie, etc.).
3. **Usage autorisé** — Dépliez : c’est ici que vous décrivez à quoi sert l’app et comment vous utilisez les permissions (ex. : afficher le fil d’actualités de la page Facebook du club sur le site).
4. **Traitement des données** — Dépliez et répondez aux questions sur l’utilisation des données.
5. **Instructions pour l’examen** — Dépliez : ce bloc est souvent marqué **« À vérifier »**. C’est là qu’on renseigne comment les testeurs Meta peuvent accéder à votre site et qu’on ajoute la **vidéo de démo** (connexion Facebook puis affichage du fil du club).

Une fois tous les blocs complétés, enregistrez ou validez puis cherchez le bouton final pour **Soumettre pour révision** (ou équivalent).

---

## Conseil : réponses courtes pour une app qui « affiche juste le fil public »

Votre app n’a pas besoin de données sensibles : elle affiche sur votre site les **publications déjà publiques** d’une page Facebook. Vous pouvez remplir le questionnaire en restant sur ce principe :

- **Usage autorisé / À quoi sert l’app**  
  Exemple : *« L’application affiche le fil d’actualités public d’une page Facebook sur le site web du club. Les utilisateurs voient les mêmes publications que sur Facebook, dans une section de notre site. Aucune donnée privée n’est utilisée. »*

- **Traitement des données**  
  Répondre en indiquant que vous n’utilisez que des **données déjà publiques** (posts, texte, image, lien) affichées sur votre site, sans stockage persistant ni réutilisation à d’autres fins.

- **Instructions pour l’examen**  
  Donner l’URL de la page « Actualités du club » (ex. https://arctraining.fr/club-feed), indiquer que les testeurs peuvent s’y rendre et cliquer sur « Connecter la page Facebook » pour voir le fil. Vidéo de démo : enregistrement court (connexion Facebook puis affichage du fil sur cette page).

Cela permet de répondre de façon honnête et minimale sans surcharger le formulaire.

### Une fois sur la bonne page (soumission des permissions)

- Vous devez pouvoir **chercher** ou **choisir** la permission **pages_read_engagement** (et éventuellement **pages_show_list**).
- Cliquez sur le bouton pour **demander l’accès** à cette permission (en anglais souvent : **Request advanced access**).
- Puis sur le bouton pour **continuer** la demande (ex. **Continue the Request**, **Suivant**, **Continuer**).

### Étapes suivantes (résumé doc Meta)

Meta demandera ensuite notamment :

- **Paramètres de l’app** : icône 1024×1024, URL de politique de confidentialité, catégorie, contact.
- **Vérification de l’app** : comment les testeurs Meta peuvent accéder à votre site pour tester.
- **Descriptions d’usage** : pour **chaque** permission, une courte description + **une vidéo de démonstration** (écran qui montre : connexion avec Facebook puis affichage du fil d’actualités du club sur votre page).
- Enfin : **Submit for Review** (Soumettre pour révision).

Tout le détail officiel (en anglais) est ici :  
**https://developers.facebook.com/docs/resp-plat-initiatives/individual-processes/app-review/submission-guide/**

---

## Points importants (d’après Meta)

- Au moins **un appel API réussi** avec chaque permission demandée, dans les **30 jours** avant la soumission (votre site qui charge le fil compte, ou le Graph API Explorer).
- **Vidéo de démo** obligatoire pour chaque permission : résolution 1080p minimum, montrer la connexion Facebook puis l’usage (ex. affichage du fil sur « Actualités du club »). Pas d’audio nécessaire.
- L’app doit être **accessible** aux testeurs Meta (site en ligne ou instructions d’accès claires).

---

## Après approbation par Meta

Sur **votre site** (arctraining.fr) :

1. Page **Actualités du club**
2. **Déconnecter la page Facebook**
3. **Connecter la page Facebook**
4. Accepter les autorisations sur la page Facebook (avec le compte admin de la page du club)

Le fil devrait alors s’afficher.

---

## Référence officielle (à jour)

Quand l’interface ne correspond pas à ce fichier, suivez le **guide officiel Meta** (en anglais) :

- **App Review – Tutorial (soumission)**  
  https://developers.facebook.com/docs/resp-plat-initiatives/individual-processes/app-review/submission-guide/

Les noms des menus et boutons varient (langue, version, type d’app). Ne vous fiez pas à un libellé précis : parcourez les menus jusqu’à trouver l’écran où l’on **demande ou soumet** des permissions, puis suivez les étapes demandées (description, vidéo, etc.) jusqu’à **Soumettre pour révision**.
