# Mini SOC

Projet fil rouge **Symfony 7 + API Platform + JWT + React (Vite/MUI) + PostgreSQL + Docker + GitHub Actions**.

## Prérequis

- Docker & Docker Compose (recommandé), ou PHP 8.3 + Node 20+ + PostgreSQL 15.
- Clés JWT Lexik déjà présentes sous `backend/config/jwt/` (`.pem`).

## Démarrage rapide (Docker)

À la racine du dossier `mini-soc/` :

```bash
docker compose up -d --build
```

Assurez-vous que votre fichier `.env` à la racine définit notamment `POSTGRES_*`, `DATABASE_URL`, `APP_SECRET` et les chemins JWT.

- **Interface** : [http://localhost:8080](http://localhost:8080) (Nginx → Vite + API `/api`)
- **Vite seul** : [http://localhost:5173](http://localhost:5173) (proxy `/api` → `127.0.0.1:8080` en dev local)
- **PostgreSQL** : `localhost:5432` (identifiants du `.env`)

Variable frontend : `VITE_API_BASE_URL=/api` (recommandé : même origine via Nginx sur le port 8080).

- **Accès recommandé** : `http://localhost:8080` (Nginx → Vite + API).
- Si tu ouvres **`http://localhost:5173`** (Vite seul dans Docker), le proxy `/api` pointe vers le service **`nginx`** via `VITE_PROXY_API_TARGET` (sinon `127.0.0.1:8080` ne serait pas joignable depuis le conteneur).

## Backend

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate
```

Compte admin démo (fixtures) : `admin@minisoc.local` / `Admin12345!`

### Connexion JWT (exemple)

```bash
curl -s -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@minisoc.local","password":"Admin12345!"}'
```

Endpoints utiles :

- `GET /api/dashboard/stats` (JWT, rôle `ROLE_AUDITEUR`+)
- Exports CSV : `GET /api/export/logs.csv`, `GET /api/export/alerts.csv` (`ROLE_ANALYSTE`+)

## Frontend

```bash
cd frontend
npm ci
npm run dev   # http://localhost:5173
```

Scripts : `npm run lint`, `npm run test`, `npm run test:coverage`, `npm run build`.

Stack UI : MUI, React Query, Recharts, DataGrid, thème sombre « SOC ».

## Stockage IP bloquées & quota AbuseIPDB

Conformément à l’implémentation actuelle, **liste d’IP bloquées** et **quota journalier AbuseIPDB** sont stockés en **PostgreSQL** (`blocked_ips`, `abuseip_quota`). Le conteneur Redis reste disponible dans Compose pour d’autres usages mais n’est pas requis par le code PHP pour ces deux fonctions.

## Qualité & CI

- **PHPStan** : niveau défini dans `backend/phpstan.neon` (actuellement 3 — objectif playbook : monter progressivement).
- Workflow : `.github/workflows/ci.yml` (PHPStan, PHPUnit + Postgres + fixtures, ESLint Airbnb, Vitest + couverture, audits Composer/npm, build Docker, placeholder de déploiement sur `main`).

## Tests

- Backend : `cd backend && ./vendor/bin/phpunit -c phpunit.dist.xml` (nécessite base de test migrée ; le groupe `integration` peut être ignoré si la base n’est pas prête — certains tests se marquent skipped).
- Frontend : `npm run test:coverage` (seuil défini dans `vite.config.js`).

---

Pour le détail fonctionnel attendu du fil rouge, se référer au playbook du dépôt parent.
