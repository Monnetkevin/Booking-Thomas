# Booking Thomas — consignes pour les agents

- Langue : français (code, commentaires, commits, messages d'interface).
- Architecture obligatoire, détaillée dans `docs/architecture.md` :
  - backend : Controller → Policy → FormRequest → Service → Repository (interfaces dans `app/Repositories/Contracts`, liées dans `RepositoryServiceProvider`) ;
  - frontend : principes SOLID (service API ↔ hooks React Query ↔ composants, dépendance à `HttpClient`).
- PHP tourne uniquement dans Docker : `docker compose exec app php artisan test`, `vendor/bin/pint`, `vendor/bin/phpstan`.
- Front : `cd frontend && npm run lint && npm run typecheck && npm test && npm run build`.
- Montants en centimes, dates de séjour `[arrivée, départ[`, prix calculés uniquement côté serveur.
- Livraison : une branche `jalon-N/<nom>` par jalon, un commit par issue (`Closes #n`), une pull request vers `develop` ; puis `develop` → `staging` (recette) → `main` (production). CI verte avant toute fusion.
