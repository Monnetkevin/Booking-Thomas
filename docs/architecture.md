# Architecture du code

## Backend (Laravel) : Controller → Policy → Request → Service → Repository

Chaque fonctionnalité traverse ces couches, dans cet ordre, sans en sauter.

| Couche | Dossier | Rôle | Interdit |
|---|---|---|---|
| **Controller** | `app/Http/Controllers` | Reçoit la requête HTTP, appelle la Policy (`$this->authorize()` / `Gate`), délègue au Service, renvoie une `JsonResource` ou un code HTTP. Gère seulement ce qui est HTTP (session, cookies, redirections). | Logique métier, requêtes Eloquent |
| **Policy** | `app/Policies` | Décide si l'utilisateur peut faire l'action sur la ressource (`ApartmentPolicy::update`). Les groupes de routes gardent en plus le middleware `role:`. | Validation, effets de bord |
| **FormRequest** | `app/Http/Requests` | Valide et normalise les entrées (`rules()`, messages). | Logique métier, accès base |
| **Service** | `app/Services` | Logique métier : règles, calculs (`PriceCalculator`), transactions, événements, appels externes (Stripe, iCal). Reçoit ses dépendances par le constructeur. | Objets HTTP (`Request`, `request()`), Eloquent direct |
| **Repository** | `app/Repositories` | Accès aux données : une interface dans `Contracts/`, une implémentation dans `Eloquent/`, liées dans `RepositoryServiceProvider`. Cache de lecture si besoin. | Règles métier |

Autres règles :

- **Modèles** : relations, casts, scopes simples. Pas de logique métier.
- **Resources** (`app/Http/Resources`) : forme des réponses JSON ; jamais de modèle renvoyé brut.
- **Enums** (`app/Enums`) pour tous les statuts et types.
- **Erreurs métier** : un Service lève une sous-classe de `App\Exceptions\DomainException` (code HTTP, `code` stable comme `apartment_not_publishable`, message traduit, `details` éventuels). La SPA s'appuie sur `ApiError.code`, jamais sur le texte.
- **Suppressions** : logiques (`SoftDeletes`) pour ce qui est référencé par l'historique (logements).
- **Fichiers** : originaux privés (disque `local`), versions publiques ré-encodées (Medialibrary, file d'attente).
- Seeders et factories peuvent créer des modèles directement (données, pas métier) ; ils passent par un Repository quand il gère un cache (`SettingRepositoryInterface`).
- Tests : un test fonctionnel par endpoint (droits, validation, effet) et des tests de Service pour les règles métier, sur PostgreSQL.

Exemple (connexion) : `SessionController@store` → `LoginRequest` (règles) → `AuthService::login()` (blocage, compte actif, mot de passe) → `UserRepositoryInterface::findByEmail()`. Pas de Policy ici : la route est publique.

## Frontend (React) : principes SOLID

| Principe | Application |
|---|---|
| **S** — responsabilité unique | Un composant affiche (`LoginForm`), un autre orchestre données et navigation (`LoginPage`). Appels d'API, cache et affichage sont dans trois fichiers distincts. |
| **O** — ouvert/fermé | Les listes déclaratives s'étendent sans modifier le composant qui les affiche (`admin/navigation.tsx` → `AdminLayout`). Variantes de composants par props (`Button variant`). |
| **L** — substitution | Toute implémentation d'une interface est interchangeable (`HttpClient` réel ou factice dans les tests). |
| **I** — interfaces ciblées | Props minimales et typées ; un service par domaine (`AuthService`, plus tard `ApartmentService`…), pas de client fourre-tout. |
| **D** — inversion de dépendance | Les services dépendent de l'interface `HttpClient` (`lib/http.ts`), pas de `fetch` ; les hooks reçoivent le service en paramètre par défaut, remplaçable en test. |

Organisation par domaine :

```
src/<domaine>/            ex. src/admin/auth/
  types.ts                types du domaine
  <domaine>Service.ts     appels d'API (createXService(http) + instance par défaut), sans React
  use<Domaine>.ts         hooks React Query (cache, invalidation)
  *.test.ts               tests Vitest des services et fonctions pures
src/components/ui/        composants de base sans logique métier
src/components/feedback/  chargement, erreurs
src/lib/                  transport HTTP, client React Query, utilitaires
```

Site public (`src/site/`) :

```
site/catalog/             données des logements : service, hooks, fonctions pures (apartmentFacts, searchParams), useSearchForm
site/components/          composants d'affichage réutilisables, alimentés par leurs props
                          (PageSection, ApartmentTile, ApartmentMosaic, FeatureList, StepList, FactCards, FaqList, CtaBanner…)
site/home/                propre à l'accueil : textes typés (useHomeContent), haut de page, bloc hôte
site/seo/                 données structurées schema.org
site/navigation.ts        liens de l'en-tête et du pied de page (listes déclaratives)
site/pages/               pages : elles assemblent des sections, sans styles ni logique en propre
```

Une page ne contient ni composant interne ni classes de style répétées : une variante de bouton
s'ajoute à `buttonClasses` (`dark`, `xl`…), un bloc réutilisable devient un composant de `site/components/`.

Les prix, disponibilités et droits sont toujours décidés par l'API ; le front affiche.
