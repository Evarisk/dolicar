# [DoliCar] [23.2.0] - Parc par entrepôt sur une carte - Carte grise par scan IA ou fichier JSON - Conducteur en saisie libre

Description : DoliCar montre maintenant où se trouve chaque véhicule, entrepôt par entrepôt et sur une carte, et le change d'entrepôt en un clic. Une carte grise se crée à partir d'une photo lue par l'IA ou d'un fichier JSON, et sa fiche affiche le kilométrage actuel, l'état des documents liés et des pièces jointes classées par type.

**Cette version demande Saturne 23.8.0 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Parc par entrepôt

* **Une nouvelle page « Parc par entrepôt »** dans le menu DoliCar : un indicateur par entrepôt avec son nombre de véhicules et sa part du parc, le total du parc et les véhicules rangés nulle part. Un clic sur un indicateur descend jusqu'à la liste de l'entrepôt.
* **La carte des entrepôts** : un cercle par entrepôt, d'autant plus grand qu'il abrite de véhicules. La latitude et la longitude se saisissent sur la fiche de l'entrepôt, ou se remplissent avec le bouton **Géolocaliser les entrepôts** à partir de son adresse postale (service OpenStreetMap Nominatim).

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/warehouse-overview.png" alt="Page Parc par entrepôt : indicateurs de 10 véhicules au total, 4 à l'Agence Lyon (40 %), 3 à l'Agence Marseille (30 %), 1 à l'Atelier Montpellier (10 %), 2 sans entrepôt, puis la carte des entrepôts avec un cercle sur Lyon, Montpellier et Marseille et le bouton Géolocaliser les entrepôts">

* **Le transfert immédiat** : chaque véhicule de la liste a son choix d'entrepôt de destination et son bouton **Transférer** ; le mouvement de stock est enregistré sur-le-champ. Le bloc « Véhicules sans entrepôt », en bas de page, sert à la première affectation.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/warehouse-transfer.png" alt="Liste des véhicules par entrepôt : Agence Lyon (4), Agence Marseille (3) et Atelier Montpellier (1), avec pour chaque véhicule l'immatriculation, la marque et le modèle, le numéro VIN, l'état Actif, une liste d'entrepôts de destination et un bouton Transférer">

> À la mise à jour, l'entrée de menu et les champs de coordonnées des entrepôts sont créés à la première ouverture de l'accueil DoliCar.

### Créer une carte grise à partir d'une photo ou d'un fichier JSON

* **Scanner la carte grise avec l'IA** : sur l'ajout rapide, déposez une photo de la carte grise ; l'IA en lit les rubriques officielles et la carte grise est créée directement. Le scan utilise le service et la clé configurés dans le module IA de Dolibarr (modèle de vision gpt-4o-mini par défaut, réglable par la constante `DOLICAR_AI_VISION_MODEL`) ; le panneau n'apparaît que si ce module est activé.
* **Ajout depuis un fichier JSON de carte grise** : un fichier dont les clés sont les rubriques officielles (A, B, C.1, D.1… Z.4) crée la carte grise sans passer par une API d'immatriculation. Seule la rubrique A (la plaque) est obligatoire, les rubriques inconnues ou vides sont ignorées, et les valeurs sont remises en forme (dates, « 1199 cm3 » devient 1199). Si un brouillon existe déjà pour la plaque, il est complété et validé au lieu de créer un doublon.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/quickcreation-ai-json.png" alt="Ajout rapide d'une carte grise : panneau Scanner la carte grise avec l'IA avec sa zone de dépôt de photo et le bouton Analyser et créer la carte grise, puis panneau Ajout depuis un fichier JSON de carte grise avec un fichier carte-grise-GT-418-KM.json choisi et le bouton Créer depuis le fichier JSON">

* **Compléter une fiche existante** : en modification d'une carte grise, le panneau « Compléter depuis un fichier JSON de carte grise » remplit les champs du formulaire ; vous relisez avant d'enregistrer, et une confirmation est demandée si la plaque du fichier diffère de celle de la fiche.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/card-edit-json.png" alt="Formulaire de modification d'une carte grise : panneau Compléter depuis un fichier JSON de carte grise déplié, fichier carte-grise-GT-418-KM.json choisi, bouton Importer, puis les boutons Enregistrer et Annuler">

