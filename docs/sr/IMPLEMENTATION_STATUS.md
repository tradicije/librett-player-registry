# Bootstrap faze 1 — implementiran obim

[English](../en/IMPLEMENTATION_STATUS.md)

Status: neobjavljena razvojna verzija faze 1 sa prvobitnim proverenim bootstrap-om i neproverenim proširenjem privatnim nacrtima. Cela faza 1 nije završena. Nema produkcionog deployment-a ili objavljenog ZIP-a.

## Prvobitna bootstrap implementacija

- Tanak WordPress ulaz i Composer PSR-4 composition root; RegistryIdentity domen/aplikacija ostaju nezavisni od WordPress-a.
- Razvojni preflight: 64-bit PHP 8.5, WordPress 7.1.3–7.1.x single-site, MariaDB 10.11 ili MySQL 8.4, potrebne tekst/database/media ekstenzije i InnoDB. Buduće grane/multisite se odbijaju; to je obim razvoja, ne univerzalno obećanje runtime podrške.
- Aktivacija prvobitnog bootstrap-a pravila je tačno tri custom tabele: migrations, identity setup singleton i privatni identity audit. Ne pravi UUID registra, igrača, klub ili politiku objave i administratoru daje samo implementiran settings capability.
- Početna migracija koristi connection-scoped advisory lock, checksum/started/completed stanje, proverava kolone/indekse/engine/kolaciju i nastavlja prekinuto početno pravljenje. Odbija drugu/buduću šemu i netransakcione tabele. Upgrade/down-migration okvir i privatni restore još nisu implementirani.
- POST sa capability/nonce proverom pravi jedan imenovan primary kroz ramsey/uuid v4. Identitet i audit upisuju se zajedno; ponovljen setup daje sukob i čuva prvobitan registar.
- Engleski source UI i priložen srpski latinični prevod; escape naziva/UUID-a. Replica setup i javni profili ne postoje.
- Deaktivacija/uninstall čuvaju tabele, identitet i capabilities. Destruktivan purge ne postoji.

## Privatni nacrti igrača i klubova — aktuelni razvojni korak

Implementirano u izvornom kodu 2026-10-08; neobjavljeno i bez runtime provere. Odvojeni moduli Players i Clubs omogućavaju privatno pravljenje, pretragu naziva po 50 rezultata, uređivanje, arhiviranje i izričito vraćanje izborom Aktivno. Igrači imaju prikazno ime, ime/prezime, neobavezno godište, državu, region i biografiju kao običan tekst. Klubovi imaju naziv, skraćenicu, državu i region. Ista imena su dozvoljena bez spajanja. Alias-i klubova, članstva, mapiranje duplikata, mediji, objavljivanje i purge još nisu implementirani.

Aplikacioni upiti i komande proveravaju `librett_registry_edit_profiles` i podešen primary kontekst. Svaka izmena kroz formu proverava i POST, WordPress capability i nonce radnje. UUID pravi ubrizgani ramsey/uuid v4 generator. Očekivana privatna revizija sprečava prepisivanje novijih izmena; nacrt i audit njegovog modula upisuju se u jednoj transakciji. Svako prihvaćeno čuvanje povećava privatnu reviziju, uključujući neizmenjenu formu. Arhiviranje čuva podatke i UUID; nema javni efekat jer objavljivanje ne postoji.

Migracija `002_private_drafts` dodaje četiri InnoDB tabele (igrači, klubovi i njihovi zasebni privatni auditi), uz checksum, advisory lock, nastavak prekinutog pravljenja i strukturnu proveru. Sveža nepodešena instalacija izvršava obe migracije i pravi sedam praznih/setup tabela bez UUID-a registra i primera profila. Postojeći podešen registar čuva identitet i podatke; administrator na strani **Šema privatnih nacrta**, uz capability/nonce, potvrđuje proverenu rezervnu kopiju baze/medija/konfiguracije i sačuvan odgovarajući kod pre instalacije ili nastavka dopunske migracije. Izjava nije implementacija provere bekapa ili obnove. Nepotpuna/neodgovarajuća šema blokira strane nacrta i izmene kroz forme. Checksum početne migracije ostaje isti. Opšti upgrade/down-migration i backup restore okvir i dalje ne postoje.

Provere u ovoj sesiji ograničene su na pregled izvornog koda/dokumentacije i whitespace-a izmena. PHP, Composer i gettext msgfmt nisu dostupni u ovom Linux okruženju; za ovaj korak nisu izvršene PHP syntax, PHPUnit, PHPStan, formatter, WordPress/database ili browser provere. Srpski PO je dopunjen i MO generisan Python standardnom bibliotekom; gettext validacija i runtime pregled prevoda ostaju za proveru. Raniji rezultati ispod pokrivaju samo prvobitan bootstrap i ne proveravaju ovo proširenje. Postojeći bootstrap testovi ostaju neizmenjeni; novi draft/upgrade failure slučajevi zahtevaju posebno odobrenu proveru.

## Stvarne provere

Dana 2026-10-07, PHP 8.5.5 CLI, WordPress 7.1.3, MariaDB 10.11.19 InnoDB, macOS/Homebrew, GD:

| Provera | Rezultat |
| --- | --- |
| PHPUnit 13.4.1 unit | Prošlo 16 testova, 42 assertions |
| PHPUnit 13.4.1 bootstrapped WordPress integracija | Prošlo 14 testova, 43 assertions |
| PHPStan 2.3.0 | Level max za sve `src/` klase, bez grešaka; WordPress stubs 7.1.2 |
| PHP CS Fixer 3.95.27 | 23 PHP fajla, bez preostalih promena prema podržanom `@PER-CS3x0` |
| Composer 2.10.3 | Stroga validacija manifesta prošla; audit pri instalaciji nije prijavio advisories |
| gettext msgfmt | Srpski katalog kompajliran/proveren |

Jezgro pokriva dozvole, dužinu/Unicode/kontrole/kodiranje naziva, UUID i nepodržana okruženja. Integracija pokriva praznu/ponovljenu šemu, sukob setup-a, audit rollback, prekinut DDL, checksum/buduću šemu, MyISAM, ugnježdene transakcije, migration lock, capability/nonce, uspešan POST/escape, aktivaciju/deaktivaciju/uninstall čuvanje. Podaci su izmišljeni; email i spoljni WordPress HTTP su blokirani.

Zaključan PHP CS Fixer nema PER 3.1 preset. Cilj ostaje PER 3.1, ali automatska provera trenutno pokriva podržana 3.0 pravila. Ne tvrdi se potpuna automatska 3.1 pokrivenost. Nema vizuelne/accessibility provere u browser-u, Linux deployment-a, MySQL provere, iscrpne crash/migration vežbe, produkcionog restore-a ili signing/import/media testova.

## Preostali rad faze 1

Dovršavanje Players/Clubs članstava, alias-a, izričitog mapiranja duplikata i purge tokova; politika/odobrenja objave; zaštićeni mediji; javna projekcija/pretraga/rute; ograničen JSON parser/šema/uvoz/izvoz; checkpoint-i/zavisna povlačenja; upgrade, izričit purge i release provere. Opis/parser zavisnosti dolaze tek u import koraku; bootstrap dodaje samo ramsey/uuid i njegove runtime zavisnosti. Prazni budući moduli se ne dodaju.
