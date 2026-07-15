# [DoliCar] [23.0.0] - Carnet de bord véhicule public (PWA) - Historique véhicule enrichi - PDF natifs - Import CSV

Description : Cette version fait entrer DoliCar dans le suivi terrain avec une **PWA publique de carnet de bord** (prise/restitution de véhicule, photos, commentaires vocaux, signatures, signalement de problème) accessible sans compte Dolibarr. L'historique véhicule est considérablement enrichi (notes de frais, documents fournisseurs, contrôles DigiQuali, rapports de problème), les documents livret d'entretien et carnet de bord passent du modèle ODT à une **génération PDF native**, et un **import CSV des cartes grises** est ajouté.

## Nouvelles fonctionnalités et innovations

### Carnet de bord véhicule public (PWA)

* Nouvelle application publique (PWA) accessible sans authentification pour la prise et la restitution de véhicule.
* Liste publique des véhicules : barre de recherche, affichage 2 colonnes avec plus d'informations, barre de navigation basse.
* Carte véhicule publique compacte + sélecteur de conducteur interne / externe.
* États **avant / après** avec photos, générés en fiches PDF.
* Écran des trajets : téléchargement du PDF par trajet, ouverture du PDF en ligne, photos départ / retour.
* Pré-remplissage du kilométrage avec la dernière valeur connue.
* Commentaires **photo et vocaux** lors de la prise / restitution du véhicule.
* Signatures des conducteurs intégrées au PDF du carnet de bord.
* **Signaler un problème** (commentaire, photo, message vocal) avec envoi d'un e-mail au gestionnaire.
* PWA forcée en pleine largeur sur mobile et sur tous les écrans.
* Création de réparation et de contrôle DigiQuali depuis le pied de page, ouverture du contrôle dans un nouvel onglet.

<!-- 📸 Ajouter une screenshot ici -->

### Historique véhicule enrichi

* Liaison de **lignes de note de frais** spécifiques (type, projet, commentaire) à un événement véhicule.
* Liaison des **documents fournisseurs et notes de frais** aux événements véhicule, avec configuration dans l'admin.
* Affichage dans l'historique des **rapports de problème** remontés depuis le carnet de bord public.
* Type d'événement rendu optionnel (repli sur « Autre ») pour pouvoir rattacher documents et lignes sans en choisir un.
* Aperçu PDF (loupe / magnifier) sur les documents liés d'un événement.

<!-- 📸 Ajouter une screenshot ici -->

### Documents : passage au PDF natif

* Migration du **livret d'entretien** et du **carnet de bord** du modèle ODT vers une génération **PDF native**.
* Fiches d'état avant / après et photos intégrées au PDF.
* Libellé de catégorie et coût de facture ajoutés au livret d'entretien.

<!-- 📸 Ajouter une screenshot ici -->

### Import CSV des cartes grises

