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
# 1. Services : PHP, PostgreSQL, Redis, Mailpit
cp backend/.env.example backend/.env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

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
npm run lint && npm run typecheck && npm run build
```

## Façon de travailler

- `main` est protégée : tout passe par une pull request dont la CI est verte.
- Une branche par jalon ou par issue, commits qui référencent l'issue (`Closes #12`).
