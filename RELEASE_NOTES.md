# [DoliCar] [23.3.0] - Archivage des véhicules vendus - Colonnes de contrôle de la liste sur le contrôle obligatoire

Description : Un véhicule vendu ou sorti du parc s'archive maintenant depuis sa fiche : il quitte la liste, le tableau de bord, les entrepôts et le carnet de bord public, et son historique reste consultable. Cette version répare aussi la modification du numéro de châssis, l'historique du véhicule, les objets liés et la génération de deux fiches du carnet de bord.

**Cette version demande Saturne 23.9.1 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Archiver un véhicule vendu

* **Un bouton « Archiver »** sur la fiche carte grise, pour un véhicule vendu ou sorti du parc. La confirmation rappelle ce qui change : le véhicule n'apparaît plus dans la liste, le tableau de bord ni les entrepôts, et le carnet de bord public n'accepte plus de saisie. Son historique reste consultable depuis sa fiche, et il peut être réactivé.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.3.0/archive-confirm.png" alt="Fiche de la carte grise GN-261-RV à l'état Actif avec la fenêtre de confirmation Archiver : Archiver le véhicule GN-261-RV (vendu, sorti du parc) ? Il n'apparaîtra plus dans la liste, le tableau de bord ni les entrepôts, et le carnet de bord public n'acceptera plus de saisie. Son historique reste consultable depuis sa fiche, et vous pourrez le réactiver. Boutons Oui et Non, et en bas de la fiche les boutons Modifier, Nouvelle proposition, Nouvelle facture, Archiver et Supprimer">

* **Un véhicule archivé est en lecture seule** : plus de bouton « Modifier », et la modification de ses champs, de son tiers ou de son projet est refusée tant qu'il n'est pas réactivé. Le raccourci de création d'un contrôle DigiQuali disparaît de sa fiche.
* **Le bouton « Réactiver »** le remet dans le parc. L'archivage et la réactivation sont tracés dans les événements de la carte grise.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.3.0/card-archived.png" alt="Fiche de la carte grise GE-385-TN avec la pastille rouge Archivé, ses champs immatriculation, produit DACIA_SANDERO et numéro de châssis, le tableau des objets liés vide, et en bas les boutons Nouvelle proposition, Nouvelle facture, Réactiver et Supprimer, sans bouton Modifier">

* **La liste masque les véhicules archivés** : elle gagne une colonne et un filtre « État ». Par défaut, l'indication « Archivé masqué » signale qu'ils sont cachés ; choisir « Archivé » dans le filtre les fait revenir, et « Retirer les filtres » revient au réglage par défaut. Depuis l'onglet « Carte grise » d'un lot, d'un produit, d'un tiers ou d'un projet, tous les véhicules restent listés, archivés compris : c'est leur historique qu'on y cherche.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.3.0/list-archived-hidden.png" alt="Liste des cartes grises avec l'indication Archivé masqué à côté du titre et la nouvelle colonne État : HC-744-BX, GT-418-KM, HB-527-PZ, FX-903-LD et GN-261-RV, chacune avec sa pastille verte Actif">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.3.0/list-archived-filter.png" alt="Liste des cartes grises filtrée sur l'état Archivé, avec l'indication Brouillon, Actif masqués : une seule ligne, GE-385-TN, DACIA_SANDERO, avec sa pastille rouge Archivé">

* **Tableau de bord et parc par entrepôt** : les véhicules archivés n'y sont plus comptés, ni dans les cartes grises par entrepôt, ni dans l'activité récente, ni dans les contrôles techniques dépassés.
* **Carnet de bord public** : un véhicule archivé, atteint par son lien, son QR code ou sa plaque, affiche un écran « Véhicule archivé ». Départ, retour, signalement de problème, réparation, contrôle, photos et mémos vocaux sont refusés, et il ne figure plus dans la liste des véhicules proposés.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/dolicar/refs/heads/assets/release-23.3.0/public-archived.png" width="32%" alt="Carnet de bord public sur téléphone : icône d'archive, titre Véhicule archivé, message Ce véhicule ne fait plus partie du parc : le carnet de bord n'accepte plus de saisie, le véhicule DACIA SANDERO (GE-385-TN) et le bouton Rechercher un autre véhicule">

> Le statut « Archivé » pouvait déjà être posé par l'action de masse « Archiver » de la liste, sans effet sur les écrans. Les véhicules archivés de cette façon sont masqués dès la mise à jour ; le bouton « Réactiver » de leur fiche les remet dans le parc.

### Contrôles DigiQuali dans la liste des cartes grises

* **Les colonnes de contrôle suivent le contrôle obligatoire** : avec DigiQuali 23.4.0, qui distingue les contrôles obligatoires des contrôles informatifs, les colonnes « Contrôles DigiQuali », « Date de contrôle », « Jours avant prochain contrôle » et « Verdict du contrôle » reprennent le contrôle d'où vient l'état du véhicule : son dernier contrôle obligatoire (le contrôle technique), et non un contrôle trimestriel informatif plus récent. Ce contrôle est listé en premier, en gras. Avec une version antérieure de DigiQuali, c'est le dernier contrôle verrouillé. Ces colonnes portent sur les contrôles rattachés à la carte grise.
* **Chaque contrôle une seule fois** : un contrôle rattaché deux fois à la carte grise était listé deux fois. Les contrôles sont maintenant triés du plus récent au plus ancien.

