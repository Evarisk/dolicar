# [DoliCar] [23.1.2] - Activation fiable - Prêt pour Saturne 23.6.0

Description : Version corrective. DoliCar s'active proprement sur une instance neuve, refuse l'activation avec un message clair quand Saturne manque, et se prépare aux prochaines versions de Saturne et des modules Evarisk.

**Cette version s'utilise avec Saturne 23.5.0 ou plus récent. Mettez-la à jour avant Saturne 23.6.0**, dont elle sait lire le nouveau registre d'objets.

## Améliorations & corrections

### Installation

* **Plus de page blanche sans Saturne.** Si Saturne n'est pas installé, l'activation de DoliCar est refusée avec un message qui nomme le module manquant, au lieu de s'arrêter sur une erreur.
* **Activation sans avertissements sur une instance neuve.** À partir de Saturne 23.6.0, les modules dont DoliCar dépend (produits, lots, factures, catégories…) sont activés avant lui : les 16 avertissements PHP de la première activation disparaissent.

### Interface publique de contrôle

* **Plus d'avertissement PHP** sur la page publique de contrôle d'un lot, d'un produit, d'un projet ou d'un tiers.

### Compatibilité

* **Prêt pour Saturne 23.6.0 et les prochaines versions des modules Evarisk.** DoliCar lit les chemins du registre d'objets sans supposer qu'ils commencent par `custom/` : il fonctionne avec un module installé dans un autre dossier, et avec les entrées sans ce préfixe des prochaines versions de Saturne, DigiQuali, Digirisk, DoliMeet et ReedCRM.

## Testez les nouveautés maintenant

**Allez tester DoliCar sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.1](https://github.com/Evarisk/dolicar/compare/23.1.1...23.1.2) et 23.1.2

* #546 #547 [Module] fix: no fatal without Saturne, activate dependencies before init [`#548`](https://github.com/Evarisk/dolicar/pull/548)
* #532 [Public] fix: prendre l'entree du registre trouvee par digiquali [`#533`](https://github.com/Evarisk/dolicar/pull/533)
* #530 [Object] fix: lire class_path du registre par dol_buildpath() [`#531`](https://github.com/Evarisk/dolicar/pull/531)
* #544 [Object] rework: retirer le prefixe custom/ des entrees du registre d'objets [`#545`](https://github.com/Evarisk/dolicar/pull/545)
