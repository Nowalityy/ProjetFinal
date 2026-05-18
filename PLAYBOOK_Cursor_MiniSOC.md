# Playbook de Construction — Mini SOC Dashboard

**Pour développement assisté sur Cursor (ou VS Code + Copilot)**
Projet fil rouge CDA — Milosavljevic Nikola — IPSSI
Stack : Symfony 7 · React 18 · PostgreSQL 15 · Docker · GitHub Actions

---

## Comment utiliser ce document

Ce playbook découpe la construction du Mini SOC en **7 phases**. Pour chaque phase :

1. **Objectif** — ce que vous devez avoir à la fin de la phase
2. **Commandes** — à taper vous-même dans le terminal (init, install, migrations)
3. **Prompt Cursor** — un bloc à copier-coller dans le chat Cursor (Cmd/Ctrl+L)
4. **Vérification** — comment savoir que la phase est réussie

**Règle d'or :** ne passez jamais à la phase suivante tant que la vérification de la phase courante n'est pas verte. Chaque phase s'appuie sur la précédente.

**Avant de commencer**, ouvrez ce fichier dans un onglet de Cursor à côté de votre code, et collez son contenu dans le contexte (glissez-le dans le chat) pour que Cursor connaisse vos jalons.

> **Conseil soutenance :** ne collez pas les prompts en aveugle. Lisez le code généré, demandez à Cursor « explique-moi cette classe » sur ce que vous ne comprenez pas. Le jury CDA vous demandera de justifier chaque choix.

---

## Arborescence cible du projet

```
mini-soc/
├── docker-compose.yml
├── .env
├── .github/
│   └── workflows/
│       └── ci.yml
├── backend/                  # Symfony 7
│   ├── Dockerfile
│   ├── config/
│   ├── src/
│   │   ├── Entity/
│   │   ├── Repository/
│   │   ├── Service/
│   │   ├── Controller/
│   │   ├── Security/Voter/
│   │   └── DataFixtures/
│   ├── migrations/
│   └── tests/
├── frontend/                 # React 18 + Vite
│   ├── Dockerfile
│   └── src/
│       ├── api/
│       ├── context/
│       ├── components/
│       ├── pages/
│       └── App.jsx
└── docker/
    └── nginx/
        └── default.conf
```

---

# PHASE 0 — Infrastructure Docker

**Objectif :** un `docker-compose up` qui démarre 5 conteneurs sans erreur (même vides).

### Commandes

```bash
mkdir mini-soc && cd mini-soc
git init
mkdir -p backend frontend docker/nginx .github/workflows
```

### Prompt Cursor

```
Contexte : projet Mini SOC Dashboard, fil rouge CDA. Architecture n-tiers Docker
Compose (cf. Jalon 4 §5.2). Stack imposée : Symfony 7 / PHP 8.2, React 18 + Vite,
PostgreSQL 15, Nginx reverse proxy, Redis 7.

Crée à la racine du projet :

1. docker-compose.yml avec 5 services :
   - database : image postgres:15, volume persistant pgdata, variables
     POSTGRES_DB=mini_soc, POSTGRES_USER, POSTGRES_PASSWORD lues depuis .env,
     healthcheck pg_isready.
   - redis : image redis:7-alpine, utilisé pour cache et rate limiting.
   - backend : build ./backend, dépend de database (condition service_healthy)
     et redis, volume ./backend monté, expose le port PHP-FPM en interne.
   - nginx : image nginx:alpine, reverse proxy, monte docker/nginx/default.conf,
     publie le port 8080 sur l'hôte, sert l'API backend et les fichiers statiques.
   - frontend : build ./frontend, pour le dev sert React via Vite sur le port 5173.
   Tous les services sur un réseau bridge commun "soc-network".

2. .env à la racine avec : POSTGRES_DB, POSTGRES_USER, POSTGRES_PASSWORD,
   APP_ENV=dev, APP_SECRET, et un commentaire indiquant de copier en .env.local.

3. .gitignore adapté (vendor/, node_modules/, .env.local, var/, /backend/public/bundles).

4. docker/nginx/default.conf : reverse proxy qui route /api vers le backend
   PHP-FPM (fastcgi_pass backend:9000) et le reste vers le frontend.

Ne crée pas encore les Dockerfiles backend/frontend, on les fera aux phases 1 et 5.
Mets des Dockerfiles minimaux temporaires (FROM php:8.2-fpm / FROM node:20-alpine)
juste pour que le build ne casse pas.
```