* **Pour vos autres logiciels** : la même création est disponible par l'API REST, `POST dolicar/registrationcertificates/fromjson`.

### Fiche carte grise

* **Le kilométrage actuel** s'affiche sur la fiche : le plus haut relevé de l'historique du véhicule (trajets du carnet de bord, entretiens, factures validées). Une correction faite depuis l'historique s'y reporte aussitôt.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/card-current-mileage.png" alt="Fiche de la carte grise GT-418-KM : bandeau avec les liens Carnet de bord publique du véhicule et Interface de contrôle qualité, champs immatriculation, produit et numéro de châssis, et à droite la ligne Kilométrage actuel 18 352 km">

* **L'état de chaque objet lié** : la liste des objets liés gagne une colonne État pour tous les objets (contrôles, propositions, factures…). Un contrôle non verrouillé porte un œil barré : il n'est pas encore visible sur l'interface publique, qui ne montre que les contrôles verrouillés. La colonne Montant TTC et la ligne Total ne s'affichent que si l'un des objets porte un montant.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/linked-objects-status.png" alt="Objets liés d'une carte grise : une proposition commerciale à l'état Brouillon pour 226,80 euros et un contrôle DigiQuali à l'état Brouillon (à valider) suivi d'une icône d'œil barré, avec la ligne Total de 226,80 euros">

* **Des tags sur les fichiers joints** : l'onglet « Fichiers joints » d'une carte grise gagne une colonne Tag, juste après le nom du fichier : Carte grise recto, Carte grise verso, Assurance recto, Assurance verso, Contrôle technique, Macaron environnement. Chaque tag est une pastille colorée ; un clic ouvre le choix, qui s'enregistre sans recharger la page. Le tag suit le fichier quand il est renommé et disparaît avec lui. Sans droit de modification sur les cartes grises, les tags restent en lecture seule.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/file-tags.png" alt="Onglet Fichiers joints d'une carte grise : six fichiers avec dans la colonne Tag les pastilles Assurance recto, Carte grise recto, Carte grise verso, Contrôle technique et Macaron environnement, et une pastille en pointillés Ajouter un tag sur le fichier photo-compteur.png">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/file-tags-picker.png" alt="Choix du tag ouvert sur un fichier sans tag : Sans tag, Carte grise recto, Carte grise verso, Assurance recto, Assurance verso, Contrôle technique et Macaron environnement, chacun dans sa pastille colorée">

> À la mise à jour, la colonne Tag apparaît après la première ouverture de l'accueil DoliCar, qui crée le champ de stockage.

### Carnet de bord public : un conducteur hors de vos listes

* **Un troisième choix « Saisie libre »** pour le conducteur, au départ comme au retour : nom (obligatoire), prénom et téléphone, pour une personne qui n'est ni utilisateur ni contact (intérimaire, prestataire…).
* **Rien à ressaisir au passage suivant** : le choix et l'identité sont retenus sur le téléphone, l'onglet « Saisie libre » s'ouvre déjà rempli.
* **Joignable en un geste** : sur la fiche du véhicule sorti, le nom du conducteur et son téléphone cliquable. Le nom alimente l'historique, les fiches véhicule et les PDF comme avant.
* L'onglet « Utilisateur interne » devient « Interne » pour que les trois choix tiennent sur un téléphone.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/public-departure-free-driver.png" width="32%" alt="Carnet de bord public sur téléphone, écran de départ : onglets Interne, Externe et Saisie libre, ce dernier sélectionné avec les champs nom, prénom et téléphone remplis, puis la date de départ et le kilométrage de départ"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/public-vehicle-out-free-driver.png" width="32%" alt="Carnet de bord public sur téléphone : fiche d'une Peugeot e-208 marquée Véhicule actuellement SORTI, avec le nom de la conductrice saisie librement, la date de départ et son numéro de téléphone cliquable"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/public-return-free-driver.png" width="32%" alt="Carnet de bord public sur téléphone, écran de retour : rubrique Rendu par avec l'onglet Saisie libre sélectionné et les champs nom, prénom et téléphone remplis, puis la date de retour et le kilométrage d'arrivée">

### Signalement de problème

