# Završetak faze 0 i ulaz u fazu 1

[English](../en/PHASE_0_READINESS.md)

Datum: 2026-10-07. Status: dokumentacija i inženjerska osnova završeni prema zahtevu da se završi priprema pre faze 1. Ovo je pregled dizajna, ne runtime provera, izvršena interoperabilnost, bezbednosni audit ili odobrenje produkcije. Nema plugin koda, instalacije zavisnosti, izdanja ili stvarne baze.

## Odluke i artefakti

| Zahtev faze 0 | Zabeležen rezultat |
| --- | --- |
| Katalog/objava | Prvi javni katalog, dozvoljena projekcija, privatni nacrti/dokazi i izričita objava/povlačenje: [politika](PUBLICATION_POLICY.md), ADR 0002 |
| Granice modula | Javni interfejsi, vlasništvo tabela, graf bez ciklusa, portovi i capabilities: [ugovori modula](MODULE_CONTRACTS.md) |
| Javna šema/identitet | Nepotpisana v1 JSON Schema, UUID-i po tipu, decimalni brojači, odsustvo polja, granice i nezavisan uvoz sa mapiranjem: [snimak](SNAPSHOT_CONTRACT.md), [validacija](CONTRACT_VALIDATION.md), ADR 0003 |
| Skladište/transakcije | InnoDB unit of work, privatne/javne revizije, zavisna uklanjanja, potvrde uvoza, zaštićeni mediji i nastavljiv DDL/migration restore: [skladište](STORAGE_AND_MIGRATIONS.md) |
| Runtime/alati | Pregledani upstream izvori; PHP 8.5 / WordPress 7.1.3 / MariaDB 10.11 glavni cilj i MySQL 8.4 alternativa; Composer/PHPStan/PHP CS Fixer/PHPUnit: [matrica](COMPATIBILITY_AND_TOOLING.md) |
| Poverenje/oporavak | Vezivanje bajtova, početno poverenje, odvojeni root/server ključevi, lanac/rotacija/sukobi/replay i zastareo restore: [autentifikovana objava](AUTHENTICATED_PUBLICATION.md) |
| Dokumentacija | Srpski/engleski dokumenti, primeri ugovora, ADR 0004, indeksi i changelog |

Matrica je u ovoj osnovi dizajna odobrena kao inženjerski cilj implementacije, ne kao proverena podrška/deployment obećanje. Tačni patch build-ovi, ekstenzije i hosting potvrđuju se pre deployment-a. Šema je strukturni ugovor nepotpisanog javnog v1; obavezna parser/semantička pravila iz pratećih dokumenata takođe su deo ugovora. Autentifikovani formati ostaju predlozi i nedostupni do kasnijih pregleda.

## Izričite odluke o obimu

U fazi 1 nema privatnog kataloga, multisite-a, istorijskog članstva/dostignuća, naloga igrača ili SPA. Nezavisan uvoz dobija nove identitete/mapiranja; javni snimak nije organizacioni restore. Tombstone izvora je informacija za pregled, ne automatsko primary brisanje. Javne su samo odobrene projekcije. Odobrenje stvarnih podataka, maloletnici, prava i retention operatora su deployment ulazi, ne izmišljene podrazumevane dozvole.

Prvi implementacioni zadatak koristi izmišljene podatke dok se ne podesi politika. Počinje runtime/dependency bootstrap-om i praznim migracijama/setup-om, zatim koracima iz ugovora modula. Nijedna funkcija faze 1 nije implementirana pri zatvaranju ove etape.

## Uslovi pri implementaciji

| Kada | Potreban dokaz |
| --- | --- |
| Bootstrap faze 1 | Stvarne runtime/database/extension verzije, održavane kompatibilne zavisnosti, lockfile/licence i bounded-parser spike; neuspeh ako parser ne odbija duple ključeve ili nema sve granice |
| Autorizovane provere faze 1 | Meta-validacija šeme i format assertions; izvršenje prihvaćenih/odbijenih primera kroz parser/šemu/semantiku; dozvole, objava/povlačenje, paralelne izmene, atomski uvoz, mediji/migracije |
| Pre stvarnih podataka/produkcije | Politika operatora/čuvanja/maloletnika, prava baze/slika, protected delivery, provereni redovi matrice, stvarna podrška i release/security tok |
| Pre faze 2 | Desktop mapiranje/lokalne izmene i očuvanje istorijskih snimaka u njegovom repozitorijumu |
| Pre faze 3 | Posebna normativna signed šema, održavan JCS provider, referentni crypto primeri, chain/conflict/feed provere i nezavisan bezbednosni pregled |
| Pre faze 5 | Offline signing/recovery UX, vežba privatnog restore-a, rotacija root-a, paralelan prenos i usklađivanje zastarelih podataka |

Install/build/check komande ne tvrde se bez konfiguracije. Korisnikov zahtev odobrava dokumentacioni rad, ne instalaciju zavisnosti, implementacione testove, objavu, commit ili push. Slučajevi ispod su zahtevi za posebno autorizovan implementacioni zadatak.

## Neuspeh i migracije za dalji rad

Granice bajtova/dubine/broja/tokena; nevalidan UTF-8/dupli ključevi; preciznost/prekoračenje brojača; nepoznata/privatna/null polja; dupli ID/slug/članstvo; reference klubova/medija; live/tombstone sukob; budući checkpoint; promenjena politika; lažan nepotpisan autoritet; isto ime/različite osobe; zastareo pregled; ponovljena potvrda/drugi payload; prekid uvoza pre/posle commit-a; capabilities/nonces; javno curenje nacrta; deljeni mediji; neuspešan finalize/unlink; preflight; paralelan setup/checkpoint; restart/checksum migracije; delimičan DDL; nedovoljan backup/disk; vraćanje starog checkpoint-a; izričit uninstall retention. Slučajevi poverenja su u autentifikovanom dizajnu.

## Stvaran pregled i ograničenja

Pregledana usklađenost dokumenata, izbori sa upstream izvorima, svojstva/lokalne reference šeme i sadržaj izmišljenih primera. Dokumentacione provere nisu našle nedostajuće lokalne Markdown linkove; svih 31 referenci šeme razrešeno je lokalno, 12 JSON artefakata bilo je čitljivo (uključujući nameran primer duplog ključa), a skupovi naziva engleskih/srpskih dokumenata su usklađeni. Ovo su samo dokumentacione provere. JSON Schema validator nije instaliran u workspace-u; meta-validacija i izvršenje primera nisu označeni kao prošli. Nema WordPress/PHP/database ili crypto testa. Ne tvrde se runtime minimum, signing interoperabilnost, izmeren kapacitet ili pravna usklađenost.