### Vérification

```bash
docker-compose config        # valide la syntaxe
docker-compose up -d database redis
docker-compose ps            # database et redis doivent être "Up (healthy)"
```

---

# PHASE 1 — Backend : fondations Symfony + entités Doctrine

**Objectif :** projet Symfony 7 initialisé, 5 entités Doctrine conformes au MPD du
Jalon 3, migration exécutée, schéma PostgreSQL créé.

### Commandes

```bash
cd backend
composer create-project symfony/skeleton:"7.*" .
composer require webapp
composer require symfony/orm-pack
composer require --dev symfony/maker-bundle orm-fixtures
composer require api lexik/jwt-authentication-bundle
composer require symfony/rate-limiter symfony/redis-messenger
composer require --dev phpunit/phpunit symfony/test-pack
```

### Prompt Cursor

```
Contexte : backend Symfony 7 du Mini SOC. Je dois créer les 5 entités Doctrine
mappées 1:1 sur le Modèle Physique de Données du Jalon 3 (PostgreSQL 15).

IMPORTANT — cohérence inter-jalons : le Jalon 3 mentionnait bcrypt mais le Jalon 4
corrige en Argon2id. On utilise Argon2id partout. Tu n'as RIEN à coder pour le hash
ici (Symfony s'en charge via security.yaml en phase 2), juste savoir que password
stocke un hash Argon2id.

Crée les 5 entités dans src/Entity/ avec attributs PHP 8 Doctrine ORM 3,
en respectant exactement ce schéma :

ENTITÉ User (table users)
- id : int, PK, auto
- email : string(255), unique, not null
- password : string(255), not null  (hash Argon2id)
- roles : json  -> doit implémenter UserInterface et PasswordAuthenticatedUserInterface
  Le rôle métier (admin|analyste|auditeur) est stocké comme ROLE_ADMIN /
  ROLE_ANALYSTE / ROLE_AUDITEUR dans le tableau roles.
- createdAt : DateTimeImmutable, not null
- Relations : OneToMany vers Alert (alertes assignées), OneToMany vers AlertComment.

ENTITÉ AuthLog (table auth_logs)
- id : int PK auto
- ip : string(45), not null
- userAgent : text, nullable
- emailHash : string(255), not null  (SHA-256 de l'email, RGPD)
- status : string(10), not null  -> enum PHP backed : success|failed|blocked
- createdAt : DateTimeImmutable, not null
- Méthode isFailure(): bool
- Relation : OneToMany vers Alert (un log peut déclencher des alertes).

ENTITÉ Alert (table alerts)
- id : int PK auto
- type : string(50), not null  (brute_force, credential_stuffing, malicious_ip,
  geo_anomaly, night_activity)
- severity : string(10), not null  -> enum PHP backed : low|medium|high|critical
- status : string(20), not null, default 'open'  -> enum : open|in_progress|
  resolved|false_positive
- description : text, nullable
- createdAt : DateTimeImmutable, not null
- resolvedAt : DateTimeImmutable, nullable
- ManyToOne vers User (user_id, nullable, onDelete SET NULL) — analyste assigné
- ManyToOne vers AuthLog (auth_log_id, not null, onDelete RESTRICT) — log déclencheur
- OneToMany vers AlertComment
- Méthodes : resolve(): void (passe status à resolved + resolvedAt = now),
  assignTo(User $u): void

ENTITÉ AlertComment (table alert_comments)
- id : int PK auto
- content : text, not null
- createdAt : DateTimeImmutable, not null
- ManyToOne vers Alert (alert_id, not null, onDelete CASCADE)
- ManyToOne vers User (user_id, not null, onDelete RESTRICT)

ENTITÉ IpReputationCache (table ip_reputation_cache)
- id : int PK auto
- ip : string(45), unique, not null
- score : smallint, not null  (0-100)
- country : string(3), nullable
- isp : string(255), nullable
- isTor : bool, not null, default false
- isVpn : bool, not null, default false
- lastChecked : DateTimeImmutable, not null
- Méthode isExpired(int $ttlHours = 24): bool  (true si lastChecked > ttl)
- Méthode isMalicious(): bool  (true si score > 75)

Crée aussi les enums PHP backed correspondants dans src/Enum/ :
AuthStatus, AlertSeverity, AlertStatus, AlertType.
Et les 5 repositories dans src/Repository/ étendant ServiceEntityRepository.

Ajoute sur les repositories les méthodes que la détection utilisera (corps à
implémenter ensuite) :
- AuthLogRepository::countFailedAttemptsByIp(string $ip, int $windowMinutes): int
- AuthLogRepository::countDistinctEmailsByIp(string $ip, int $windowMinutes): int
- AlertRepository::findByStatus(AlertStatus $status): array
- IpReputationCacheRepository::findValidByIp(string $ip): ?IpReputationCache
```

