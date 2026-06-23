# ProgressIA

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Type Support for `.vue` Imports in TS

TypeScript cannot handle type information for `.vue` imports by default, so we replace the `tsc` CLI with `vue-tsc` for type checking. In editors, we need [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar) to make the TypeScript language service aware of `.vue` types.

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Type-Check, Compile and Minify for Production

```sh
npm run build
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```

## Prototype ProgressIA (fr)

Ce dépôt contient un prototype Vue 3 + Tailwind CSS pour créer et éditer des cours composés de séances rédigées en Markdown.

- Données fictives en JSON: [src/data/mock-courses.json](src/data/mock-courses.json#L1-L200)
- Store Pinia: [src/stores/courses.ts](src/stores/courses.ts#L1-L200)
- Pages principales: Home, Catalogue, Cours, Séance, Back-office, Dashboards (étudiant/enseignant)

Pour lancer le prototype:

```sh
npm install
npm run dev
```

## Backend (API Symfony)

Le front est désormais connecté à une **API découplée** Symfony 8 + API Platform, avec authentification **JWT**. Le code vit dans [backend/](backend/) (monorepo).

Stack : Symfony 8.1, API Platform, Doctrine ORM, MariaDB (Docker), LexikJWTAuthenticationBundle, Nelmio CORS.

### Lancer le backend

```sh
cd backend
docker compose up -d                              # MariaDB (port hôte 3309)
php bin/console doctrine:migrations:migrate -n    # schéma
php bin/console doctrine:fixtures:load -n         # données de démo
symfony server:start -d --no-tls                  # API sur http://127.0.0.1:8000
```

Comptes de démonstration (créés par les fixtures) :

- Enseignant — `teacher@progressia.test` / `teacher`
- Étudiant — `student@progressia.test` / `student`

### Endpoints principaux

- `POST /api/login` → renvoie un token JWT
- `GET /api/me` → profil de l'utilisateur authentifié
- `GET /api/courses` (public) → catalogue imbriqué (cours → séances → chapitres)
- `POST|PATCH|DELETE /api/courses|sessions|chapters` → CRUD (réservé `ROLE_TEACHER`)
- Docs interactives : http://127.0.0.1:8000/api

### Connexion front ↔ back

Le front lit l'URL de l'API dans [.env](.env) (`VITE_API_URL`). La couche d'appel est dans [src/api/client.ts](src/api/client.ts) (fetch + Bearer JWT). Les stores [auth](src/stores/auth.ts) et [courses](src/stores/courses.ts) consomment l'API.

> **État de la migration** : l'arbre cours/séances/chapitres et l'authentification passent par l'API. La **gamification** (progression, points, badges, uploads, évaluations) est encore en `localStorage` — prévue en phase 2 (entités `Progress`/`Badge`/`Evaluation`/`Upload` côté API).

