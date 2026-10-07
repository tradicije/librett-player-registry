# Razvojni standardi

[English](../en/DEVELOPMENT.md)

Još nema koda/alata za pokretanje. [Arhitektura](ARCHITECTURE.md) definiše obavezne granice modula, a [plan](PLAN.md) etape.

Na datum dizajna (7. oktobar 2026) PHP stranica podrške navodi 8.5 kao najnoviju podržanu granu. Cilj je savremen PHP 8.5 razvoj; pre tvrdnje o deploy-u odobriti WordPress/PHP/database matricu. PHP-FIG PER Coding Style 3.1 zamenjuje/proširuje PSR-12; Composer PSR-4, strict types i izričiti tipovi koriste se u nezavisnim modulima.

Održavanu statičku analizu, formatter, testove i audit izabrati u fazi 0. Tačne install/check/build komande navesti tek kada postoji konfiguracija. Frontend, ako ga bude, koristi strict TypeScript i odvojene view/state/transport module; framework nije odabran.

Domain/application paketi učitavaju se bez WordPress bootstrap-a. Adapter integration provere imaju posebno WordPress okruženje. Planirani CI proverava smer zavisnosti, syntax/style/statičku analizu, unit/adapter slučajeve, migracije, izvoz, recovery primere i sadržaj paketa. Testovi zahtevaju autorizaciju zadatka prema AGENTS.md; u ovom docs bootstrap-u nisu dodati niti pokrenuti.

Release ZIP sadrži runtime kod, potrebne zavisnosti i notices, ne tajne, testove, baze igrača niti recovery materijal. Verzija/tag ne bira se pre implementacije.

Reference: [PHP podrška](https://www.php.net/supported-versions.php), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [PSR-4](https://www.php-fig.org/psr/psr-4/).
