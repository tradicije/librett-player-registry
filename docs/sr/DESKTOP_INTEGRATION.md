# LibreTT Desktop integracija

[English](../en/DESKTOP_INTEGRATION.md)

Status: planirano; povezivanje sa registrom još nije implementirano u Desktop-u.

## Korisnički tok

U Igračima dodati „Preuzmi / ažuriraj registar” i JSON uvoz/izvoz. Podesiti izvorni URL ili pouzdan fajl povezivanja; prvi default može biti librett.org, ali mora biti zamenljiv. Isti registar kasnije može biti na stoni.rs ili sajtu druge organizacije. Javni katalog ne zahteva nalog igrača; privatni izvor traži poseban read-only authorization dizajn.

Preuzmi/proveri → prikaži nove zapise i promene → razreši mapiranja/obavezna polja → potvrdi transakciju → koristi offline. Neuspeh čuva lokalnu bazu i prihvaćeni cursor. Link profila otvara trenutnu udaljenu javnu stranu, ne hardkodovan domen.

## Poreklo podataka

Za povezanog igrača čuvati lokalni UUID, UUID registra, udaljeni UUID igrača, prihvaćeni autoritet/checkpoint, reviziju zapisa, poslednje preuzete normalizovane vrednosti i izričite lokalne override odluke. Remote metadata odvojena je od lokalnog profila. Klubovi imaju odgovarajuće mapiranje; remote ID ne zamenjuje lokalni ID.

Jedan lokalni igrač kasnije može imati pregledane veze sa više registara; prvo izdanje to može ograničiti. Duplo ime je kandidat za pregled, nikad automatsko spajanje. Povezivanje ručnog profila je izričito i ne zamenjuje istorijske prijave.

## Osvežavanje i lokalne izmene

Uporediti staru online vrednost B, lokalnu L i novu online R po polju:

| Uslov | Radnja |
| --- | --- |
| Nema lokalnog override-a i L = B | Primeni validnu R |
| Lokalni override; online nije promenjeno | Čuvaj lokalnu vrednost |
| Lokalni override; promenjeno i online | Default čuva lokalno; ponudi pregled/prihvatanje online vrednosti |
| Korisnik bira „koristi vrednost registra” | Primeni R i ukloni override tog polja |

Izričit override ostaje i kada vrednosti slučajno postanu iste. Pražnjenje polja je lokalna izmena. Nedostajuća online javna/neobavezna polja imaju dogovoreno značenje; ne prazniti lokalno prećutno. Pre upisa proveriti ceo validan desktop profil.

Online godište može nedostajati. Desktop ga zahteva, pa organizator mora dopuniti ili profil ostaje pending import, bez izmišljene godine. Fotografije preuzimati uz limits/provere/crop; URL nije zamena za validirane bytes tamo gde ih desktop zahteva.

## Uklanjanje i zastareli izvori

Tombstone označava da online profil nije dostupan; čuvati lokalnog igrača i istoriju turnira. Stari snapshot ne sme vratiti objavu niti prepisati novije poreklo. Nova generacija autoriteta proverava se kroz pouzdan prenos, ne samo po broju revizije.

Osvežavanje ne briše ručne lokalne igrače niti njihove klubove. Uvoz celog registra nije restore cele desktop baze.

## Jednosmernost

Lokalne izmene, uplate, dolasci i turniri nikada se ne šalju registru. Registry API klijent ima samo čitanje/preuzimanje. Predlozi pripadaju ovlašćenim registry sajtovima, ne skrivenoj desktop funkciji. Remote poreklo ne zaobilazi lokalnu validaciju niti takmičarska pravila.

## Kriterijumi prihvatanja

Prvi/ponovljeni uvoz, nov domen, ista imena/drugi identiteti, ručno povezivanje, lokalna i online izmena, prazno polje, nedostajuće godište, neispravna slika, purge, nepoznata šema, prekid uvoza, offline rad i istorijski snimci. Desktop kod se menja u njegovom repozitorijumu kada implementacija bude zatražena.
