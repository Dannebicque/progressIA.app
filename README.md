# ProgressIA

ProgressIA est une application Vue 3 avec un backend Symfony 8 / API Platform.

## Architecture

- Frontend : Vue 3, TypeScript, Vite, Pinia, Tailwind CSS.
- Backend : Symfony 8.1, API Platform, Doctrine ORM, JWT.
- Base de données : MariaDB.
- Développement : Docker Compose avec hot reload Vite et sources Symfony montées en volume.
- Production : images Docker immuables publiées sur GHCR et routées par Traefik.

Le frontend reste à la racine du dépôt. Le backend Symfony se trouve dans `backend/`.

## Développement avec Docker

La configuration de développement est définie dans `compose.yaml`.

```sh
docker compose up -d
```

Au premier démarrage, Docker installe automatiquement les dépendances Composer et npm et génère les clés JWT de développement si elles n'existent pas.

Services :

- Front Vue / Vite : http://localhost:5173
- API Symfony : http://localhost:8080
- Documentation API Platform : http://localhost:8080/api
- MariaDB : accessible uniquement sur le réseau Docker interne

Commandes utiles :

```sh
# Logs
docker compose logs -f

# Console Symfony
docker compose exec php php bin/console

# Migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Fixtures
docker compose exec php php bin/console doctrine:fixtures:load

# Composer
docker compose exec php composer install

# Arrêt
docker compose down
```

Les sources Vue et Symfony sont montées en volumes : il n'est pas nécessaire de reconstruire les images à chaque modification.

## Configuration frontend

Le frontend utilise `VITE_API_URL` pour joindre l'API. En développement Docker, cette valeur est injectée par `compose.yaml` et vaut `http://localhost:8080`.

En production, l'image est construite avec :

```text
VITE_API_URL=https://api.progressia.app
```

## Production

La configuration de production est définie dans `compose.prod.yaml`.

Contrairement au développement, le code source n'est pas monté depuis le VPS. Les trois images suivantes contiennent les artefacts applicatifs :

```text
ghcr.io/dannebicque/progressia-front:<commit-sha>
ghcr.io/dannebicque/progressia-api:<commit-sha>
ghcr.io/dannebicque/progressia-api-nginx:<commit-sha>
```

Traefik expose :

```text
https://progressia.app      -> frontend Vue
https://api.progressia.app  -> nginx -> PHP-FPM -> Symfony/API Platform
```

Les réseaux Docker externes `web` et `shared_internal` doivent déjà exister sur le VPS. `web` est utilisé par Traefik et `shared_internal` permet au backend de joindre les services partagés, notamment la base de données de production.

### Variables de production

Copier `.env.prod.example` vers `.env.prod.local` dans le répertoire de déploiement du VPS et renseigner les vraies valeurs :

```sh
cp .env.prod.example .env.prod.local
```

`.env.prod.local`, `.env.version` et le répertoire `jwt/` ne doivent jamais être commités.

## CI/CD GitHub Actions

### CI

`.github/workflows/ci.yml` est exécuté sur les pull requests et sur `main`.

Il vérifie :

- l'installation et le build Vue/TypeScript ;
- `composer.json` ;
- l'installation des dépendances Symfony ;
- la configuration YAML Symfony ;
- le conteneur de services Symfony.

### Déploiement

`.github/workflows/deploy.yml` se déclenche uniquement après le succès de la CI sur `main`.

Le workflow :

1. construit les images frontend, PHP/Symfony et nginx API ;
2. les publie sur GitHub Container Registry avec le SHA du commit et le tag `latest` ;
3. copie `compose.prod.yaml` vers le VPS ;
4. télécharge les nouvelles images ;
5. redémarre les services ;
6. génère les clés JWT si nécessaire ;
7. exécute les migrations Doctrine ;
8. reconstruit le cache Symfony.

Secrets GitHub Actions requis :

```text
SSH_HOST
SSH_PORT
SSH_USER
SSH_PRIVATE_KEY
TARGET_COMPOSE_DIR
GHCR_USERNAME
GHCR_TOKEN
```

`TARGET_COMPOSE_DIR` peut par exemple valoir `/var/www/progressia`.

`GHCR_TOKEN` doit disposer au minimum du droit `read:packages` afin que le VPS puisse télécharger les images privées depuis GHCR.

## Développement frontend hors Docker

Il reste possible de lancer uniquement le frontend localement :

```sh
npm install
npm run dev
```

La variable `VITE_API_URL` de `.env` est alors utilisée.

## Scripts frontend

```sh
npm run build
npm run lint
npm run format
```

## Backend

Le backend Symfony/API Platform se trouve dans `backend/`.

Principaux endpoints :

- `POST /api/login` : authentification JWT ;
- `GET /api/me` : profil de l'utilisateur authentifié ;
- `GET /api/courses` : catalogue ;
- `POST|PATCH|DELETE /api/courses|sessions|chapters` : CRUD enseignant ;
- `/api` : documentation API Platform.
