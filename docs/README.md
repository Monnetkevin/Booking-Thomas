# Documentation

- [Spécifications techniques](https://claude.ai/artifact/Ra1hBHiuU4Cx6hdkW8rdqV) : document de référence (architecture, modèle de données, règles de prix, iCal, Stripe).
- [Maquette](https://claude.ai/artifact/9po5xhZyae3mjDVyJM6mor) : écrans publics et back-office.
- [Backlog](https://github.com/Monnetkevin/Booking-Thomas/issues) : issues regroupées en 9 jalons.

## Conventions

- Dates de séjour en `date` (sans heure) ; un séjour occupe `[arrivée, départ[`.
- Montants en entiers, en centimes d'euro.
- Toute nuit occupée est une ligne de `availability_blocks` ; PostgreSQL refuse deux blocs actifs qui se recouvrent.
- Les prix sont toujours calculés par le serveur (`PriceCalculator`), jamais par le navigateur.
