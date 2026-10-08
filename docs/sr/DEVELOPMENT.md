# Razvojni standardi

[English](../en/DEVELOPMENT.md)

Početni bootstrap faze 1 je implementiran. Pre proširenja pročitaj [stanje implementacije](IMPLEMENTATION_STATUS.md), [arhitekturu](ARCHITECTURE.md) i [matricu kompatibilnosti](COMPATIBILITY_AND_TOOLING.md). Tačne zavisnosti su u composer.lock; instaliraj iz lockfile-a bez implicitnog ažuriranja verzija.

Koristi PHP 8.3.3–8.5.x na 64-bitnom runtime-u sa ekstenzijama navedenim u composer.json. WordPress adapter zahteva i GD. Iz korena repozitorijuma pokreni:

```sh
composer install
php vendor/bin/phpunit
php vendor/bin/phpstan analyse --debug --memory-limit=512M
php vendor/bin/php-cs-fixer check --sequential
composer validate --strict --no-check-publish
```

PHPStan koristi najstroži nivo. `--debug` radi sekvencijalno i izbegava ograničenja worker socket-a u sandbox okruženjima. Formatter trenutno podržava PER-CS 3.0; dokumentovani cilj ostaje PER 3.1, uz ručni pregled dopuna izvan tog ruleset-a. Domain/application klase moraju da se učitavaju bez WordPress-a.

## Privremeno WordPress integraciono okruženje

Koristi zaseban WordPress 7.1.3 direktorijum i privatnu MariaDB 10.11 instancu sa UNIX socket-om, isključenom mrežom i privremenim root nalogom bez lozinke. Helper je samo za to lokalno test okruženje. Ne usmeravaj ga na postojeći korisnički sajt ili produkcionu bazu. MySQL 8.4 je izabran cilj, ali nije proveren u ovoj izmeni.

Postavi putanje svog privremenog okruženja:

```sh
export LIBRETT_WP_ROOT=/putanja/do/privremenog/wordpress
export LIBRETT_DB_SOCKET=/putanja/do/privatnog/mysql.sock
export LIBRETT_TEST_DB=librett_registry_test_bootstrap
ln -s "$PWD" "$LIBRETT_WP_ROOT/wp-content/plugins/librett-player-registry"
php tools/install-integration-site.php
php vendor/bin/phpunit -c phpunit.integration.xml
```

Installer zahteva prefiks baze `librett_registry_test_` i odbija prepisivanje drugačijeg wp-config.php. Pravi sintetički administratorski nalog bez ispisa lozinke; mail, spoljni WordPress HTTP, cron i automatska ažuriranja su isključeni. Integracioni testovi zahtevaju tu zasebnu bazu, koriste izolovane prefikse tabela i uklanjaju svoje test tabele. Lifecycle test koristi i plugin tabele zasebnog sajta. Ovo okruženje nije namenjeno deployment-u.

Unit/integracioni testovi pokrivaju identitet, privatni CRUD, dodatne migracije, članstva, zaštićene medije, objavu, JSON validaciju/uvoz i privatnost. Replikacija/oporavak ne postoje. CI workflow i produkcioni release nisu implementirani; razvojni ZIP builder je dostupan. [Stanje implementacije](IMPLEMENTATION_STATUS.md) beleži stvarne verzije, rezultate i ograničenja.

Čuvaj granice modula, strict types, izričite portove i Composer PSR-4. Budući scripted frontend koristi strict TypeScript; framework nije izabran. Budući release ZIP mora da sadrži runtime zavisnosti i [third-party notices](../../THIRD_PARTY_NOTICES.md), bez razvojnih zavisnosti, testova, baza igrača ili tajni za oporavak. Release verzija nije dodeljena.

## Razvojni alati kroz rootless Podman

Na ovom Fedora računaru PHP/Composer/gettext instaliraju se u rootless Podman razvojnu sliku. Iz repozitorijuma koristite `tools/dev/run`; PHP nije instaliran kao sistemska izvršna datoteka. Slika dodaje mysqli, intl, GD i ZIP zvaničnoj PHP 8.5 CLI slici i kopira Composer 2. Preduslovi su rootless Podman i sistemski curl/tar.

```sh
tools/dev/run build
tools/dev/run composer install --no-interaction
tools/dev/run db-start
tools/dev/run wp-download
tools/dev/run wp-install
```

Wrapper povezuje repozitorijum kao `/workspace`, privremeni WordPress kao `/wordpress` i zajednički MariaDB UNIX socket. MariaDB 10.11 radi u `librett-registry-dev-db` bez mreže; podaci, preuzimanja, keš i WordPress konfiguracija ostaju u ignorisanom `local/dev/`. PHP komande nemaju mrežu. Composer komande uključuju mrežu za preuzimanje zavisnosti; WordPress blokira spoljni HTTP i email. Sistemska baza/servis se ne koriste.

Kada je provera implementacije odobrena, instalirani alati pozivaju se ovako:

```sh
tools/dev/run php vendor/bin/phpunit
tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
tools/dev/run php vendor/bin/phpstan analyse --debug --memory-limit=512M
tools/dev/run php vendor/bin/php-cs-fixer check --sequential
tools/dev/run msgfmt --check --output-file=local/dev/serbian.mo languages/librett-player-registry-sr_RS.po
```

Zaustavite privremenu bazu kroz `tools/dev/run db-stop`; ponovo je pokrenite kroz `db-start`. Zaustavljanje čuva podatke. Nisu podešeni automatsko pokretanje, purge ili produkcioni deployment. Tagovi slika biraju dokumentovane grane; stvarne verzije zabeležiti pri instalaciji, jer tagovi nisu nepromenljivi patch pin-ovi.

## Provera proširenja privatnim nacrtima ostaje za izvršavanje

[Aktuelne provere i ograničenja](VERIFICATION_2026_10_08.md) zamenjuju raniji bootstrap obim. Zaštićeno skladište podesi prema [administratorskom uputstvu](ADMIN_GUIDE.md).
## PHP 8.3 i razvojni instalacioni paket

Korisnikov PHP 8.3.3 hosting je izričito prihvaćen compatibility cilj (ADR 0008). `config.platform.php=8.3.3` čuva kompatibilan izbor zavisnosti i kada Composer radi na PHP 8.5; stvarna platform provera ostaje potrebna. PHPUnit 12 zamenjuje 13 da testovi rade na obe grane. Runtime paketi nisu promenjeni. Raniji PHPUnit 13/PHP 8.5 rezultati ostaju istorijska evidencija.

```sh
LIBRETT_DEV_PHP=8.3.3 tools/dev/run build
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
LIBRETT_DEV_PHP=8.3.3 tools/package-plugin
```

Builder kopira izabrani source/docs/languages, instalira samo runtime zavisnosti iz lockfile-a u novo ignorisano staging skladište, proverava stvarnu platformu/autoload i pravi ZIP sa jednim root-om i SHA-256 u `build/`. Razvojne zavisnosti, testovi, alati, Git metadata, lokalne baze/mediji/konfiguracija i recovery tajne nisu uključeni. Radni razvojni vendor se ne menja i ništa se ne objavljuje. Pogledaj [PHP 8.3 provere](PHP83_COMPATIBILITY.md).

`LIBRETT_DEV_DB=11.8.8` bira hosting bazu sa zasebnim kontejnerom, data direktorijumom i UNIX socket-om. Pokreni db-start i wp-install sa tom promenljivom pre integracionih/paket provera. Podrazumevano 10.11 okruženje i podaci ostaju sačuvani.
