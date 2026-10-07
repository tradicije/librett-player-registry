# Administratorski tokovi — planirano

[English](../en/ADMIN_GUIDE.md)

Ovo su budući ekrani, ne dostupno uputstvo za instalaciju.

Mediji prve etape dolaze iz zaštićenog storage-a tek posle odobrenja. Uvoz javnog snimka pravi neobjavljene nacrte sa izričitim mapiranjem; ne vraća privatna odobrenja niti daje autoritet izvora. Pogledaj [skladište/restore](STORAGE_AND_MIGRATIONS.md) i [spremnost](PHASE_0_READINESS.md).

## Prvo podešavanje

Aktivacija daje prazno stanje. Biraj „Napravi novi registar” ili „Hostuj repliku”. Nov registar dobija nov identitet; replika čuva identitet izvora. Početno jedan registar po instalaciji.

Za repliku unesi URL povezivanja ili pouzdan connection fajl. Pre potvrde prikaži naziv, ID, host, otisak i obim objave. Sam ID nije adresa. Proveri izvor pre preuzimanja stvarnih profila.

## Novi glavni registar

Unesi naziv/politiku objave, odredi administratore i napravi/preuzmi offline recovery materijal kroz odobren postupak. Nema default saveza/igrača. Objasniti razliku operativnog ključa, recovery autoriteta i admin lozinke.

Praktično je prvo uneti klubove. Forme biraju klub po UUID-u, polja profila i zasebno javnu objavu. Moderator pregleda duplikate; ne spaja samo po imenu. Glavni CRUD i dalje proverava capabilities i reviziju.

Pratiti predloženi [tok objave](PUBLICATION_POLICY.md): pripremiti neobjavljen nacrt, privatno zabeležiti odobrenje, pregledati javna polja i izričito odobriti objavu. Čuvanje izmena ih ne objavljuje automatski. Arhiviranje povlači javni pristup u prvoj etapi. Podešavanje recovery materijala važi tek za kasniju pregledanu funkciju poverenja, ne kao uslov prve etape na jednom sajtu.

## Javni profili

Liste igrača/klubova, pretraga, filteri i profili trajnog identiteta na čitljivim promenljivim URL-ovima. Javna polja/slike odvojena su od privatnih informacija. Replika prikazuje izvor/svežinu. Savez može dodati svoj urednički sadržaj bez menjanja kopiranih profila.

## Replika

Sync prikazuje poslednji uspešan checkpoint, prihvaćen glavni izvor, pending status i razumljivu grešku. Neuspeh čuva poslednju kopiju uz oznaku zastarelosti. Admin uređuje prikaz, ne objavljuje direktne zajedničke izmene. „Predloži dodavanje/izmenu/uklanjanje” pravi zaseban zahtev za glavnu moderaciju. Predlozi mogu tražiti uparivanje i kada je čitanje javno.

## Uvoz/izvoz i backup

Javni JSON je prenosiva javna projekcija. Privatni backup organizacije ima dodatne admin podatke i posebno se štiti. Nijedan ne sadrži tajni recovery ključ. Mediji traže proverljiv paket/preuzimanje; WordPress attachment ID nije dovoljan.

Pre potvrde prikaži izvor, identitet i broj/sukobe zapisa. Kopiranje starog registra za nezavisan nastavak traži novi identitet. Deaktivacija čuva podatke. Update/uninstall ne briše ih prećutno.

## Oporavak

Validna kopija/checkpoint i potpisano recovery odobrenje postavljaju novi glavni izvor/ključ. Stari host ne mora odgovoriti. Zabeležiti moguć gubitak promena i dostaviti potpisan prenos peer-ima/operatorima. Oporavak čuva normalnu admin autorizaciju; ne omogućava anonimne izmene.

Pročitaj [poverenje i oporavak](TRUST_AND_RECOVERY.md). Izgubljen ključ/sukob tvrđenja daju jasnu putanju neuspeha ili rešavanja, ne prećutno prihvatanje novog autoriteta.

## Jezici i upotrebljivost

Srpski/engleski UI, keyboard forme, čitljivi razmaci, jednostavne potvrde/pregledi, javne mobilne strane i razumljive greške. Podaci bilo koje države su podržani konceptom; imena/sadržaj profila ne prevode se automatski.