* **Plusieurs destinataires** : le champ « Email des responsables » de la configuration accepte une liste, séparée par des virgules ou à raison d'une adresse par ligne ; chacune reçoit le signalement. Chaque adresse est vérifiée : si l'une est invalide, elle est nommée dans le message d'erreur et rien n'est enregistré.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/problem-report-recipients.png" alt="Configuration de DoliCar, bloc Signalement de problème : champ Email des responsables avec trois adresses, une par ligne, et liste Modèle d'email sur Signalement de problème (carnet de bord public)">

* **Visible dans la vue d'ensemble des modèles d'e-mail de Saturne** : la colonne « Utilisé par » nomme le réglage de DoliCar, « Signalement de problème · Mail envoyé au responsable », et mène à sa page de configuration, au lieu d'afficher le nom technique de la constante.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/email-template-used-by.png" alt="Vue d'ensemble des modèles d'e-mail de Saturne, groupe Signalement de problème : le modèle Signalement de problème (carnet de bord public), son sujet, et dans la colonne Utilisé par le réglage Signalement de problème · Mail envoyé au responsable">

### Un DoliCar plus moderne

* Les écrans de DoliCar adoptent le design moderne partagé par Saturne : étapes de l'ajout rapide et sections en cartes, plaque en caractères de largeur fixe, liens du bandeau de la fiche en pastilles, types d'événements de l'historique, verdicts et jours avant le prochain contrôle en pastilles colorées, retards de contrôle du tableau de bord de l'ambre au rouge.
* Dans la configuration, les marques de véhicules s'affichent en grille : la page est près de six fois moins longue.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.2.0/quickcreation-theme.png" alt="Ajout rapide d'une carte grise au nouveau design : trois étapes Rechercher, Vérifier le résultat et Créer la carte grise en cartes, carte Numéro d'immatriculation avec le choix de l'API et la plaque d'exemple AB-123-CD, carte Client / Tiers, bouton Lancer la recherche et les panneaux repliés du scan IA et de l'ajout par fichier JSON">

### Installation

* **Une version de Saturne trop ancienne est refusée proprement** : DoliCar déclare la version de Saturne avec laquelle il est testé et refuse de s'activer sur une version antérieure, avec le message de Dolibarr qui nomme la version attendue, au lieu de s'arrêter sur une erreur dans une page.

## Améliorations & corrections

### Carte grise et historique du véhicule

* **Suppression définitive** : supprimer une carte grise, y compris par l'action de masse de la liste, laissait la ligne en base au statut « Supprimé ». Elle est désormais réellement effacée.
* **Un document pour un seul événement** : une facture, un devis, une note de frais, une commande ou une facture fournisseur, un contrôle déjà rattaché à un événement du véhicule n'est plus proposé pour un autre événement. Le serveur refuse aussi un envoi forgé, ainsi qu'une note de frais dont une partie des lignes est déjà rattachée.
* **Montants comptés une seule fois** : une facture liée à la fois directement et par son champ « Carte grise » apparaissait deux fois dans les objets liés, et son montant était compté deux fois dans le total.
* **Lignes de note de frais d'un événement** : avec la protection CSRF renforcée de Dolibarr, leur chargement échouait (erreur 403) ; l'appel porte maintenant son jeton.

### Carnet de bord public

* **Contrôle relié au véhicule** : le contrôle créé depuis le carnet de bord public était relié au lot sous un type que DigiQuali ne lit pas : pas de Lot/Série sur le contrôle, pas d'accès à la fiche du véhicule, et le dernier contrôle affiché ignorait ceux créés depuis DigiQuali. C'est réparé, et les contrôles déjà créés sont repris à la mise à jour.
* **Envois mieux filtrés** : une photo au format SVG ou dans un format d'image non reconnu est refusée ; un mémo vocal n'est accepté que s'il s'agit bien d'un fichier audio, dans la limite de taille d'envoi de l'instance.
* **Libellé « Tiers »** : il s'affichait sous sa forme technique sur la fiche publique du véhicule.

### Langues

* **Traduction anglaise complète** : avec un navigateur en anglais, l'interface publique affichait des clés techniques et le mail de signalement partait avec le sujet `ProblemReportEmailSubject`. Les 441 libellés sont traduits.
* **Apostrophes doublées** : huit libellés affichaient « Plaque d''immatriculation » et équivalents dans les PDF. Le retard des contrôles techniques du tableau de bord utilise l'unité de jour du cœur au lieu d'un « j » écrit en dur.
* **Plus de libellés écrasés chez les voisins** : cinq libellés génériques de DoliCar remplaçaient ceux du cœur et de DigiQuali sur les pages où DoliCar se charge, par exemple le sujet des PDF de notes de frais de DoliFraisPro ou un message de l'application mobile de DigiQuali. Ils portent maintenant un nom propre à DoliCar.

