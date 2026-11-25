# [DoliCar] [23.3.1] - Carnet de bord public plus sûr

Description : le carnet de bord public du véhicule contrôle mieux ce qu'il reçoit : la plaque d'immatriculation recherchée, l'entité du lien et la signature du conducteur. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.9.1 ou supérieur.**

## Améliorations & corrections

### Carnet de bord public : contrôles renforcés

* La recherche du véhicule par sa plaque d'immatriculation et l'entité du lien sont contrôlées avant toute lecture.
* La signature d'un trajet n'est enregistrée que si c'est une signature tracée sur la page. Sinon, le trajet est refusé avec un message clair.
* Le formulaire de saisie de la plaque renvoie vers le carnet du véhicule avec une plaque correctement transmise.
* Les usages prévus ne changent pas : scan du QR code ou saisie de la plaque, départ et retour signés au doigt.

## Testez les nouveautés maintenant

**Allez tester le carnet de bord public d'un véhicule sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.3.0](https://github.com/Evarisk/dolicar/compare/23.3.0...23.3.1) et 23.3.1

* [VehicleLogbook] fix: signature du carnet de bord public enregistrée seulement si c'est une image [`063f498`](https://github.com/Evarisk/dolicar/commit/063f498)
* [Public] fix: recherche du véhicule par immatriculation et entité du lien contrôlées [`059224b`](https://github.com/Evarisk/dolicar/commit/059224b)