* Nouvel import CSV des cartes grises (certificats d'immatriculation).

<!-- 📸 Ajouter une screenshot ici -->

### Carte grise / certificat d'immatriculation

* Bannière refondue : liens stylés avec logos des modules (carnet de bord + contrôle qualité), logo DoliCar et lien vers l'interface de contrôle qualité.
* Objets liés : colonne **montant** avec total et filtre recherche / verdict, ligne de recherche par colonne façon listes natives Dolibarr.
* Bouton de **suppression** avec confirmation sur la fiche.
* Affichage de la plaque d'immatriculation sur la carte de contrôle public.

## Améliorations & corrections

### Carnet de bord public

* Densification du formulaire prise / restitution : réduction des marges, paddings et tailles pour limiter le défilement.
* Labels des champs masqués au profit des seuls titres de carte, inputs kilométrage / date réduits.
* Bloc carburant resserré, pad de signature agrandi pour le mobile.
* Alignement et taille homogènes des boutons média (photo / audio), neutralisation du style pico.css sur le bloc média Saturne.
* Icônes de la barre basse ne débordant plus du pied de page.

### Historique / événements véhicule

* Lecture directe des liens de lignes de note de frais (`fetchObjectLinked` ignorait le type hors module).
* Libellé du type de frais traduit via son code (langue « trips »), usage de `transnoentities` pour éviter les entités HTML.
* Affichage de la note à la place de la date dans le sélecteur de ligne de note de frais.
* Rendu des sauts de ligne des notes au lieu du `<br>` littéral.
* Suppression de l'adresse IP dans la note de rapport de problème.

### Documents

* Livret d'entretien : suppression de la colonne garage, repack de l'ODT avec le `mimetype` en première entrée, `dol_print_date` en remplacement de `dolOutputDates`.
* Suppression du modèle de document avant réajout pour éviter les doublons à la réinstallation.

### Divers

* `printFieldListOption` : ancrage de la regex de contexte pour éviter un objet null (`#430`).
* Numéro d'immatriculation masqué sur les cartes proposition et facture (`#411`).
* Sous-menu de création rapide rattaché au certificat d'immatriculation (`#419`).
* Recompilation de `dolicar.min.css` depuis les sources SCSS.

## Comparaison des versions [22.0.0](https://github.com/Evarisk/dolicar/compare/22.0.0...23.0.0) et 23.0.0

* [#464] [VehicleHistory] feat: pdf preview magnifier on linked documents [`5972e7b`](https://github.com/Evarisk/dolicar/commit/5972e7b)
* [#466] [VehicleLogBook] departure/return photos in state sheet PDF, prefill mileage, photo editor modal, thumbnail sizing [`ec324c0`](https://github.com/Evarisk/dolicar/commit/ec324c0) [`4e8b034`](https://github.com/Evarisk/dolicar/commit/4e8b034) [`12efdb0`](https://github.com/Evarisk/dolicar/commit/12efdb0) [`8ea9eed`](https://github.com/Evarisk/dolicar/commit/8ea9eed)
* [#457] [VehicleLogBook] public trips screen with per-trip PDF & depart/return photos, before/after state PDF sheets, success screen [`32014ff`](https://github.com/Evarisk/dolicar/commit/32014ff) [`780e374`](https://github.com/Evarisk/dolicar/commit/780e374) [`9b4cee4`](https://github.com/Evarisk/dolicar/commit/9b4cee4) [`a51deba`](https://github.com/Evarisk/dolicar/commit/a51deba)
* [#456] [VehicleLogBook] bottom nav bar + public vehicles list, search bar, 2-column list, driver picker, repair/DigiQuali from footer, full-width PWA [`54b670b`](https://github.com/Evarisk/dolicar/commit/54b670b) [`f4f9001`](https://github.com/Evarisk/dolicar/commit/f4f9001) [`0baa676`](https://github.com/Evarisk/dolicar/commit/0baa676) [`c9fc272`](https://github.com/Evarisk/dolicar/commit/c9fc272) [`f776e04`](https://github.com/Evarisk/dolicar/commit/f776e04) [`6e736a8`](https://github.com/Evarisk/dolicar/commit/6e736a8) [`07fa80e`](https://github.com/Evarisk/dolicar/commit/07fa80e) [`7f6ac1e`](https://github.com/Evarisk/dolicar/commit/7f6ac1e) [`caf79ce`](https://github.com/Evarisk/dolicar/commit/caf79ce) [`80f9edb`](https://github.com/Evarisk/dolicar/commit/80f9edb)
* [#452] [VehicleEvent] link specific expense report lines (type, project, comment), optional event type, fee label fixes [`9847084`](https://github.com/Evarisk/dolicar/commit/9847084) [`3a9fe22`](https://github.com/Evarisk/dolicar/commit/3a9fe22) [`c512781`](https://github.com/Evarisk/dolicar/commit/c512781) [`9a34b86`](https://github.com/Evarisk/dolicar/commit/9a34b86) [`960c9ac`](https://github.com/Evarisk/dolicar/commit/960c9ac) [`9a40338`](https://github.com/Evarisk/dolicar/commit/9a40338) [`4b889a6`](https://github.com/Evarisk/dolicar/commit/4b889a6)
* [#448] [RegistrationCertificate] styled banner links with module logos, amount column with total/filter, per-column search row [`e0239bc`](https://github.com/Evarisk/dolicar/commit/e0239bc) [`29986c8`](https://github.com/Evarisk/dolicar/commit/29986c8) [`ce68d6c`](https://github.com/Evarisk/dolicar/commit/ce68d6c) [`b00b1fb`](https://github.com/Evarisk/dolicar/commit/b00b1fb) [`a3970d6`](https://github.com/Evarisk/dolicar/commit/a3970d6) [`258ea39`](https://github.com/Evarisk/dolicar/commit/258ea39)
* [#446] [PublicVehicleLogBook] photo & voice comments on pickup/return, densified form [`cf5af41`](https://github.com/Evarisk/dolicar/commit/cf5af41) [`6ea43fd`](https://github.com/Evarisk/dolicar/commit/6ea43fd) [`2448e57`](https://github.com/Evarisk/dolicar/commit/2448e57) [`a609119`](https://github.com/Evarisk/dolicar/commit/a609119) [`0c3f1a6`](https://github.com/Evarisk/dolicar/commit/0c3f1a6) [`a953efb`](https://github.com/Evarisk/dolicar/commit/a953efb)
* [#443] [PublicVehicleLogBook] report a problem (comment, photo, voice) with email to manager, show reports in history [`7ffabfa`](https://github.com/Evarisk/dolicar/commit/7ffabfa) [`829613d`](https://github.com/Evarisk/dolicar/commit/829613d) [`611bdc1`](https://github.com/Evarisk/dolicar/commit/611bdc1) [`46d700b`](https://github.com/Evarisk/dolicar/commit/46d700b) [`9515fa1`](https://github.com/Evarisk/dolicar/commit/9515fa1) [`382cfce`](https://github.com/Evarisk/dolicar/commit/382cfce) [`63e5f88`](https://github.com/Evarisk/dolicar/commit/63e5f88) [`58bb194`](https://github.com/Evarisk/dolicar/commit/58bb194)
* [#442] [PublicControl] add: show vehicle registration plate on public control card [`aafd105`](https://github.com/Evarisk/dolicar/commit/aafd105)
* [#439] [VehicleLogBook] add: driver signatures in logbook pdf, save public signature + submit feedback [`a862674`](https://github.com/Evarisk/dolicar/commit/a862674) [`1d6daa0`](https://github.com/Evarisk/dolicar/commit/1d6daa0)
* [#437] [VehicleHistory] feat: link supplier docs/expense reports to vehicle events + admin config [`c48d00f`](https://github.com/Evarisk/dolicar/commit/c48d00f)
* [#432] [LivretEntretien] rework: switch maintenance booklet and logbook from ODT to native PDF [`1888468`](https://github.com/Evarisk/dolicar/commit/1888468)
* [#430] [ExtraField] fix: anchor printFieldListOption context regex to avoid null object [`f90ac02`](https://github.com/Evarisk/dolicar/commit/f90ac02)
* [#424] [LivretEntretien] feat: add category label and invoice cost to livret [`e6f6376`](https://github.com/Evarisk/dolicar/commit/e6f6376)
* [#419] [Menu] fix: make quickcreation submenu of registrationcertificatefr [`6e71193`](https://github.com/Evarisk/dolicar/commit/6e71193)
* [#418] [VehicleHistory] feat: manage event types via actioncomm categories + backward compat migration [`fed63a5`](https://github.com/Evarisk/dolicar/commit/fed63a5) [`cef210e`](https://github.com/Evarisk/dolicar/commit/cef210e)
* [#416] [VehicleHistory] feat: link invoices, proposals and digiquali controls to vehicle events [`123a8d9`](https://github.com/Evarisk/dolicar/commit/123a8d9)
* [#415] [LivretEntretien] fix: odt mimetype repack, remove garage column, dol_print_date [`830362e`](https://github.com/Evarisk/dolicar/commit/830362e) [`9c54663`](https://github.com/Evarisk/dolicar/commit/9c54663) [`935aebd`](https://github.com/Evarisk/dolicar/commit/935aebd)
* [#413] [RegistrationCertificateFr] inline digiquali control creator widget on fk_lot row, api error messages, css [`d9d2b6f`](https://github.com/Evarisk/dolicar/commit/d9d2b6f) [`ca60654`](https://github.com/Evarisk/dolicar/commit/ca60654) [`ec26b11`](https://github.com/Evarisk/dolicar/commit/ec26b11) [`cdf5a67`](https://github.com/Evarisk/dolicar/commit/cdf5a67) [`cc61993`](https://github.com/Evarisk/dolicar/commit/cc61993)
* [#411] [ExtraField] fix: hide registration_number on propal and invoice card [`c298b73`](https://github.com/Evarisk/dolicar/commit/c298b73)
* [#410] [RegistrationCertificateFr] feat: merged actioncom list from card and vehicle history [`b679fb8`](https://github.com/Evarisk/dolicar/commit/b679fb8) [`a06c827`](https://github.com/Evarisk/dolicar/commit/a06c827)
* [#343] [Import] feat: import csv des cartes grises [`f025219`](https://github.com/Evarisk/dolicar/commit/f025219)
* [#336] [RegistrationCertificateFr] add: delete button and confirmation on card [`8fa9a7f`](https://github.com/Evarisk/dolicar/commit/8fa9a7f)
* [#266] [VehicleLogBookDocument] feat: add ODT document generation for vehicle logbook [`44324f0`](https://github.com/Evarisk/dolicar/commit/44324f0)
* [CSS] build: recompile dolicar.min.css from scss sources [`e701f0b`](https://github.com/Evarisk/dolicar/commit/e701f0b)