## Améliorations & corrections

### Fiche carte grise

* **Numéro de châssis conservé à la modification** : le formulaire de modification ne proposait aucun numéro de châssis, ou ceux de tous les produits après un changement de produit, et l'enregistrer sans rien toucher détachait le lot de la carte grise. Les lots du produit choisi sont proposés et le lot rattaché reste sélectionné.
* **Bouton « + » du numéro de châssis** : après la création d'un lot depuis le formulaire de modification, on arrivait sur la création d'une nouvelle carte grise. On revient au formulaire de modification, avec le nouveau lot dans la liste.
* **Fiche photos et fiche d'état avant/après** : leur génération depuis le bloc « Carnet de bord » de la fiche échouait (« Failed to load doc generator »). Elles sont générées et listées dans le bloc.
* **Objets liés** : le type d'objet s'affichait sous sa forme technique (« Digiquali_control », « Propal ») ; il est traduit (« Contrôle », « Proposition commerciale »). La colonne Kilométrage, toujours vide, affiche le kilométrage saisi sur les factures, propositions et commandes (par exemple « 101 650 km »).

### Historique du véhicule

* **Notes lisibles** : les notes des événements (trajets du carnet de bord, corrections de kilométrage) affichaient leurs balises `<br>` en clair ; elles s'affichent mises en forme. Les extraits de notes des documents liés sont débarrassés de leurs balises, et dans le livret d'entretien PDF, les lignes d'une note ne sont plus collées les unes aux autres.
* **Factures avec leur numéro définitif** : une facture validée s'inscrivait dans l'historique sous sa référence provisoire « (PROVn) ». Elle y figure avec son numéro définitif.

### Stabilité

* **Pages des autres modules** : une page qui annule son action, par exemple l'annulation d'un formulaire sur la fiche d'une enveloppe DoliLetter, s'arrêtait sur une erreur fatale dès que DoliCar était actif. C'est réparé.
* **DigiQuali installé dans un autre dossier que DoliCar** (racine alternative déclarée dans `conf.php`) : la fiche carte grise et le carnet de bord public ne s'arrêtent plus sur une erreur.
* **Moins d'avertissements PHP** : plus aucun à chaque génération des quatre documents PDF de DoliCar (livret d'entretien, carnet de bord, fiche photos, fiche d'état), ni à l'affichage de la colonne des contrôles de la liste.
* **Déclencheurs** : l'icône de DoliCar dans Configuration › Modules › Déclencheurs était une image cassée ; elle s'affiche.

## Testez les nouveautés maintenant

**Allez tester l'archivage d'un véhicule vendu sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.0](https://github.com/Evarisk/dolicar/compare/23.2.0...23.3.0) et 23.3.0

* [#614] [Trigger] fix: picto dolicar_color@dolicar, l'image dolicar@dolicar n'existe pas [`a31a3857`](https://github.com/Evarisk/dolicar/commit/a31a3857)
* [#612] [Hook] fix: doActions et quickCreationAction acceptent $action nul [`7aabedb7`](https://github.com/Evarisk/dolicar/commit/7aabedb7)
* [#610] [RegistrationCertificateFr] feat: archived vehicle refuses third party and project changes from the banner [`7ad49ea3`](https://github.com/Evarisk/dolicar/commit/7ad49ea3)
* [#610] [RegistrationCertificateFr] feat: archive a sold vehicle, hidden from lists, dashboard, warehouses and public logbook [`0ef0ff27`](https://github.com/Evarisk/dolicar/commit/0ef0ff27)
* [#608] [VehicleHistory] fix: facture inscrite dans l'historique du véhicule avec sa référence définitive, plus « (PROVn) » [`9beebac5`](https://github.com/Evarisk/dolicar/commit/9beebac5)
* [#606] [RegistrationCertificateFr] feat: control columns of the list follow the mandatory control [`485e4913`](https://github.com/Evarisk/dolicar/commit/485e4913)
* [#599] [RegistrationCertificateFr] fix: fiche photos et fiche d'état générées depuis la fiche carte grise [`f78e2152`](https://github.com/Evarisk/dolicar/commit/f78e2152)
* [#598] [Documents] fix: propriété $warnings déclarée sur les modèles PDF, plus d'avertissement à la génération [`23a0f85c`](https://github.com/Evarisk/dolicar/commit/23a0f85c)
* [#594] [RegistrationCertificateFr] fix: retour au formulaire de modification après création d'un lot depuis celui-ci [`be096c55`](https://github.com/Evarisk/dolicar/commit/be096c55)
* [#597] [Module] fix: classes de DigiQuali chargées par dol_include_once(), plus par un chemin relatif [`fc37328b`](https://github.com/Evarisk/dolicar/commit/fc37328b)
* [#596] [LinkedObjects] fix: type d'objet traduit et kilométrage des factures, propositions et commandes affiché [`a1043c19`](https://github.com/Evarisk/dolicar/commit/a1043c19)
* [#595] [VehicleHistory] fix: notes de l'historique du véhicule affichées en HTML, plus de <br> en clair [`96cdaa17`](https://github.com/Evarisk/dolicar/commit/96cdaa17)
* [#594] [RegistrationCertificateFr] fix: numéro de châssis proposé en modification, l'enregistrement ne détache plus le lot [`5a74c34c`](https://github.com/Evarisk/dolicar/commit/5a74c34c)
