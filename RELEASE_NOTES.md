# [DoliCar] [23.1.0] - Carnet de bord affiné - Historique véhicule enrichi - Dolibarr 24

Description : Cette version affine le **carnet de bord** : la restitution du véhicule note qui le rend, le seuil de kilométrage d'arrivée avertit au lieu de bloquer, et le signalement de problème s'appuie sur un **modèle d'email configurable**. L'**historique du véhicule** reçoit les factures validées et permet de corriger un kilométrage depuis le back-office, motif à l'appui. La création rapide gagne les champs du tiers et applique l'API de carte grise configurée. Le module est déclaré compatible **Dolibarr 24**, et les deux motifs de refus du contrôle de paquet du Dolistore sont corrigés.

**Cette version demande Saturne 23.2.1 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Carnet de bord

* La restitution du véhicule enregistre **qui le rend**.
* Le **signalement de problème** utilise un modèle d'email configurable, avec un lien vers la liste des modèles depuis la configuration.

### Historique du véhicule

* Les **factures validées** alimentent l'historique.
* Un **kilométrage** peut être corrigé depuis le back-office, avec motif obligatoire.

### Création rapide

* Les **champs du tiers** sont proposés dans l'assistant de carte grise.
* La recherche par plaque applique l'**API de carte grise configurée**.

### Contrôle public

* L'onglet de contrôle public expose les **documents de la carte grise**.

## Améliorations & corrections

### Carnet de bord

* Le seuil de kilométrage d'arrivée **avertit** au lieu de bloquer la saisie.
* Le bloc de signalement de problème n'étire plus la page de configuration.

### Conformité du paquet Dolistore

* Le contrôle de paquet refusait le zip : `manifest.json.php` chargeait l'environnement Dolibarr par un `require` unique, alors que la règle demande **au moins deux tentatives** — une pour le module à la racine de Dolibarr, une pour le module dans `custom`.
* Second motif : les classes de DigiQuali étaient incluses par un chemin `/custom` en dur. Elles passent par `dol_include_once`, ce qui **répare au passage l'onglet historique du véhicule**, jusqu'ici fatal dès que DigiQuali n'était pas installé — son include n'était pas gardé par `isModEnabled`.

### Compatibilité

* Le module déclare **Dolibarr 23 au minimum et 24 au maximum**.

### Intégration continue

* Les pull requests passent désormais **PHPStan**, un **lint PHP** et un contrôle de **parité des fichiers de langue** français / anglais.
* Les assets sont compilés par la chaîne du socle et vérifiés sur les pull requests, le gulpfile local est supprimé.
* La version du trigger est déclarée avec son type, et les classes bouchons des tests de Saturne sont sorties du périmètre analysé — deux pièges qui rendent la chaîne qualité cassante ou faussement verte d'une release à l'autre.

## Comparaison des versions [23.0.0](https://github.com/Evarisk/dolicar/compare/23.0.0...23.1.0) et 23.1.0

* [CI] fix: ajouter expensereport/class aux dossiers vus par PHPStan [`6a9a5e9`](https://github.com/Evarisk/dolicar/commit/6a9a5e9)
* [#505] [CI] feat: PHPStan, lint PHP et parité des langues [`f432e88`](https://github.com/Evarisk/dolicar/commit/f432e88)
* [#503] [Module] fix: inclure les classes de DigiQuali par dol_include_once [`08f6419`](https://github.com/Evarisk/dolicar/commit/08f6419)
* [#501] [Module] fix: bootstrap main.inc.php à deux tentatives, exigé par le Dolistore [`e2c6fb7`](https://github.com/Evarisk/dolicar/commit/e2c6fb7)
* [#499] [Module] rework: bornes de version Dolibarr 23 minimum, 24 maximum [`ebfe861`](https://github.com/Evarisk/dolicar/commit/ebfe861)
* [#488] [VehicleHistory] feat: correct a mileage from the back office, reason required [`f2c4af2`](https://github.com/Evarisk/dolicar/commit/f2c4af2)
* [#495] [VehicleLogBook] feat: name who returns the vehicle, drop the mileage warning [`057ee7f`](https://github.com/Evarisk/dolicar/commit/057ee7f)
* [#492] [Setup] add: link to the email templates list from the problem report block [`c8015fd`](https://github.com/Evarisk/dolicar/commit/c8015fd)
* [#492] [Setup] fix: stop the problem report block from stretching the setup page [`bea8c59`](https://github.com/Evarisk/dolicar/commit/bea8c59)
* [#492] [VehicleLogBook] feat: configurable email template for the problem report [`9d57d52`](https://github.com/Evarisk/dolicar/commit/9d57d52)
* [#489] [VehicleLogBook] fix: warn instead of blocking over the arrival mileage threshold [`c40a191`](https://github.com/Evarisk/dolicar/commit/c40a191)
* [#485] [CI] fix: basculer les assets en mode verify, le robot ne peut plus pousser [`59cfcf5`](https://github.com/Evarisk/dolicar/commit/59cfcf5)
* [#483] [CI] feat: vérifier les assets compilés à chaque push [`d42c1f2`](https://github.com/Evarisk/dolicar/commit/d42c1f2)
* [#481] [CI] rework: compiler les assets via le socle, supprimer le gulpfile local [`ab7cb60`](https://github.com/Evarisk/dolicar/commit/ab7cb60)
* [#464] [VehicleHistory] feat: push validated invoices into the vehicle history [`31efcfa`](https://github.com/Evarisk/dolicar/commit/31efcfa)
* [#476] [QuickCreation] fix: apply the configured registration certificate api to the plate search [`6805887`](https://github.com/Evarisk/dolicar/commit/6805887)
* [#473] [QuickCreation] feat: add third party fields to registration certificate wizard [`4d81572`](https://github.com/Evarisk/dolicar/commit/4d81572)
* [#470] [PublicControl] feat: expose registration certificate documents on public control tab [`95de420`](https://github.com/Evarisk/dolicar/commit/95de420)