### Installation et compatibilité

* **Module installé ailleurs que dans `htdocs/custom`** : DoliCar n'écrit plus ce chemin en dur. Dans une seconde racine déclarée dans `conf.php`, le menu « Interface publique », les onglets « Carte grise » des lots, produits, tiers et projets, le logo et les styles du carnet de bord public, l'application installable, les liens vers DigiQuali et la catégorie « Marques » créée à l'activation fonctionnent. Les menus et onglets sont réécrits à la première ouverture de l'accueil DoliCar après la mise à jour.
* **Application installée sur un téléphone** : son adresse de démarrage ne s'ouvrait que sur un serveur Apache configuré comme WAMP ; sur Apache par défaut ou nginx, elle donnait une page introuvable. Elle vise désormais la bonne page.
* **Installation par lien symbolique** : DoliCar retrouve Saturne quand il est installé par un lien symbolique, au lieu de s'arrêter en erreur.
* **Registre d'objets** : les chemins que DoliCar déclare au registre de Saturne sont publiés tels qu'installés ; les anciennes versions des modules qui consultent ce registre les lisent sans s'arrêter en erreur.

### Qualité

* Chaîne d'intégration continue renforcée : contrôle que DoliCar n'appelle que l'API publiée de Saturne, refus des inclusions relatives de Saturne, actions GitHub passées en Node 24, analyse statique étendue au module IA du cœur et retrait du changelog généré automatiquement.

## Testez les nouveautés maintenant

