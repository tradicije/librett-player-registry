# Razvojni standardi

[English](../en/DEVELOPMENT.md)

Početni bootstrap faze 1 je implementiran. Pre proširenja pročitaj [stanje implementacije](IMPLEMENTATION_STATUS.md), [arhitekturu](ARCHITECTURE.md) i [matricu kompatibilnosti](COMPATIBILITY_AND_TOOLING.md). Tačne zavisnosti su u composer.lock; instaliraj iz lockfile-a bez implicitnog ažuriranja verzija.

Koristi PHP 8.5 na 64-bitnom runtime-u sa ekstenzijama navedenim u composer.json. WordPress adapter zahteva i GD ili Imagick. Iz korena repozitorijuma pokreni:

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

Unit i integracioni testovi pokrivaju implementirani identitet/bootstrap; ne proveravaju planirani CRUD igrača, publikovanje, replikaciju ili oporavak. CI workflow i release pakovanje nisu implementirani. [Stanje implementacije](IMPLEMENTATION_STATUS.md) beleži stvarne verzije, rezultate i ograničenja.

Čuvaj granice modula, strict types, izričite portove i Composer PSR-4. Budući scripted frontend koristi strict TypeScript; framework nije izabran. Budući release ZIP mora da sadrži runtime zavisnosti i [third-party notices](../../THIRD_PARTY_NOTICES.md), bez razvojnih zavisnosti, testova, baza igrača ili tajni za oporavak. Release verzija nije dodeljena.

## Provera proširenja privatnim nacrtima ostaje za izvršavanje

Players/Clubs proširenje od 2026-10-08 nije izvršeno u ovom okruženju, gde PHP/Composer/msgfmt nisu dostupni. Raniji bootstrap rezultati ne pokrivaju migraciju 002 i forme nacrta. Pre deployment-a odobrena provera mora pokriti odbijene dozvole/nonce, nepodešen/replica kontekst, Unicode/kontrole/ograničenja polja, opciono godište, ista imena, zastarele izmene, iscrpljenu reviziju, audit rollback, arhiviranje/vraćanje, ograničenu pretragu, svežu/postojeću aktivaciju, prekid/checksum/buduću šemu/lock migracije i čuvanje svih sedam tabela. Postojeći bootstrap testovi nisu menjani niti ponovo izvršeni.