### Commandes (après génération)

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

### Vérification

`doctrine:schema:validate` doit afficher « The mapping files are correct » et
« The database schema is in sync ». Vérifiez dans PostgreSQL que les 5 tables
existent avec les bons index (cf. Jalon 3 §V récapitulatif des index).

---

# PHASE 2 — Sécurité & authentification JWT

**Objectif :** endpoint `/api/login` fonctionnel, JWT RS256 émis, RBAC par Voters,
rate limiter sur le login. Correspond à UC-01 et à la séquence « Authentification
JWT » du Jalon 4.

### Commandes

```bash
php bin/console lexik:jwt:generate-keypair
```

### Prompt Cursor

```
Contexte : backend Mini SOC. Implémenter l'authentification conforme au Jalon 4
§3.1 (séquence Authentification JWT) et §6.2 (OWASP A01, A02, A07).

Exigences précises issues des jalons :
- Hash mot de passe : Argon2id (Jalon 4 corrige le bcrypt du Jalon 3).
- JWT : access token RS256, durée 1h. Refresh token 7j.
- Rôles : ROLE_ADMIN, ROLE_ANALYSTE, ROLE_AUDITEUR (RBAC, cf. CDC F1.2).
- Rate limiter sur /api/login : 10 requêtes/min par IP (Jalon 4 §5.4, OWASP A07).
- Chaque tentative de login (succès OU échec) crée un AuthLog avec emailHash en
  SHA-256 (jamais l'email en clair — RGPD, Jalon 4 §6.2 A09).

Tâches :

1. config/packages/security.yaml :
   - password_hasher Argon2id pour App\Entity\User
   - provider entity sur User (propriété email)
   - firewall "login" sur ^/api/login en json_login, success/failure handlers
     personnalisés
   - firewall "api" sur ^/api en jwt (lexik)
   - access_control : /api/login public, le reste authentifié
   - role_hierarchy : ROLE_ADMIN > ROLE_ANALYSTE > ROLE_AUDITEUR

2. config/packages/lexik_jwt_authentication.yaml : token_ttl 3600.

3. src/Service/AuthService.php :
   - authenticate() est géséré par le firewall json_login ; AuthService expose
     plutôt logAttempt(string $ip, string $email, AuthStatus $status,
     ?string $userAgent): void  qui hash l'email en SHA-256 et persiste un AuthLog.
   - Méthode hashEmail(string $email): string

4. Success/Failure handlers (src/Security/) qui appellent AuthService::logAttempt
   pour tracer success / failed dans auth_logs, puis renvoient le JWT (succès)
   ou 401 (échec).

5. config/packages/rate_limiter.yaml : limiteur "login" sliding_window,
   10 req / 1 minute, storage Redis. Brancher dans le LoginController ou via
   un event listener qui renvoie 429 si dépassé.

6. Les 3 Voters RBAC dans src/Security/Voter/ :
   - AlertVoter : VIEW (tous rôles), EDIT (ROLE_ANALYSTE+), DELETE (ROLE_ADMIN)
   - UserManagementVoter : tout réservé ROLE_ADMIN
   - L'auditeur est lecture seule partout.

Donne-moi aussi la requête curl pour tester le login une fois que les fixtures
de la phase 6 seront chargées.
```

