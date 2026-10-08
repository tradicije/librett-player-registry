# Implementirani razvojni obim

[English](../en/IMPLEMENTATION_STATUS.md)

Ažurirano 2026-10-08. Neobjavljen razvojni kod; nema produkcionog ZIP-a ni tvrdnje o podršci. Ovaj zapis zamenjuje status koji je pokrivao samo bootstrap.

## Šest dogovorenih koraka

1. Privatni Players/Clubs unos, pretraga, izmena, arhiviranje/vraćanje, revizije, validacija i testovi transakcija/audita.
2. Članstva po UUID-u, alias-i klubova i pregled duplikata; imena nikada ne spajaju identitete. Arhiviranje uklanja pogođena članstva i povlači odobrene profile.
3. Izbor licence baze u wp-admin: ODbL 1.0, CC0 1.0, CC BY 4.0, CC BY-SA 4.0, All rights reserved ili Custom. Custom prihvata HTTPS URL ili zaštićen PDF/UTF-8 tekst (1 MiB). Nema podrazumevane licence. Standardni URL uslova može se dopuniti posle izričitog izbora. Svrha, verzija politike, uslovi medija i neobavezna politika maloletnika zasebni su unosi.
4. Zaštićena obrada JPEG/PNG fotografija i izričito odobravanje izabranih javnih polja uz privatni osnov i pregled uzrasta. Privatne izmene ne menjaju odobrene kopije. Promena politike povlači prethodna odobrenja. Povlačenje zatvara javnu isporuku medija; već preuzete kopije ne mogu se opozvati.
5. Javni profili i pretraga kroz `[librett_registry]`, GET REST rute i ograničen nepotpisan JSON uvoz/izvoz. Uvoz traži privatni pregled, izričita mapiranja i potvrdu; novi identiteti nisu objavljeni. Potvrde i očekivane revizije sprečavaju duplu/zastarelu primenu. WordPress ne preuzima automatski fotografije izvora.
6. LibreTT Desktop uvoz izvora/fajla, pregled/dopuna/mapiranje, zadržavanje lokalnih izmena, osvežavanje i evidencija povlačenja. Podaci idu samo registar → Desktop. Istorijski snimci prijava ostaju isti. Desktop šema 22 odvaja mapiranja od lokalnih ID-eva i pamti odvojene veze kada korisnik obriše lokalni profil.

## Arhitektura i migracije

Nezavisni application/domain portovi; WordPress adapteri poseduju custom tabele. Composition koordinira Players, Clubs, Publication i Media u transakcijama. Replication, Proposals i Recovery ostaju projektovani moduli. Migracije 001–006 stvaraju 22 prazne tabele; definicije 001/002 su sačuvane. Dodatne migracije koriste advisory lock, checksum, nastavak DDL-a i strukturnu proveru. Podešeni sajtovi traže izjavu operatora o backup-u. Deaktivacija/deinstalacija čuvaju podatke; destruktivni purge i privatni restore ne postoje.

Objava koristi trajnu evidenciju revizija/povlačenja i odobrene kopije, a ne izvoz živih privatnih nacrta. Naziv i UUID su obavezni; ostala polja se izričito odobravaju. Nepoznat uzrast blokira objavu; maloletnici traže dokumentovanu politiku. Kredencijali, privatni osnov, audit i putanje skladišta ne ulaze u javni izvoz.

## Stvarne provere

Razvojno okruženje: Fedora 44, rootless Podman; PHP 8.5.11, WordPress 7.1.3 single-site, MariaDB 10.11.19/InnoDB/GD. Composer zavisnosti su zaključane. Komande i završni rezultati su u [evidenciji provere](VERIFICATION_2026_10_08.md).

Testovi pokrivaju nacrte, identitet, dodatne šeme, članstva/alias-e, odobrenje/privatnost, povlačenje, fotografije, Custom licence, JSON primere/ograničenja i transakcioni uvoz. Desktop testovi pokrivaju postojeće turnire, migracije/backup i uvoz, lokalne izmene, ponavljanje, zastareli pregled, rollback, povlačenje i istorijske snimke. Sintetički snimak izvezen iz WordPress-a uvozi se u SQLite Desktop i opstaje posle restarta/backup restore-a.

## Ograničenja

Nema potpisa, autentifikovane replikacije, predloga, oporavka autoriteta, automatskog otkrivanja izvora ni garancije svežine pri prvom kontaktu. Nepotpisani UUID/revizije ne uspostavljaju poverenje. MySQL 8.4, multisite, produkcioni kapacitet i macOS/Windows integracija nisu provereni ovde. Interaktivni GUI/browser i javni HTTPS deployment traže operatorov pregled. Prava i objava ostaju odgovornost operatora.

Zaštićeni fajlovi traže privatno skladište van svih web-served direktorijuma. Baza i privatni fajlovi traže usklađen backup. Neuspeli upis može ostaviti privatni orphan fajl; garbage collection nije implementiran. PHP/server upload i database packet limiti mogu biti niži od protokolskih 32 MiB. Baza saveza nije uključena.

## Dopuna za PHP 8.3 hosting

Korisnik je odobrio PHP 8.3.3 kompatibilnost i kompletan razvojni instalacioni ZIP posle pokušaja GitHub source-only instalacije. Runtime prihvata 64-bit PHP 8.3.3–8.5.x; WordPress ostaje 7.1.3–7.1.x single-site. Zavisnosti se biraju prema 8.3.3, uz razvojni PHPUnit 12. Lokalni ZIP builder uključuje runtime vendor bez produkcionog release-a. [Stvarne PHP 8.3/paket provere](PHP83_COMPATIBILITY.md) dopunjuju raniju PHP 8.5 evidenciju.
