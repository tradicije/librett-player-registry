# PHP 8.3 hosting i razvojni ZIP

[English](../en/PHP83_COMPATIBILITY.md)

Korisnik je odobrio prilagođavanje PHP zahteva posle GitHub source ZIP instalacije na PHP 8.3.3 / WordPress 7.1.3. Zapis dopunjuje ranije PHP 8.5/PHPUnit 13 rezultate i ne predstavlja produkcioni release.

## Stvarne provere

- PHP 8.3.3 (64-bit), WordPress 7.1.3 single-site, MariaDB 10.11.19/InnoDB, GD, izdvojeno rootless razvojno okruženje.
- PHPUnit 12.5.38: 47 unit testova / 80 assertions i 42 integraciona testa / 146 assertions prošlo na tačno PHP 8.3.3.
- PHPStan maksimalni nivo na PHP 8.3.3: nema grešaka u source-u.
- PHP 8.5.11 regresija: isti skup 47 unit / 42 integraciona testa prošao je uz PHPUnit 12.5.38.
- Composer platform izbor je 8.3.3. Runtime verzije paketa nisu promenjene; razvojni PHPUnit/Symfony koriste održavane verzije kompatibilne sa PHP 8.3. Audit pri update-u nije prijavio poznate advisories.

Runtime ZIP je stvarno raspakovan i aktiviran na novom izdvojenom WordPress 7.1.3 / PHP 8.3.3 sajtu: 22 prazne custom tabele, administratorske dozvole, sledeće učitavanje, admin hook-ovi, shortcode/API i izričito pravljenje primary registra. Composer stvarna platform provera i paket autoload prošli su. Runtime vendor ima sedam paketa i nema razvojnih zavisnosti. Prvi pokušaj tražio je ispravku testnog alata da ponovo učita korisničke dozvole posle aktivacije; plugin nije tražio zaobilaženje dozvola.

PHP CS Fixer radi na PHP 8.3.3 i proverava 122 source/test/tool fajla. Srpske aktivacione poruke kompajlirane su uz msgfmt --check. PHP source i oba shell helpera prolaze sintaksnu proveru. Lokalni ZIP i SHA-256 su u ignorisanom build/; release i spoljašnje otpremanje nisu izvršeni.

## Instalacija

Instaliraj `build/librett-player-registry-development.zip` kroz WordPress Dodaci → Dodaj novi → Otpremi dodatak. Potvrdi zamenu ranije source-only kopije kada WordPress zatraži, pa aktiviraj. ZIP sadrži runtime Composer vendor, source, prevode, šemu i dokumentaciju. Composer na hostingu nije potreban. GitHub Code → Download ZIP i dalje daje samo izvorni kod.

Identitet/podaci registra ne nastaju automatski. Napravi svoj primary kroz LibreTT Registry, zatim koristi Igrači/Klubovi. Fotografije/Custom dokument traže privatni direktorijum prema [administratorskom uputstvu](ADMIN_GUIDE.md). Baza/ekstenzije/filesystem dozvole ostaju zahtevi konkretnog hostinga.

## Dopuna za MariaDB 11.8 hosting

Korisnik je naveo `11.8.8-MariaDB-ubu2404`. Uslov sada prihvata MariaDB 10.11 ili 11.8; MySQL 8.4 i definicije/checksum šeme ostaju isti. Zaseban MariaDB 11.8.8 razvojni kontejner koristi nezavisne data/socket direktorijume; postojeća 10.11 baza se ne nadograđuje. Na PHP 8.3.3 / WordPress 7.1.3 / tačnoj verziji `11.8.8-MariaDB-ubu2404` prošla su sva 42 integraciona testa / 146 provera. Raspakovani ZIP je prošao i novu aktivaciju, svih 22 custom tabela, administratorska ovlašćenja, pokretanje u sledećem zahtevu, admin hook-ove, registraciju shortcode/API-ja i eksplicitno kreiranje primarnog registra.

## Ponavljanje provere

```sh
LIBRETT_DEV_PHP=8.3.3 tools/dev/run build
LIBRETT_DEV_PHP=8.3.3 tools/dev/run php vendor/bin/phpunit
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run db-start
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run wp-install
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run php vendor/bin/phpunit -c phpunit.integration.xml
LIBRETT_DEV_PHP=8.3.3 tools/package-plugin
LIBRETT_DEV_PHP=8.3.3 LIBRETT_DEV_DB=11.8.8 tools/dev/run php tools/verify-plugin-package.php
```

Paket provera stvara nov sintetički sajt/bazu u ignorisanom local/dev i ne kontaktira hosting korisnika. PHP 8.4 se prihvata u uslovu, ali nije izvršen ovde. Podrazumevano PHP 8.5 razvojno okruženje ostaje. Ne uvodi se podrška za WordPress <7.1.3, multisite, MySQL deployment, živi browser pregled ni produkciona garancija kapaciteta.

Ponovo su provereni [PHPUnit PHP kompatibilnost](https://phpunit.de/supported-versions.html) i [PHP podrška grana](https://www.php.net/supported-versions.php). Predlog commita: `fix: support PHP 8.3 hosting and package runtime dependencies`.