### Vérification

Après les fixtures (Phase 6), tester :
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@minisoc.local","password":"Admin12345!"}'
```
Doit renvoyer `200` + un `token`. Un mauvais mot de passe → `401`. 11 essais en
moins d'une minute → `429`.

---

# PHASE 3 — Cœur métier : services de détection

**Objectif :** `DetectionService` et `AlertService` complets et fonctionnels.
C'est le cœur du projet et la séquence « Détection Brute Force » du Jalon 4.

### Prompt Cursor

```
Contexte : backend Mini SOC. Implémenter la détection des menaces, conforme au
Jalon 4 §3.2 (tableau des règles de détection) et au CDC §3.3.

Les 5 règles à implémenter, avec seuils EXACTS des jalons :

| Règle              | Critère                                        | Seuil      | Action                                      | Criticité |
|--------------------|------------------------------------------------|------------|---------------------------------------------|-----------|
| Brute Force        | échecs même IP en 10 min                       | >= 5       | Alerte + blocage IP 15 min + notif admin    | HIGH      |
| Credential Stuffing| échecs sur comptes différents, même IP, < 5min | >= 3 comptes| Alerte + notif admin                       | HIGH      |
| IP Malveillante    | score AbuseIPDB                                | > 75       | Alerte + enrichissement cache               | HIGH      |
| Anomalie géo.      | connexion depuis pays inhabituel               | hors pays  | Alerte seule                                | MEDIUM    |
| Activité nocturne  | connexion entre 22h et 6h                      | hors heures| Log + alerte                                | LOW       |

1. src/Service/DetectionService.php — méthodes publiques :
   - checkBruteForce(string $ip): bool
     -> AuthLogRepository::countFailedAttemptsByIp($ip, 10) >= 5
   - checkCredentialStuffing(string $ip): bool
     -> AuthLogRepository::countDistinctEmailsByIp($ip, 5) >= 3
   - checkIpReputation(string $ip): bool  -> score > 75 via AbuseIPDBClient
   - analyzeGeolocation(string $ip, int $userId): bool
   - isNightActivity(?DateTimeInterface $when = null): bool  (22h-6h)
   - analyze(AuthLog $log): void  -> orchestrateur : appelle toutes les règles
     pertinentes pour ce log, et délègue à AlertService la création des alertes.
   DetectionService NE crée PAS d'alerte directement (SRP, Jalon 4 §5.3) :
   il appelle AlertService.

2. Implémente le corps des méthodes de AuthLogRepository :
   - countFailedAttemptsByIp : COUNT des auth_logs status=failed, ip donnée,
     created_at >= now - windowMinutes. Requête DQL paramétrée (OWASP A03).
   - countDistinctEmailsByIp : COUNT DISTINCT email_hash, status=failed, ip,
     fenêtre temporelle.

3. src/Service/AlertService.php — méthodes publiques :
   - createAlert(AlertType $type, AlertSeverity $severity, AuthLog $log,
     string $description): Alert  -> persiste l'alerte liée au log déclencheur.
   - blockIp(string $ip, int $minutes): void  -> pose une clé Redis
     "blocked_ip:{ip}" avec TTL ; le LoginController la vérifie et renvoie 429.
   - isIpBlocked(string $ip): bool
   - notifyAdmin(Alert $alert): void  -> pour l'instant log Monolog niveau alert
     (email réel = Could Have, hors MVP — cf. CDC §5.3 MoSCoW).
   - resolveAlert(int $id, string $comment, User $author): void

