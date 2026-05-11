# [DoliCar] [22.0.0] - Carte grise via API - Création rapide assistée - Tableau de bord

Description : Cette version intègre l'API `apiplaqueimmatriculation.com` pour récupérer automatiquement les données d'un véhicule depuis sa plaque ou son VIN, introduit un assistant de création rapide complet pour le certificat d'immatriculation, ajoute un livret d'entretien généré dynamiquement, refond le tableau de bord avec des widgets dédiés et expose l'API REST CRUD du certificat.

## Nouvelles fonctionnalités et innovations

### Carte grise via API `apiplaqueimmatriculation.com`

* Implémentation de l'API : récupération automatique des données véhicule depuis la plaque d'immatriculation ou le VIN.
* Recherche véhicule par VIN.
* Mise à jour des marques de voiture (`car brands`) via l'API.
* Nouveaux champs gérés (carrosserie, énergie, etc. selon la réponse API).
* Configuration de la clé API dans `setup.php`.
* Affichage des messages d'erreur de l'API à l'utilisateur.
* Sauvegarde des `vin` & `plate_number` postés en cas d'erreur API (formulaire pré-rempli au retry).
* `curl` utilisé en remplacement de `file_get_contents` pour `immatriculationapi`.
* Statut **brouillon** introduit pour préserver les données API en cas d'échec de traitement.

<!-- 📸 Ajouter une screenshot ici -->

### Assistant de création rapide (QuickCreation)

* Wizard complet de création rapide pour le certificat d'immatriculation (`#399`).
* Pré-remplissage produit + lot quand on crée depuis un `productlot`.

<!-- 📸 Ajouter une screenshot ici -->

### Livret d'entretien

* Nouveau document `template_livret_entretien.odt` généré sur le certificat d'immatriculation.

<!-- 📸 Ajouter une screenshot ici -->

### Tableau de bord enrichi

* Nouveau dashboard avec :
  * Statistiques des entrepôts (warehouse stats)
  * Activités récentes
  * Widget des contrôles techniques (CT) en retard
* Refonte des activités récentes et de l'UX de la liste des entrepôts.

<!-- 📸 Ajouter une screenshot ici -->

### Onglet historique véhicule

* Nouvel onglet « Historique véhicule » sur la fiche carte grise.
* Vue dédiée `registrationcertificatefr_vehiclehistory.php`.

### Liaisons et objets liés

* Affichage des contrôles **DigiQuali** liés au certificat d'immatriculation (`#363`).
* Libellé du modèle de fiche affiché dans les objets liés (contrôles et surveys).
* Contrôles supprimés masqués dans la liste des objets liés.
* Bannière du `productlot` enrichie : affichage du certificat d'immatriculation lié.

### API REST CRUD

* Nouveaux endpoints REST CRUD complets pour `RegistrationCertificateFr` (`#356`).

---

## Améliorations & corrections

### Carte d'immatriculation

* Champs « carte grise » groupés dans une section repliable (collapsible).
* Brouillon sauvegardé avant l'appel API + gestion d'erreur pour retry.
* `vin` manquant et erreur de champ dans le fallback de fetch lot — corrigés.
* Picto entrepôt ajouté avant le champ « entrepôt de destination ».
* Bouton de recherche aligné, ordre du champ entrepôt revu.
* `Categorie` initialisé pour éviter un fatal sur fetch (`#383`).
* `Categorie` retiré au save pour ne plus provoquer d'erreur SQL.
* Lecture du VIN depuis le batch du `productlot` dans la note de propal.
* Lien de création de `productlot` invalide et classe `Categorie` manquante — corrigés.
* Flag `showinpwa` ajouté sur les champs de liste.
* Fichier de langue chargé dans `getTooltipContentArray`.
* Fatal `getRegistrationCertificateData` corrigé.
* Fatal lié à un `redirect` avant `db->commit` corrigé (2 occurrences).
* Erreur sur `productlot` précédemment créé corrigée.

