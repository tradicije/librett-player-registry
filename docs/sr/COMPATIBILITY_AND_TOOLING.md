# Matrica kompatibilnosti i izbor alata

[English](../en/COMPATIBILITY_AND_TOOLING.md)

Ažuriranje faze 1: [implementirana osnova](IMPLEMENTATION_STATUS.md) beleži instalirane verzije i stvarne testove. Ostali ugovori ispod su planirani; tvrdnje pregleda faze 0 odnose se na istorijski dokumentacioni zadatak.

Pregled: 2026-10-07. Status: izabrani inženjerski ciljevi za pregled/implementaciju, ne proverena podrška plugina ili oglašen minimum. Bootstrap zavisnosti su instalirane/zaključane; stvarni dokazi su u statusu implementacije.

## Dokazi upstream-a

[PHP podrška](https://www.php.net/supported-versions.php) navodi aktivno podržan 8.5. [WordPress download](https://wordpress.org/download/) nudi 7.1.3; [PHP matrica](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) navodi 7.1 uz PHP 8.5. To opisuje upstream, ne naš plugin. Ponovo proveriti patch izdanja pri implementaciji i zabeležiti tačne verzije.

[WordPress zahtevi hostinga](https://wordpress.org/about/requirements/) preporučuju MariaDB 10.11+ ili MySQL 8.0+. Biramo uži transakcioni cilj ispod, bez tvrdnje da stariji engine-i ili sve novije verzije rade. [MariaDB održavanje](https://mariadb.org/about/#maintenance-policy) i [MySQL 8.4 DDL](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html) proveravaju se za konkretan build. Migracije ne pretpostavljaju DDL rollback.

## Kandidat matrice deployment provera

| Cilj | WordPress | PHP | Baza | Uloga / dokaz |
| --- | --- | --- | --- | --- |
| A | 7.1.3 single-site | Poslednji stabilan 8.5 patch, 64-bit | Poslednji održavan MariaDB 10.11 patch, InnoDB | Glavni razvoj i prvi deployment kandidat; upstream kompatibilan, plugin neproveren |
| B | Isti | Isti | Poslednji održavan MySQL 8.4 patch, InnoDB | Obavezna alternativna provera pre tvrdnje o MySQL podršci; plugin neproveren |

Bez podrške PHP <8.5, prerelease PHP-a, SQLite WordPress adaptera, multisite/network aktivacije i replika u fazi 1. HTTPS i Linux deployment referenca; razvoj može koristiti macOS/Windows kontejnere, ali native hosting traži zaseban dokaz. Pre odobrenja deployment-a navesti build servera, SQL režime, kolaciju, dozvole filesystem-a/medija, web server, memory/upload/body granice i sve druge plugine/temu.

Kandidati PHP ekstenzija: `mysqli`, `mbstring`, `intl`, `fileinfo` i jedan proveren GD/Imagick backend; JSON/hash podrška mora postojati. Granice slika i zaštićen storage su obavezni. `sodium` je uslov tek kasnije funkcije potpisa/oporavka. Bez prećutnog prelaska na nepotpisanu proveru kada nema kriptografije. WordPress mreža/mediji traže adapter provere. Početni ulaz/izlaz slika je samo JPEG/PNG. Lokalni uvoz ne traži mrežu.

## Izabrani alati

| Svrha | Izbor / pregledan upstream | Implementaciona odluka |
| --- | --- | --- |
| Autoload/zavisnosti | Composer 2, trenutni download 2.10.3 ([izvor](https://getcomposer.org/download/)) | PSR-4, lockfile posle instalacije; bez nepotrebnih Composer plugins/scripts |
| Statička analiza | PHPStan 2, pregledan 2.3.0 ([izdanje](https://github.com/phpstan/phpstan/releases/tag/2.3.0)) | Najstroži praktičan nivo jezgra, tipizirani adapter stubs; bez opšteg ignorisanja grešaka |
| Formatiranje | PHP CS Fixer 3, pregledan 3.95.27 ([izdanje](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/tag/v3.95.27)) | PER Coding Style 3.1 ([standard](https://www.php-fig.org/per/coding-style/)); zaključati podržana izričita pravila i pregledati nepokrivena |
| Core/use-case testovi uz autorizaciju | PHPUnit 13 ([podrška](https://phpunit.de/supported-versions.html)) | Izolovan core suite na PHP 8.5; tačan patch pri instalaciji |
| WordPress provere uz autorizaciju | PHPUnit 13 sa izolovanim bootstrapped WordPress integration harness-om | Ne učitavati WordPress `WP_UnitTestCase` u PHPUnit 13: [WordPress matrica](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/) navodi PHPUnit 9. Ne dodavati zastareo runner; koristiti javni WordPress bootstrap/API i privremenu bazu |
| Audit zavisnosti | Composer audit ([CLI](https://getcomposer.org/doc/03-cli.md#audit)) | Audit zaključanih runtime/dev paketa i notices |
| UUID adapter | Održavana stabilna linija ramsey/uuid ([uputstvo](https://uuid.ramsey.dev/en/stable/)) | v4 preko porta; resolver bira/zaključava kompatibilno izdanje pre upotrebe |
| Adapter šeme | opis/json-schema 2 ([uputstvo](https://opis.io/json-schema/2.x/)) | Draft 2020-12, lokalne šeme, izričiti formats; bez Opis proširenja |
| Ograničen JSON ulaz | Održavan streaming/token parser kroz `BoundedJsonReader` | Izbor je implementacioni spike: dupli ključevi, byte/token/depth/count granice i PHP 8.5 su kriterijumi. Bez neograničenog `json_decode` ili ručno pisanog parsera |
| Kasnije potpisivanje | PHP sodium Ed25519 ([uputstvo](https://www.php.net/manual/en/book.sodium.php)) | Bez sopstvenih algoritama potpisa; JCS provider traži poseban interoperabilni pregled |

Parser/JCS paket ne može odgovorno biti označen proverenim pre stvarne provere potrebnih sposobnosti. Spike je izričito raspoređen u odgovarajuću implementacionu etapu; ne dodaje se neproveren paket radi zatvaranja liste. To je granica dizajna/izbora alata, ne izostavljena runtime provera. Bez DI framework-a, ORM-a, SPA ili dodatnog coding-standard stack-a u prvoj etapi.

Pri implementacionom bootstrap-u rešiti održavana stabilna izdanja za PHP 8.5, pregledati licence/bezbednost/održavanje, zaključati verzije, navesti stvarne komande/verzije i pokrenuti samo autorizovane provere. Faza 0 nema izmišljene build/test komande. Nijedan red matrice još nije označen kao testiran ili odobren od operatora.