4. Anti-doublon : ne pas créer 50 alertes brute force pour la même IP en rafale.
   Avant createAlert pour brute_force/credential_stuffing, vérifier qu'il n'existe
   pas déjà une alerte "open" du même type pour cette IP dans la fenêtre.

Commente le code en français, c'est un livrable pédagogique. Respecte SOLID :
injection de dépendances par constructeur, aucune instanciation directe (Jalon 4
§5.3 principe D).
```

### Vérification

Un test manuel : 5 `curl` de login en échec depuis la même IP → une alerte
`brute_force` HIGH doit apparaître en base, et le 6e login renvoie `429`. La
Phase 6 ajoutera le test PHPUnit automatisé de cette logique.

---

# PHASE 4 — API REST & enrichissement AbuseIPDB

**Objectif :** endpoints exposés via API Platform, client AbuseIPDB avec cache 24h,
export CSV. Couvre UC-02, UC-05, UC-07.

### Prompt Cursor

```
Contexte : backend Mini SOC. Exposer l'API REST et l'enrichissement threat
intelligence, conforme au CDC §3.5 (dashboard), §3.6 (exports) et Jalon 4 UC-05.

1. API Platform sur les entités :
   - Alert : GET collection (avec filtres status, severity, type, période — cf.
     CDC F4.2), GET item, PATCH (changement de statut, réservé ROLE_ANALYSTE+
     via AlertVoter). Pas de DELETE sauf ROLE_ADMIN.
   - AuthLog : GET collection en lecture seule, paginé, triable par created_at.
   - AlertComment : GET et POST (POST réservé ROLE_ANALYSTE+).
   - User : CRUD complet réservé ROLE_ADMIN (UserManagementVoter).
   Configure les groupes de sérialisation pour ne JAMAIS exposer le champ password.

