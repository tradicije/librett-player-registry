# Poverenje, replike, predlozi i oporavak

[English](../en/TRUST_AND_RECOVERY.md)

Status: model pretnji i zahtevi protokola; nije implementirano niti auditovano.

## Identitet i ključevi

UUID registra označava bazu, ne vlasnika. Klijenti pamte recovery/javni identitet poverenja nakon proverenog povezivanja. Razdvojiti:

- Offline autoritet oporavka: odobrava novi operativni ključ/adresu; tajna je kod maintainer-a van običnih servera i replika.
- Operativni publication ključ: glavni sajt potpisuje potvrđene snimke/promene; ne može sam sebi dati novi recovery root.

Autentifikovana tvrdnja autoriteta vezuje ID registra, prethodnu tvrdnju, generaciju, glavnu adresu, novi ključ i data checkpoint. Potrebna je precizna kanonska/signature specifikacija. „Recovery kod” u UI-ju nije obična lozinka koju prihvata svaki server sa njenim hash-om.

## Glavni sajt/replika

Samo ovlašćene glavne izmene prave prihvaćene zajedničke revizije. Admin replike menja lokalni prikaz, pristup i šalje predloge. Zajednički CRUD odbija upise u replica režimu na use-case/API sloju.

Vlasnik hostinga može izmeniti open-source plugin ili svoju bazu. Klijenti koji proveravaju odbijaju neodobrene zajedničke objave pomoću potpisa i zapamćenog autoriteta. Sistem ne sprečava proizvoljan lokalni fork i ne tvrdi da vlasnik fizički ne može promeniti fajlove.

Prvo poverenje proizvoljnom manifestu nije nezavisno autentifikovano. Prikazati naziv, host i otisak; podržati pouzdan connection fajl/proveru van izvora. TOFU, ako bude dopušten, je izričit. Novi ključ zahteva potvrđen prenos, ne samo nov manifest.

## Predlozi

Upariti replica aktera odvojeno od javnog čitanja. Kredencijali su opozivi i ograničeni na slanje/pregled sopstvenih zahteva, ne objavu/admin registra. Limits i server dozvole su obavezni. Pending predlog i privatni dokazi nisu javni profil. Glavno prihvatanje proverava početnu reviziju i beleži aktera/ishod; zastareo zahtev ne prepisuje novi profil.

## Oporavak

1. Nabaviti najnoviji backup ili proveren checkpoint replike; ključ ne vraća izgubljene zapise.
2. Na novom hostu vratiti/pripremiti podatke i napraviti svež operativni ključ.
3. Van novog servera maintainer potpisuje odobrenje ključa/adrese, sa vraćenim checkpoint-om i novom generacijom. Offline signing UX još nije dogovoren; tajni root ključ se ne šalje proizvoljnom novom hostu.
4. Novi sajt proverava odobrenje prema zapamćenom recovery autoritetu, primenjuje transakciju i omogućava glavne operacije autentifikovanom administratoru.
5. Potpisan prenos stiže kroz peer-e, connection fajl ili kontakt operatora. Klijenti ga proveravaju i usklađuju podatke pre promene izvora.

Stari sajt nije potreban. Potpis sam ne javlja offline replikama adresu novog hosta; pronalaženje je transport/administratorski postupak.

## Povratak starog sajta i sukobi

Klijenti čuvaju prihvaćenu generaciju, lanac i checkpoint. Posle validnog prenosa stari operativni ključ ne može dalje objavljivati prihvaćene promene. Odvojen klijent bez te informacije može još videti stari glavni sajt; prikazati prihvaćeni autoritet/svežinu i omogućiti ručan uvoz/ažuriranje.

Nema centralnog koordinatora. Dva vlasnika kopije recovery tajne mogu potpisati sukobljene tvrdnje. Prepoznati različite tvrdnje iste generacije i zaustaviti zajednički sync do izričitog rešavanja; ne birati prvopristiglu kao univerzalnu istinu niti prihvatati neproveren veći broj. I validan viši prenos zahteva pravila lanca i usklađivanja podataka.

Pre izdanja definisati rotaciju recovery ključa i kontinuitet klijenata koji su dugo offline. Gubitak recovery ključa onemogućava autentifikovano preuzimanje starog identiteta bez ranije podešenog alternativnog autoriteta; novi fork/registar je drugi identitet.

## Vraćanje podataka i rollback

Restore navodi koji checkpoint postoji i da li nedostaju nove promene. Ne koristiti stari publication ključ niti stare event ID-jeve za konfliktan sadržaj. Generacije/revizije i snapshot-resync proveriti napadačkim primerima. Javna replika možda nema sve privatne admin/medijske podatke za potpun oporavak organizacije.

## Potreban pregled bezbednosti

Kompromitovan server, ukraden recovery ključ, zlonameran izvor/mirror, izmenjen JSON/slike, dupli/preuređeni feed, replay, iscrpljivanje resursa, SSRF/redirect, stari root, paralelan takeover, prekinuta migracija i privatni podaci. Zaključati održavane crypto/HTTP biblioteke i pregledati postupak poverenja; sam dokument nije bezbednosna garancija.
