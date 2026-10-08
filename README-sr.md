# LibreTT Player Registry

Prenosiv registar igrača i klubova sa javnim profilima, WordPress administracijom i jednosmernim preuzimanjem u LibreTT Desktop.

![Licenca: AGPL-3.0-or-later](https://img.shields.io/badge/License-AGPL--3.0--or--later-3da639.svg)
![Status: Razvoj](https://img.shields.io/badge/Status-Development-818cf8.svg)
![WordPress adapter: razvoj](https://img.shields.io/badge/WordPress_adapter-development-21759b.svg)

[English](README.md)

**Status: neobjavljen razvojni katalog.** Implementirani su privatna administracija igrača/klubova, članstva/alias-i, licence, zaštićene fotografije, izričita javna objava, pretraga/API, ograničen JSON uvoz/izvoz i jednosmerni Desktop uvoz. Pogledaj [stvarni obim i provere](docs/sr/IMPLEMENTATION_STATUS.md).

## Šta pravimo

Jedan dodatak **LibreTT Player Registry** (`librett-player-registry`) pravi nov prazan glavni registar; hostovanje replike je planirano. Ima administraciju, pretražive javne profile i API; WordPress je prvi adapter, a ne deo domena.

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

## Integracija sa LibreTT Desktop — razvoj

Korisnik LibreTT Desktop aplikacije može da preuzme bazu igrača iz ovog registra i čuva je lokalno za rad bez interneta. Podaci se prenose **u jednom smeru: online registar → LibreTT Desktop**. Kasnije izmene profila u Desktop aplikaciji ostaju na korisnikovom računaru; osvežavanje podataka iz registra čuva izričite lokalne izmene i istorijske snimke turnira.

Ovaj kanal za preuzimanje ne šalje izmene lokalnih profila, privatne beleške, dolaske, uplate ni podatke turnira nazad registru. Time štiti lokalne podatke korisnika od slanja kroz ovu integraciju i online bazu od izmena napravljenih u Desktop aplikaciji. Integracija je implementirana u razvojnom Desktop kodu; nije objavljen release.

## Izbor licence baze — razvoj

Administrator bira licencu baze podataka u podešavanjima dodatka u `wp-admin`: **ODbL 1.0**, **CC0 1.0**, **CC BY 4.0**, **CC BY-SA 4.0**, **All rights reserved** ili **Custom**. Za Custom administrator može da navede URL licence ili da otpremi dokument licence. Licenca baze se ne bira automatski. Tok je implementiran u razvojnom dodatku.

Licenca baze određuje se odvojeno od AGPL-3.0-or-later licence dodatka, odobrenja za objavu ličnih podataka i dozvola za fotografije.

## Aktuelna etapa

Razvojni katalog obuhvata prazan single-site registar, javne profile, JSON uvoz/izvoz i Desktop integraciju. Slede operatorov pregled i release spremnost, pa proverene replike, predlozi i oporavak. Dizajn predviđa kasnije bezbednosne funkcije; one još nisu implementirane garancije.

## Dokumentacija

Počni od [indeksa dokumentacije](docs/sr/INDEX.md).

- [Plan razvoja](docs/sr/PLAN.md)
- [Arhitektura](docs/sr/ARCHITECTURE.md)
- [Model podataka i životni ciklus](docs/sr/DATA_MODEL.md)
- [Predlog politike objavljivanja](docs/sr/PUBLICATION_POLICY.md)
- [Predlog protokola](docs/sr/PROTOCOL.md)
- [Ugovor snimka — nacrt 1](docs/sr/SNAPSHOT_CONTRACT.md)
- [Desktop integracija](docs/sr/DESKTOP_INTEGRATION.md)
- [Poverenje i oporavak](docs/sr/TRUST_AND_RECOVERY.md)
- [Administratorski tokovi](docs/sr/ADMIN_GUIDE.md)
- [Arhitektonska odluka](docs/adr/0001-registry-foundation.md)
- [Doprinos projektu](CONTRIBUTING-sr.md), [bezbednost](SECURITY-sr.md), [uputstva agentima](AGENTS.md)

Dokumentaciona priprema faze 0 je završena: [spremnost i uslovi](docs/sr/PHASE_0_READINESS.md), [šema/primeri](docs/sr/CONTRACT_VALIDATION.md) i [pregledani ciljevi kompatibilnosti/alata](docs/sr/COMPATIBILITY_AND_TOOLING.md). Prvi implementacioni korak i njegove provere sada su zabeleženi odvojeno.

## Razvoj

Composer zavisnosti i test konfiguracije su zabeležene. Prati [razvojno podešavanje](docs/sr/DEVELOPMENT.md) na privremenom WordPress sajtu; sveža aktivacija pravi prazne setup/tabele privatnih nacrta, a poseban administratorski zahtev UUID registra. Nema produkcionog installer-a ili ZIP izdanja.

## Autor i licenca

Copyright (C) 2026 Aleksa Dimitrijević.

Kod i projektna dokumentacija koriste **GNU Affero General Public License, verziju 3 ili bilo koju kasniju** (`AGPL-3.0-or-later`); pogledaj [LICENSE](LICENSE). Komercijalna distribucija dozvoljena je uz poštovanje licence. Softver se pruža bez garancije.

Licenca softvera ne licencira automatski baze igrača, lične podatke, fotografije niti materijal trećih lica. Njihove dozvole i uslovi objave određuju se posebno. Ne tvrdi se da je brend registrovan žig.