2. src/Service/AbuseIPDBClient.php — client HTTP de l'API AbuseIPDB v2 :
   - check(string $ip): array  -> consulte d'abord le cache
     (IpReputationCacheRepository::findValidByIp). Si entrée valide (non expirée
     < 24h) la renvoie. Sinon appelle l'API, stocke le résultat dans
     ip_reputation_cache, le renvoie.
   - isQuotaExceeded(): bool  -> compteur Redis journalier, quota 1000 req/jour.
   - Fallback gracieux : si l'API est down ou quota dépassé, renvoyer un résultat
     neutre (score 0, source "fallback") sans planter — cf. CDC §4.1 et risques.
   - Clé API lue depuis une variable d'environnement (jamais en dur — OWASP A02).
   - URL hard-codée vers api.abuseipdb.com (pas d'URL utilisateur — OWASP A10).

3. src/Controller/DashboardController.php — endpoint GET /api/dashboard/stats
   renvoyant en un seul appel les widgets du CDC §3.5 :
   - compteurs : alertes actives, tentatives 24h, IPs uniques 24h, taux de succès
   - séries pour graphiques : tentatives/heure sur 7j, alertes par criticité 30j,
     répartition des statuts
   - top 10 IPs : ip, nb tentatives, taux d'échec, score, pays

4. src/Controller/ExportController.php :
   - GET /api/export/logs.csv  et  GET /api/export/alerts.csv
   - Génère un CSV en streaming (StreamedResponse), réservé ROLE_ANALYSTE+.

Toutes les requêtes BDD en DQL paramétré. Commentaires en français.
```

### Vérification

`http://localhost:8080/api/docs` (Swagger d'API Platform) liste tous les endpoints.
`GET /api/dashboard/stats` renvoie un JSON structuré. L'export CSV se télécharge.

---

# PHASE 5 — Frontend React 18

**Objectif :** SPA React conforme au sitemap du Jalon 2, thème sombre, dashboard
avec graphiques Recharts.

### Commandes

```bash
cd ../frontend
npm create vite@latest . -- --template react
npm install
npm install @mui/material @emotion/react @emotion/styled @mui/x-data-grid
npm install recharts axios react-router-dom @tanstack/react-query
```

### Prompt Cursor

```
Contexte : frontend Mini SOC, React 18 + Vite. Conforme au Jalon 2 §5 (sitemap,
zoning, charte graphique) et au CDC §4.1.

Charte graphique (Jalon 2 §5.4) : thème sombre cybersécurité — fond bleu foncé,
zones secondaires gris foncé, cyan pour actions, rouge pour critique, orange pour
moyen, vert pour positif. Police Inter ou Roboto.

Sitemap (Jalon 2 §5.1) : Login, Dashboard, Alertes (liste), Détail alerte, Logs,
Administration, Profil. Layout : sidebar gauche persistante + header utilisateur
+ zone centrale.

Tâches :

1. src/api/client.js : instance Axios baseURL /api, intercepteur de requête qui
   ajoute le header Authorization: Bearer <token>, intercepteur de réponse qui
   redirige vers /login si 401.

2. src/context/AuthContext.jsx : contexte React (Context API + hooks, cf. CDC) qui
   gère token + user, fonctions login()/logout(), stockage du token EN MÉMOIRE +
   éventuellement sessionStorage (note : pas localStorage pour le refresh token,
   risque XSS). Hook useAuth().

3. src/components/ : Layout (sidebar + header), ProtectedRoute (redirige si non
   authentifié, et restreint selon le rôle pour les pages admin).

4. src/theme.js : thème MUI sombre avec la charte ci-dessus.

5. Pages dans src/pages/ :
   - Login : formulaire email/password, appelle useAuth().login().
   - Dashboard : appelle GET /api/dashboard/stats via React Query. Affiche
     4 cartes compteurs, un LineChart (tentatives/heure 7j), un BarChart (alertes
     par criticité), un PieChart (statuts), un tableau Top 10 IPs.
   - Alerts : MUI DataGrid des alertes, filtres criticité/statut/type, lien vers
     le détail.
   - AlertDetail : détail d'une alerte, changement de statut, fil de commentaires.
   - Logs : DataGrid en lecture seule des auth_logs.
   - Admin : gestion utilisateurs (réservé ROLE_ADMIN).

6. src/App.jsx : routing react-router-dom, QueryClientProvider, AuthProvider,
   ThemeProvider.

Ne mets aucune logique métier dans les composants : les appels API passent par
src/api/. Commente en français.
```

### Vérification

`npm run dev`, ouvrir `http://localhost:5173`, se connecter avec un compte des
fixtures, voir le dashboard se remplir.

---

# PHASE 6 — Fixtures & tests PHPUnit

**Objectif :** données de test reproductibles + tests automatisés. Couverture
backend cible ≥ 70 % (CDC OT6).

### Prompt Cursor

```
Contexte : backend Mini SOC. Créer les fixtures et la suite de tests PHPUnit.

1. src/DataFixtures/AppFixtures.php — volume aligné sur le Jalon 3 §VI
   (vérification pratique) :
   - 10 utilisateurs : 1 admin (admin@minisoc.local / Admin12345!),
     plusieurs analystes, quelques auditeurs. Mots de passe hashés Argon2id.
   - 500 auth_logs répartis sur les 30 derniers jours, mélange success/failed/
     blocked, plusieurs IP dont certaines avec des rafales d'échecs (pour
     déclencher la détection brute force).
   - 50 alertes de types et criticités variés, certaines assignées, statuts variés.
   - 120 alert_comments répartis sur les alertes.
   - Quelques entrées ip_reputation_cache (dont une IP score > 75).
   Utilise des références entre fixtures pour les relations.

2. tests/ — tests PHPUnit 11 :
   UNITAIRES (tests/Unit/) :
   - DetectionServiceTest : vérifie checkBruteForce (5 échecs => true, 4 => false),
     checkCredentialStuffing (3 comptes => true), isNightActivity (23h => true,
     14h => false). Mock les repositories.
   - AlertServiceTest : createAlert lie bien le log, anti-doublon fonctionne.
   - IpReputationCache::isExpired et isMalicious.
   FONCTIONNELS (tests/Functional/) :
   - AuthTest : login OK => 200 + token ; mauvais mdp => 401 ;
     11 tentatives/min => 429.
   - AlertApiTest : un auditeur ne peut PAS modifier une alerte (403) ;
     un analyste peut (200) ; les permissions RBAC sont respectées.
   - DashboardTest : GET /api/dashboard/stats => 200, structure JSON correcte.

3. Configure phpunit.xml.dist pour une base de test séparée, et fournis les
   commandes pour préparer la base de test et lancer la couverture.

Vise une couverture ≥ 70 % sur src/Service/ (le cœur métier).
```

### Commandes

```bash
# Base de test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Charger les fixtures (dev)
php bin/console doctrine:fixtures:load --no-interaction

# Lancer les tests + couverture
php bin/phpunit --coverage-text
```

### Vérification

Tous les tests verts. La couverture sur `src/Service/` doit afficher ≥ 70 %.

---

# PHASE 7 — Pipeline CI/CD GitHub Actions

**Objectif :** workflow à 6 étapes conforme au Jalon 4 §6.1.

### Prompt Cursor

```
Contexte : Mini SOC. Créer le workflow GitHub Actions conforme au Jalon 4 §6.1
(pipeline 6 étapes) et au CDC §4.2.

Crée .github/workflows/ci.yml déclenché sur push develop/main et pull_request
vers main. 6 jobs :

1. lint : PHPStan niveau 8 sur backend/src + ESLint (config Airbnb) sur frontend.
2. test : PHPUnit 11 sur le backend (service PostgreSQL 15 + Redis en
   "services:" du job, couverture ≥ 70 %) + Jest sur le frontend (≥ 60 %).
3. security : composer audit + npm audit.
4. docker-build : build des images backend et frontend, tag branch-sha.
5. deploy : ne s'exécute que sur push main — placeholder SSH + docker-compose pull.
   Mets-le en "needs" des jobs précédents et avec un environment "production".

Ajoute les outils manquants au backend :
   composer require --dev phpstan/phpstan
et au frontend la config ESLint Airbnb.

Le job test doit utiliser une vraie base PostgreSQL de service, pas SQLite, pour
rester fidèle à la production.
```

### Vérification

Pusher sur une branche `develop` → l'onglet Actions de GitHub montre les 6 jobs.
Les badges build/coverage peuvent être ajoutés au README (CDC critères qualité).

---

# Récapitulatif — checklist de fin de projet

Alignée sur les critères de réussite du CDC §7 :

- [ ] `docker-compose up` déploie l'app en < 10 min (CDC critère fonctionnel)
- [ ] Authentification JWT opérationnelle, RBAC 3 rôles testé
- [ ] Collecte automatique des auth_logs à chaque tentative
- [ ] Au moins 3 types de détection actifs (brute force, credential stuffing, IP)
- [ ] Dashboard interactif avec graphiques temps réel
- [ ] Gestion des alertes : workflow open → in_progress → resolved/false_positive
- [ ] Export CSV logs et alertes fonctionnel
- [ ] OWASP Top 10 : mesures des §6.2 Jalon 4 en place
- [ ] RGPD : emailHash SHA-256, purge 90j, anonymisation IP
- [ ] Couverture tests backend ≥ 70 %
- [ ] Pipeline CI/CD 6 étapes verte
- [ ] Documentation API OpenAPI accessible (/api/docs)
- [ ] README avec badges + commits conventionnels

---

# Ordre de priorité si le temps manque (MoSCoW du CDC §5.3)

Si vous prenez du retard, voici quoi sacrifier — dans cet ordre :

**Must Have (jamais sacrifier) :** Phases 0, 1, 2, 3 partielles (brute force),
dashboard basique, gestion alertes CRUD.

**Should Have (sacrifiable en dernier) :** enrichissement AbuseIPDB (Phase 4
partielle), credential stuffing, exports CSV, gestion utilisateurs admin.

**Could Have (sacrifiable d'abord) :** notifications email réelles, heatmap,
géolocalisation carte, MFA.

Un MVP qui tourne et se démontre vaut mieux qu'un projet complet à moitié cassé
le jour de la soutenance.
