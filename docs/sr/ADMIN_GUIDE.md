# Administratorsko uputstvo — razvojni katalog

[English](../en/ADMIN_GUIDE.md)

Neobjavljen izvorni kod za izdvojeni razvojni sajt. Pročitaj [obim/ograničenja](IMPLEMENTATION_STATUS.md) pre stvarnih podataka. Igrači i klubovi nisu uključeni.

## Instalacija i podešavanje

1. Izabrani razvojni cilj: 64-bit PHP 8.3.3–8.5.x, WordPress 7.1.3 single-site, MariaDB 10.11 ili 11.8/InnoDB; instaliraj Composer runtime zavisnosti. MySQL 8.4 nije proveren.
2. Aktiviraj dodatak; nova aktivacija stvara praznu šemu i administratorske dozvole. Otvori **LibreTT Registry** i napravi svoj imenovani glavni registar.
3. Postojeća podešena instalacija: napravi backup baze i privatnih fajlova, pa kroz **Šema registra** potvrdi backup i instaliraj/nastavi dodatne migracije 001–006. Izjava ne pravi backup. Ponovni pokušaj nastavlja provereni DDL; ne menjaj evidentirane checksum vrednosti da zaobiđeš grešku.
4. Za fotografije/Custom dokumente obezbedi upisiv direktorijum PHP korisnika, mode 0700, van document root-a, WordPress direktorijuma i svih web-server alias-a. Podesi `LIBRETT_PRIVATE_STORAGE` u wp-config.php na apsolutnu putanju (ili environment promenljivu). Fajlovi imaju 0600. Nema fallback-a na javni uploads; unos bez upload-a ne traži privatni direktorijum.

### Instalacija pripremljenog razvojnog ZIP-a

GitHub **Code → Download ZIP** daje samo izvorni kod, bez `vendor` direktorijuma; ne može direktno da se aktivira. Koristi `build/librett-player-registry-development.zip`, napravljen komandom `tools/package-plugin`, koji uključuje zaključane runtime zavisnosti. U Dodaci → Dodaj novi → Otpremi dodatak izaberi taj ZIP. Ako je već instaliran ZIP izvornog koda, potvrdi WordPress zamenu, pa aktiviraj. Postojeći podaci registra ostaju. Produkciona release verzija nije uvedena.

Paket se priprema za korisnikov PHP 8.3.3 / WordPress 7.1.3 single-site. Stvarni testovi su u [PHP 8.3 kompatibilnosti](PHP83_COMPATIBILITY.md). Baza/ekstenzije/privatno skladište i dalje su potrebni; hosting podešavanja proveravaju se pri aktivaciji.

## Privatni katalog

Unesi klubove i igrače kroz **Klubovi**/**Igrači**. UUID identifikuje zapis; ista imena su dozvoljena. Aktivno/Arhivirano vraća/arhivira uz prikazanu reviziju. Sukob traži ponovno učitavanje i pregled. Nema automatskog spajanja po imenu.

Kroz **Članstva, alias-i i pregled duplikata** proveri UUID i detalje kandidata. Unesi UUID-eve aktivnih klubova igrača (najviše 100) ili alias-e kluba (najviše 20). Izmena članstva podiže reviziju igrača. Arhiviranje kluba uklanja pogođena članstva; arhiviranje povlači njegovu javnu kopiju. Vraćanje nacrta ga ne objavljuje automatski.

## Licence i podešavanja objave

Izričito izaberi ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved ili Custom. Standardni URL može se dopuniti posle izbora; zadržana prava traže tvoj URL uslova. Custom koristi HTTPS URL ili UTF-8 tekst/PDF do 1 MiB. Prazni Custom unosi zadržavaju ranije sačuvan dokument. Dokument se isporučuje kao preuzimanje; ne stavljaj recovery tajne ili privatni osnov objave u njega.

Unesi verziju politike, svrhu i HTTPS uslove medija; politika maloletnika obavezna je pre njihove objave. Licenca ne uspostavlja dozvolu objave. Svaka izmena politike traži novu verziju: prethodne objave povlače se atomarno i traže novi pregled.

## Fotografije i javno odobrenje

**Fotografije igrača** prihvata JPEG/PNG do 5 MiB i 4096 piksela po strani. Zabeleži javnu atribuciju i privatni osnov prava. GD dekodira/ponovo kodira sliku i uklanja originalne metapodatke/dodatne bajtove. Privatni pregled traži dozvolu uređivanja. Čuvanje/uklanjanje fotografije samo po sebi ne objavljuje nacrt.

U **Pregled objave** proveri podatke, izaberi dodatna javna polja, uzrast i privatni osnov odobrenja. Objavi klubove pre članstava igrača. Naziv/UUID su obavezni javni podaci. Nepoznat uzrast blokira; maloletnici traže podešenu politiku. Kasnije privatne izmene ne menjaju odobrenu kopiju. Povlačenje/arhiviranje zatvara javni pristup i fotografije; preuzete kopije ostaju van kontrole servera.

## Javni sajt i JSON

Dodaj `[librett_registry]` na WordPress stranu. GET rute pod `/wp-json/librett-registry/v1/`: `manifest`, `snapshot`, `players`, `clubs`, `players/{uuid}`, `clubs/{uuid}`, `media/{uuid}`, `license`. Pretraga koristi `q`, `offset`, `limit` (najviše 50). Snapshot je cela odobrena javna projekcija, nepotpisan format v1; sačuvaj odgovor `snapshot` za prenos fajlom. Privatna polja i dokazi nisu uključeni.

**JSON uvoz** proverava fajl do 32 MiB, priprema privatni pregled i traži potvrdu. Postojeće zapise poveži izričitim redovima `player|club source-UUID local-UUID`; inače koristi nove nezavisne UUID-eve ili sačuvano poreklo. Proveri polja izvora i revizije cilja. Dostavljena polja i članstva menjaju nacrte; izostavljena opciona polja ostaju lokalna. Uvoz nikada ne odobrava objavu niti briše zapis zbog povlačenja izvora. Fotografije ostaju opis izvora bez preuzimanja. Pregled traje sat vremena; otkaži nepotrebne (najviše pet po operatoru).

PHP/proxy upload i database `max_allowed_packet` moraju podržati stvarni JSON i mapiranja; protokolski limit nije garancija kapaciteta servera. Backup uključuje privatne tabele i zasebno skladište. Sam backup baze ne vraća privatne fotografije/licence. Automatski privatni restore, purge i čišćenje orphan fajlova nisu implementirani.

## Desktop

Otvori Desktop **Igrači** i panel uvoza registra. Koristi HTTPS `snapshot` URL ili preuzet JSON. Pregledaj mapiranja, dopuni godište/lokalna ograničenja, po želji izričito preuzmi/iseci fotografiju, pa potvrdi. Lokalne izmene i istorijske prijave ostaju lokalne. Osvežavanje koristi identitet/checkpoint i sačuvane lokalne izbore. Beleške, kontakti, uplate i turniri ne šalju se nazad.

## Dozvole

`librett_registry_manage_settings`: setup/šema/politika. `librett_registry_edit_profiles`: privatni katalog/mediji. `librett_registry_publish_profiles`: objava, uz edit dozvolu za pregled. `librett_registry_import` uz edit: JSON uvoz. Mutacije traže POST, capabilities, nonce i application autorizaciju. Javni API daje samo odobrenu projekciju. Deaktivacija/deinstalacija čuvaju podatke i dozvole.
