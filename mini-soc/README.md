# Mini SOC

Console web de supervision léger façon SOC : tableau de bord, alertes, journaux d’accès, profil, exports CSV et administration utilisateurs selon les rôles. Stack : **Symfony 7**, **API Platform**, **JWT (Lexik)**, **React 19 (Vite)**, **PostgreSQL**, **Docker Compose**, **GitHub Actions**.

## Structure du dépôt

```
mini-soc/
├── docker-compose.yml    # nginx, frontend, backend, postgres, redis, …
├── docker/nginx/           # Reverse proxy (/ → Vite dev, /api → PHP)
├── backend/               # Symfony, API REST, Doctrine, détection / alertes
├── frontend/              # SPA React (MUI, React Query, Recharts, DataGrid)
├── docs/                  # Mémo école (.md + PDF optionnel)
└── .github/workflows/     # CI (PHPStan, PHPUnit, ESLint, Vitest, build Docker)
```

## Prérequis

- **Docker Engine** et **Compose** (recommandé), ou PHP **8.3+**, Node **20+**, PostgreSQL **15**.
- Fichiers **`.env`** : à partir des **`.env.example`** (voir plus bas).
- Paires **`.pem`** Lexik JWT dans `backend/config/jwt/` (ou génération selon la doc Symfony / Lexik — les fichiers réels restent hors dépôt par défaut, voir `.gitignore`).

## Démarrage rapide avec Docker

Depuis **`mini-soc/`** :

```bash
cp .env.example .env                    # puis ajuster valeurs locales
cp backend/.env.example backend/.env    # ou fusionner vos variables Doctrine / JWT selon environnement

docker compose up -d --build
```

Ensuite :

- **Interface principale** : [http://localhost:8080](http://localhost:8080) (Nginx sert la SPA avec `/api` vers le backend).
- **PostgreSQL** : port **`5432`** (identifiants issus du `.env` racine ou service `database` dans Compose selon votre fichier).
- Frontend seul (`npm run dev` dans `frontend/` ou service `vite` du compose selon votre config) : souvent **`5173`** — en conteneur, le proxy **`/api`** doit viser une cible joignable (ex. service `nginx` port 8080), pas forcément la loopback du poste.

Variable typique SPA : **`VITE_API_BASE_URL=/api`** quand même origine que l’interface (cas Nginx `:8080`).

### Première mise en base de données (hors tout Docker automatisé)

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate
```

**Compte démo** (fixtures) : `admin@minisoc.local` / `Admin12345!`

## API & authentification

Obtenir un jeton JWT (exemple avec le compte fixtures) :

```bash
curl -s -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@minisoc.local","password":"Admin12345!"}'
```

À noter ensuite dans `Authorization: Bearer …` pour les appels suivants.

**Exemples d’entrées utilisées dans l’app** :

- `GET /api/dashboard/stats` — agrégés pour le tableau de bord (JWT, rôle auditeur ou supérieur).
- `GET /api/export/logs.csv`, `GET /api/export/alerts.csv` — exports CSV (JWT, Analyste ou supérieur).

La liste exhaustive des routes reste disponible sous **Swagger / docs API Platform** quand ils sont exposés en environnement développement.

## Frontend hors Docker (machine locale)

```bash
cd frontend
npm ci
npm run dev    # défaut http://localhost:5173 ; proxy `/api` selon vite.config.js
```

Scripts usuels :

- **`npm run lint`** — ESLint (config projet type Airbnb ESLint legacy).
- **`npm run test`** / **`npm run test:coverage`** — Vitest.
- **`npm run build`** — production Vite (`dist/` ignoré par git).

Interface : Material UI (thème sombre type poste SOC), données via **TanStack Query / Axios**.

## Données, détection et intégrations

- Tentatives SSH / HTTP simulées, **alertes** liées aux règles de détection côté services PHP.
- **AbuseIPDB** : enrichissement facultatif avec clé d’API — cache et quota suivent l’implémentation actuelle stockée principalement dans PostgreSQL.
- Liste d’IPs bloquées / quota journaliers : persistance prévue conformément aux entités présentes dans le projet (PostgreSQL comme source principale métier décrite dans cette version).

### Rate limiting connexion JSON

Une protection par **Symfony RateLimiter** sur **`/api/login`** limite le débit depuis le monde extérieur (anti-bruteforce sur la route de jeton dédiée).

## Qualité logicielle et CI

- **`backend/phpstan.neon`** — analyse statique PHP (niveau ajustable).
- **`./vendor/bin/phpunit -c phpunit.dist.xml`** — tests PHPUnit (certains peuvent passer en *skipped* si la base ou le jeu de données n’est pas configuré comme attendu dans un environnement local minimal).
- **`.github/workflows/ci.yml`** — enchaîne sur `push`/`PR` : analyse PHP, PHPUnit avec PostgreSQL dans le job CI, ESLint frontend, Vitest avec couverture, audits Composer/npm légers, montée en container des images `backend`, `frontend`, `nginx`, plus une étape de **notification / déploiement indicateur** sur `main` (à adapter à vos outils Railway, Kubernetes, SCP, …).

## Documentation pour un rendu académique

Dans **`docs/`** :

| Fichier | Description |
|---------|-------------|
| `MemoMiniSOC-ecole.md` | Source Markdown narrative (à compléter : auteur et formation dans l’en-tête). |
| `MemoMiniSOC-ecole.pdf` | Version PDF facultative (voir régénération ci-dessous). |

Pour **régénérer le PDF** après modification du `.md` (avec Docker si **Pandoc + LaTeX** ne sont pas installés localement) :

```bash
# depuis mini-soc/
docker run --rm -v "$(pwd)/docs:/docs" pandoc/latex:latest \
  /docs/MemoMiniSOC-ecole.md -o /docs/MemoMiniSOC-ecole.pdf
```

Le propriétaire du fichier créé peut être **`root:root`** suivant votre moteur Docker ; un `sudo chown` permet de corriger sur la machine de développement.

## Environnement de variables

| Fichier | Rôle |
|---------|------|
| `.env.example` | Modèle Postgres, chemins JWT, AbuseIPDB, Redis d’infra Compose, etc. |
| `backend/.env.example` | Paramètres Symfony locaux (Doctrine, CORS, messagerie…). |

Ne **commitez jamais** de vrais secrets : copiez les exemples vers des fichiers ignorés **`.env`** et **`backend/.env`**.

---

*Application de formation / POC — aucune donnée de production.*
