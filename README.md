# Booking Thomas

Site vitrine et gestion d'appartements en location courte durée : réservation directe avec paiement Stripe,
synchronisation des calendriers Airbnb par iCal et back-office pour l'hôte.

- **Spécifications** : voir `docs/` et la spec technique partagée.
- **Backlog** : [issues GitHub](https://github.com/Monnetkevin/Booking-Thomas/issues), regroupées en 9 jalons.

## Structure du dépôt

| Dossier     | Contenu                                                                  |
|-------------|--------------------------------------------------------------------------|
| `backend/`  | API JSON Laravel (PHP 8.4, Sanctum, PostgreSQL, Redis)                   |
| `frontend/` | SPA React + TypeScript (Vite, Tailwind, React Router, TanStack Query) : site public et `/admin` |
| `infra/`    | Docker de développement, puis Nginx, Supervisor, déploiement, sauvegardes |
| `docs/`     | Documentation technique et décisions                                     |

## Démarrage en local

Prérequis : Docker (avec Compose), Node.js 22+ et npm.

```bash
# 1. API : dépendances PHP et clé, puis services (PHP, PostgreSQL, Redis, Mailpit)
cp backend/.env.example backend/.env
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link   # photos publiques

# 2. Front (sur la machine hôte)
cd frontend
npm install
npm run dev
```

| Service                  | URL                       |
|--------------------------|---------------------------|
| SPA (Vite)               | http://localhost:5173     |
| API Laravel              | http://localhost:8000/api |
| Mailpit (e-mails reçus)  | http://localhost:8025     |

Le service `queue` traite les tâches de fond (conversions des photos en WebP et AVIF, e-mails).
| PostgreSQL               | localhost:5432            |

Vite relaie `/api` et `/sanctum` vers Laravel : la SPA et l'API partagent la même origine, sans CORS.

Compte admin créé par le seeder : variables `ADMIN_EMAIL` et `ADMIN_PASSWORD` de `backend/.env`.

## Commandes utiles

```bash
# Backend
docker compose exec app php artisan test     # tests Pest (sur PostgreSQL)
docker compose exec app vendor/bin/pint      # style
docker compose exec app vendor/bin/phpstan   # analyse statique (Larastan)

# Frontend
npm run lint && npm run typecheck && npm test && npm run build
```

## Façon de travailler

| Branche | Rôle | Alimentée par |
|---|---|---|
| `jalon-N/<nom>` | Travail d'un jalon, un commit par issue (`Closes #12`) | — |
| `develop` | Intégration, branche par défaut | PR depuis les branches de jalon |
| `staging` | Recette avec le client (environnement de test, clés Stripe de test) — créée au moment de la recette | PR depuis `develop` |
| `main` | Production | PR depuis `staging` (ou `develop` avant la recette) |

`develop`, `staging` et `main` sont protégées : pull request obligatoire et CI verte.
