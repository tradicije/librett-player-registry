# LibreTT Player Registry

Prenosiv registar igrača i klubova sa javnim profilima, WordPress administracijom i jednosmernim preuzimanjem u LibreTT Desktop.

![Licenca: AGPL-3.0-or-later](https://img.shields.io/badge/License-AGPL--3.0--or--later-3da639.svg)
![Status: Planiranje](https://img.shields.io/badge/Status-Design-818cf8.svg)
![WordPress adapter: planiran](https://img.shields.io/badge/WordPress_adapter-planned-21759b.svg)

[English](README.md)

**Status: plan i dokumentacija.** Ovaj repozitorijum još nije WordPress dodatak koji može da se instalira. Nema objavljene verzije, potvrđenih minimalnih sistemskih zahteva ni implementiranog protokola sinhronizacije.

## Šta pravimo

Jedan dodatak **LibreTT Player Registry** (`librett-player-registry`) može da napravi novi prazan registar ili hostuje repliku postojećeg registra. Ima administraciju, pretražive javne profile i API; WordPress je prvi adapter, a ne deo domena.

Savez ili klub iz bilo koje države može napraviti svoju bazu. Dodatak ne sadrži srpsku bazu igrača. Isti registar može da se preseli na drugi domen ili prikaže na više sajtova bez promene identiteta igrača.

## Osnovne odluke

- Custom tabele za podatke registra; bez zavisnosti od CPT-ova igrača/klubova i postmeta.
- Nezavisni domen i aplikacioni sloj, sa zamenljivim adapterima za skladište, HTTP i medije.
- Trajni identitet registra i UUID igrača/klubova, odvojeni od URL slug-ova profila.
- Glavni registar objavljuje prihvaćene promene. Replike prikazuju proverene kopije i mogu slati predloge, umesto direktnog uređivanja zajedničkih zapisa.
- Odvojen ključ za oporavak, čuvan van servera, može odobriti novog glavnog izdavača ako stari hosting nestane. Sukobi autoriteta i nedostupne promene zahtevaju izričito rešavanje.
- Verzionski JSON snimci i tok promena koriste isti model; nisu sirovi WordPress database dump.
- LibreTT Desktop preuzima zapise u lokalnu bazu. Korisnik ih slobodno menja lokalno; te izmene se ne šalju online registru.
- Javne baze, mediji i privatni administrativni podaci imaju različita pravila objave i bekapa.

## Predlog prve etape

Pravljenje i uređivanje praznog registra na jednom WordPress sajtu, javni profili igrača/klubova i proverljiv JSON uvoz/izvoz. Zatim desktop integracija, pa proverene replike, predlozi i oporavak. Dizajn predviđa kasnije bezbednosne funkcije; one još nisu implementirane garancije.

## Dokumentacija

Počni od [indeksa dokumentacije](docs/sr/INDEX.md).

- [Plan razvoja](docs/sr/PLAN.md)
- [Arhitektura](docs/sr/ARCHITECTURE.md)
- [Model podataka i životni ciklus](docs/sr/DATA_MODEL.md)
- [Predlog politike objavljivanja](docs/sr/PUBLICATION_POLICY.md)
- [Predlog protokola](docs/sr/PROTOCOL.md)
- [Desktop integracija](docs/sr/DESKTOP_INTEGRATION.md)
- [Poverenje i oporavak](docs/sr/TRUST_AND_RECOVERY.md)
- [Administratorski tokovi](docs/sr/ADMIN_GUIDE.md)
- [Arhitektonska odluka](docs/adr/0001-registry-foundation.md)
- [Doprinos projektu](CONTRIBUTING-sr.md), [bezbednost](SECURITY-sr.md), [uputstva agentima](AGENTS.md)

## Razvoj

Komande za instalaciju, build i testiranje još nisu određene. Alati, minimalne PHP/WordPress verzije, zahtevi baze i CI biće izabrani u prvoj implementacionoj etapi. Ovaj folder dokumentacije nije funkcionalan plugin.

## Autor i licenca

Copyright (C) 2026 Aleksa Dimitrijević.

Kod i projektna dokumentacija koriste **GNU Affero General Public License, verziju 3 ili bilo koju kasniju** (`AGPL-3.0-or-later`); pogledaj [LICENSE](LICENSE). Komercijalna distribucija dozvoljena je uz poštovanje licence. Softver se pruža bez garancije.

Licenca softvera ne licencira automatski baze igrača, lične podatke, fotografije niti materijal trećih lica. Njihove dozvole i uslovi objave određuju se posebno. Ne tvrdi se da je brend registrovan žig.
