# [DoliCar] [23.1.1] - Page publique des véhicules plus légère - Lien de contrôle partageable

Description : Version corrective. La page publique de contrôle d'un véhicule ne s'arrête plus faute de mémoire, le lien vers l'historique des contrôles peut être envoyé à un client, et le signalement de problème du carnet de bord dit la vérité sur l'envoi de l'e-mail.

**Le lien signé vers l'historique des contrôles s'appuie sur DigiQuali 23.2.0.** Avec une version plus ancienne de DigiQuali, le lien garde sa forme d'avant et ne s'ouvre que pour un utilisateur connecté à partir de DigiQuali 23.2.0.

## Améliorations & corrections

### Interface publique de contrôle

* **La page d'un véhicule ne s'arrête plus faute de mémoire.** L'onglet Documentation chargeait tous les fichiers partagés de l'instance — plusieurs milliers — pour ne garder que ceux de la carte grise : avec DigiQuali, la page d'un véhicule pouvait dépasser la mémoire autorisée. Seuls les fichiers des cartes grises du lot sont désormais chargés.
* **Un lien qu'on peut envoyer à un client.** Le lien « Interface de contrôle qualité » du bandeau de la carte grise porte la signature de DigiQuali 23.2.0 : copié dans un e-mail, il s'ouvre pour un visiteur sans compte.
* **L'onglet Carnet de bord trouve sa place dans la nouvelle barre de navigation** de l'interface publique de DigiQuali : une icône de voiture et un libellé court sur une seule ligne.

### Carnet de bord

* **Le signalement de problème ne promet plus un e-mail qui n'est pas parti.** L'écran de confirmation n'annonce « Le responsable a été prévenu par email » que si l'envoi a réussi ; sinon il indique que le signalement est enregistré dans l'historique du véhicule et invite à contacter le responsable. L'échec d'envoi est tracé dans le journal.

### Qualité

* Chaîne d'analyse statique consolidée (exclusion des bouchons de test de Saturne, scan du socle par dossier, baseline élaguée) et fichier de changelog renommé en `ChangeLog.md`.

## Testez les nouveautés maintenant

**Allez tester l'interface publique de contrôle d'un véhicule sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.0](https://github.com/Evarisk/dolicar/compare/23.1.0...23.1.1) et 23.1.1

* #534 [Public] fix: ne charger que les fichiers partagés des certificats du lot, pas tous ceux de l'instance [`#535`](https://github.com/Evarisk/dolicar/pull/535)
* #536 [Public] fix: lien du bandeau vers l'historique public des contrôles signé [`#537`](https://github.com/Evarisk/dolicar/pull/537)
* #524 [PublicControl] fix: icône voiture et libellé court pour l'onglet Carnet de bord [`#525`](https://github.com/Evarisk/dolicar/pull/525)
* #493 [VehicleLogBook] fix: ne plus annoncer le responsable prévenu quand le mail n'est pas parti [`#529`](https://github.com/Evarisk/dolicar/pull/529)
* #521 [Mod] fix: renommer le changelog en ChangeLog.md [`#522`](https://github.com/Evarisk/dolicar/pull/522)
* #509 #511 #515 #518 [CI] chaîne qualité : exclusions de stubs, scan du socle par dossier, baseline élaguée