### Stock / entrepôt

* `warehouse_id` sélectionné utilisé lors de la création du batch de stock.
* Outil `dolicartools` créé pour réparer la quantité de stock par lot (`#374`).
* Migration de stock à l'init du module corrigée.
* Increment de stock évité quand le lot VIN existe déjà.
* Liste des certificats d'immatriculation avec liens dans l'outil de réparation.
* Entrée de menu « Outils » ajoutée au module DoliCar.

### Admin

* Domaine de langue admin chargé pour les traductions manquantes (`#380`).
* Paramètre `action` lu correctement → boutons « tout / aucun » fonctionnels (`#393`).

### SCSS

* Migration `@import` → `@use` dans toutes les feuilles de style DoliCar (`#388`).

### Module / classes

* Plusieurs passes de nettoyage de code (`[Class] core: clean code`, 5 commits).

## Comparaison des versions [21.0.0](https://github.com/Evarisk/dolicar/compare/21.0.0...22.0.0) et 22.0.0

* [#404] [LivretEntretien] feat: maintenance booklet document generation [`5192294`](https://github.com/Evarisk/dolicar/commit/5192294)
* [#399] [QuickCreation] feat: full quickcreation wizard [`05df48b`](https://github.com/Evarisk/dolicar/commit/05df48b)
* [#395] [RegistrationCertificateFr] rework: replace file_get_contents with curl [`75c2e6f`](https://github.com/Evarisk/dolicar/commit/75c2e6f)
* [#394] [RegistrationCertificateFr] fix: handle missing vin and wrong field in lot fetch fallback [`3e50260`](https://github.com/Evarisk/dolicar/commit/3e50260)
* [#393] [Admin] fix: read action parameter so all/none buttons work [`8101020`](https://github.com/Evarisk/dolicar/commit/8101020)
* [#392] [RegistrationCertificateFr] feat: save draft before api call and handle error retry [`73d8e2d`](https://github.com/Evarisk/dolicar/commit/73d8e2d)
* [#389] [Dashboard] feat/rework: warehouse stats, recent activities, overdue CT widgets [`3a40ae9`](https://github.com/Evarisk/dolicar/commit/3a40ae9) [`00641a1`](https://github.com/Evarisk/dolicar/commit/00641a1)
* [#388] [SCSS/RegCertif] rework: @import → @use, collapsible carte grise section [`b7b0652`](https://github.com/Evarisk/dolicar/commit/b7b0652) [`df9565e`](https://github.com/Evarisk/dolicar/commit/df9565e)
* [#387] [RegistrationCertificateFr] add: warehouse picto before destination warehouse field [`205363e`](https://github.com/Evarisk/dolicar/commit/205363e)
* [#383] [RegistrationCertificateFr] fix: initialize Categorie object to prevent fatal [`7c703f9`](https://github.com/Evarisk/dolicar/commit/7c703f9)
* [#382] [RegistrationCertificateFr] fix: align search button and reorder warehouse field [`184f92c`](https://github.com/Evarisk/dolicar/commit/184f92c)
* [#380] [Admin] fix: load admin lang domain for missing translations [`19e6924`](https://github.com/Evarisk/dolicar/commit/19e6924)
* [#377] [LinkedObjects] fix: hide deleted controls in linked objects list [`28a62bc`](https://github.com/Evarisk/dolicar/commit/28a62bc)
* [#374] [Tools/RegistrationCertificate] add: dolicartools page, fix stock batch [`5005698`](https://github.com/Evarisk/dolicar/commit/5005698) [`736af04`](https://github.com/Evarisk/dolicar/commit/736af04) [`4a79e85`](https://github.com/Evarisk/dolicar/commit/4a79e85) [`f6471d3`](https://github.com/Evarisk/dolicar/commit/f6471d3) [`e66d810`](https://github.com/Evarisk/dolicar/commit/e66d810) [`313e633`](https://github.com/Evarisk/dolicar/commit/313e633)
* [#363] [LinkedObjects] feat: show linked objects and digiquali controls on registration cert card [`5ec04e4`](https://github.com/Evarisk/dolicar/commit/5ec04e4)
* [#362] [RegistrationCertificateFr] fix: invalid productlot create link, missing Categorie [`fcf149a`](https://github.com/Evarisk/dolicar/commit/fcf149a)
* [#361] [DoliCar] fix: read vin from product lot batch in propal note [`a086068`](https://github.com/Evarisk/dolicar/commit/a086068)
* [#359] [RegistrationCertificateFr] fix: remove category on save causing sql error [`b184473`](https://github.com/Evarisk/dolicar/commit/b184473)
* [#358] [ProductLot/RegCertif] feat: linked registration cert in banner, lang in tooltip [`f59fe64`](https://github.com/Evarisk/dolicar/commit/f59fe64) [`fbfceca`](https://github.com/Evarisk/dolicar/commit/fbfceca)
* [#357] [RegistrationCertificateFr] fix: add showinpwa flag on list fields [`aa2fe95`](https://github.com/Evarisk/dolicar/commit/aa2fe95)
* [#356] [API] add: REST CRUD endpoints for RegistrationCertificateFr [`1da1a48`](https://github.com/Evarisk/dolicar/commit/1da1a48)
* [#353] [RegistrationCertificateFr] feat: draft status to preserve API data on process failure [`b9b6e53`](https://github.com/Evarisk/dolicar/commit/b9b6e53)
* [#351] [RegistrationCertificateFr] fix: pre-fill product and lot when creating from productlot [`5992b25`](https://github.com/Evarisk/dolicar/commit/5992b25)
* [#347] [Api] add: api key config, brands update, vin search, new fields, save vin/plate post-error [`cb495bf`](https://github.com/Evarisk/dolicar/commit/cb495bf) [`ac3eeb9`](https://github.com/Evarisk/dolicar/commit/ac3eeb9) [`8057d35`](https://github.com/Evarisk/dolicar/commit/8057d35) [`2f83ccc`](https://github.com/Evarisk/dolicar/commit/2f83ccc) [`dec9dec`](https://github.com/Evarisk/dolicar/commit/dec9dec) [`d0fc577`](https://github.com/Evarisk/dolicar/commit/d0fc577) [`590db73`](https://github.com/Evarisk/dolicar/commit/590db73)
* [#345] [RegistrationCertificate] add/fix: apiplaqueimmatriculation.com implementation, curl, fatal redirect, getRegistrationCertificateData [`9c013fc`](https://github.com/Evarisk/dolicar/commit/9c013fc) [`0845d38`](https://github.com/Evarisk/dolicar/commit/0845d38) [`7746391`](https://github.com/Evarisk/dolicar/commit/7746391) [`8fe9f6b`](https://github.com/Evarisk/dolicar/commit/8fe9f6b) [`704583d`](https://github.com/Evarisk/dolicar/commit/704583d)
* [#2366] [RegistrationCertificateFr] feat: display sheet model label in linked objects [`e3c826c`](https://github.com/Evarisk/dolicar/commit/e3c826c)
* [Vehicle History] feat: vehicle history tab on carte grise card [`35daff6`](https://github.com/Evarisk/dolicar/commit/35daff6)
* [Class] core: clean code (5 commits) [`e6e884b`](https://github.com/Evarisk/dolicar/commit/e6e884b) [`b458264`](https://github.com/Evarisk/dolicar/commit/b458264) [`054ac05`](https://github.com/Evarisk/dolicar/commit/054ac05) [`4b2459f`](https://github.com/Evarisk/dolicar/commit/4b2459f) [`229a8c9`](https://github.com/Evarisk/dolicar/commit/229a8c9)