**Allez tester le parc par entrepôt et les tags des fichiers joints sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.2](https://github.com/Evarisk/dolicar/compare/23.1.2...23.2.0) et 23.2.0

* [#589] [RegistrationCertificateFr] fix: icônes Font Awesome 5 au lieu des noms de la version 6, libellés « d'arrivée » [`e32ba52c`](https://github.com/Evarisk/dolicar/commit/e32ba52c)
* [#587] [PublicVehicleLogBook] fix: contrôle du carnet de bord relié au lot (VIN) en productlot [`5c16934f`](https://github.com/Evarisk/dolicar/commit/5c16934f)
* [#585] [Media] fix: renforcement de la validation des types de médias à l'upload [`6247ca80`](https://github.com/Evarisk/dolicar/commit/6247ca80)
* [#560] [CI] ci: retrait du workflow Generate Auto Changelog sur main [`70905284`](https://github.com/Evarisk/dolicar/commit/70905284)
* [#580] [PWA] fix: start_url du manifeste vers la page .php [`85b7fb46`](https://github.com/Evarisk/dolicar/commit/85b7fb46)
* [#580] [Module] fix: chemins relatifs confiés au cœur sans custom/ [`fc462cc8`](https://github.com/Evarisk/dolicar/commit/fc462cc8)
* [#580] [Module] fix: chemin disque construit avec DOL_DOCUMENT_ROOT . '/custom/…' [`9707c166`](https://github.com/Evarisk/dolicar/commit/9707c166)
* [#580] [Module] fix: dol_buildpath('/custom/…') sans custom/ [`04bba38e`](https://github.com/Evarisk/dolicar/commit/04bba38e)
* [#580] [Module] fix: URL construite avec DOL_URL_ROOT . '/custom/…' [`1c0df632`](https://github.com/Evarisk/dolicar/commit/1c0df632)
* [#577] [CI] fix: baseline PHPStan, libelle DoliCarRegistrationNumber du formulaire d'import [`3086eab5`](https://github.com/Evarisk/dolicar/commit/3086eab5)
* [#577] [Langs] fix: libelle « Tiers » brut sur la fiche vehicule publique (companies non charge) [`fab8f3c3`](https://github.com/Evarisk/dolicar/commit/fab8f3c3)
* [#577] [Langs] fix: apostrophes doublees, « j » en dur, cles generiques prefixees [`3965f98a`](https://github.com/Evarisk/dolicar/commit/3965f98a)
* [#576] [Langs] fix: traduction en_US complete (441 cles) [`e22ca266`](https://github.com/Evarisk/dolicar/commit/e22ca266)
* [#574] [RegistrationCertificateFr] feat: état des objets liés, montant masqué sans montant [`fcb2be11`](https://github.com/Evarisk/dolicar/commit/fcb2be11)
* [#572] [RegistrationCertificateFr] feat: tags on the attached files of a carte grise [`f3978d4b`](https://github.com/Evarisk/dolicar/commit/f3978d4b)
* [#568] [ProblemReport] fix: boolean array_filter callbacks for phpstan [`dd9a2dc0`](https://github.com/Evarisk/dolicar/commit/dd9a2dc0)
* [#569] [RegistrationCertificateFr] feat: show the current mileage on the card [`71a64701`](https://github.com/Evarisk/dolicar/commit/71a64701)
* [#568] [ProblemReport] feat: several recipients for the problem report email [`c79727bf`](https://github.com/Evarisk/dolicar/commit/c79727bf)
* [#487] [VehicleLogBook] feat: free driver identity on the public departure and return forms [`c5da8fa8`](https://github.com/Evarisk/dolicar/commit/c5da8fa8)
* [#426] [VehicleHistory] fix: prevent assigning the same document to several vehicle events [`f9d0460d`](https://github.com/Evarisk/dolicar/commit/f9d0460d)
* [#564] [CI] ci: actions GitHub en Node 24 (checkout@v7, cache@v6) [`aae55dd9`](https://github.com/Evarisk/dolicar/commit/aae55dd9)
* [#562] [CI] ci: contrôle « API Saturne publiée » dans la CI qualité [`c898226b`](https://github.com/Evarisk/dolicar/commit/c898226b)
* [#445] [Warehouse] fix: PHPStan, doc de retour lisible et casse de Productlot [`ba005b3a`](https://github.com/Evarisk/dolicar/commit/ba005b3a)
* [#429] [CI] fix: PHPStan scanne htdocs/ai/lib, en phase avec le checkout partiel [`6efb8941`](https://github.com/Evarisk/dolicar/commit/6efb8941)
* [#429] [CI] fix: PHPStan voit htdocs/ai/lib du cœur [`5d648fa2`](https://github.com/Evarisk/dolicar/commit/5d648fa2)
* [#560] [CI] ci: retrait du workflow Generate Auto Changelog [`a98420b8`](https://github.com/Evarisk/dolicar/commit/a98420b8)
* [#558] [Mod] feat: version minimale de Saturne, activation refusee sur un Saturne trop ancien [`fac628aa`](https://github.com/Evarisk/dolicar/commit/fac628aa)
* [#556] [Registre] fix: chemins du registre publies tels qu'installes, lisibles par Saturne < 23.3.0 [`9ab3787a`](https://github.com/Evarisk/dolicar/commit/9ab3787a)
* [#554] [Email] feat: declare the DoliCar email template setting to the Saturne overview [`c186974b`](https://github.com/Evarisk/dolicar/commit/c186974b)
* [#552] [CI] ci: refuser les inclusions relatives de Saturne [`cbfa6e56`](https://github.com/Evarisk/dolicar/commit/cbfa6e56)
* [#552] [Saturne] fix: inclure Saturne par dol_include_once / dol_buildpath [`f4314851`](https://github.com/Evarisk/dolicar/commit/f4314851)
* [#540] [SCSS] feat: thème moderne des écrans DoliCar [`1f29379f`](https://github.com/Evarisk/dolicar/commit/1f29379f)
* [#540] [Front] rework: styles inline déplacés en SCSS [`894cab73`](https://github.com/Evarisk/dolicar/commit/894cab73)
* [#528] [CSRF] fix: ajouter le token CSRF aux liens et appels AJAX [`1afcc6db`](https://github.com/Evarisk/dolicar/commit/1afcc6db)
* [#445] [Warehouse] feat: fleet by warehouse with indicators, map and quick transfer [`065ae2e4`](https://github.com/Evarisk/dolicar/commit/065ae2e4)
* [#337] [RegistrationCertificateFr] fix: delete carte grise definitively (hard delete) [`f901d79c`](https://github.com/Evarisk/dolicar/commit/f901d79c)
* [#429] [RegistrationCertificateFr] feat: add carte grise creation from JSON and AI photo scan [`c9a485ee`](https://github.com/Evarisk/dolicar/commit/c9a485ee)
