# Mini SOC — ProjetFinal

**Mini SOC** est une application de type *Security Operations Console* : back-office pour visualiser les tentatives d’authentification, gérer des alertes, consulter des journaux, exporter des rapports CSV et piloter quelques utilisateurs selon les rôles — le tout sécurisé par **JWT** et exposé en **REST** (Symfony / API Platform) avec une interface **React** contemporaine.

## Contenu du dépôt

| Répertoire | Rôle |
|------------|------|
| [`mini-soc/`](mini-soc/) | Code source principal (Docker Compose, backend, frontend, CI GitHub Actions, documentation métier technique). |
| [`mini-soc/docs/`](mini-soc/docs/) | Mémo de synthèse pour rendu institutionnel (`MemoMiniSOC-ecole.md`, PDF régénérable). |

Pour **installer**, **lancer** et **tester** les API et scripts qualité (`lint`, PHPUnit, Vitest…), suivre le fichier **[`mini-soc/README.md`](mini-soc/README.md)** qui fait office de référence projet.

---

*Projet pédagogique / démo — jeu de données factices.*
