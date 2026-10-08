# Politika objavljivanja — predlog faze 0

[English](../en/PUBLICATION_POLICY.md)

**Razvojno ažuriranje (2026-10-08):** [Implementirani obim](IMPLEMENTATION_STATUS.md) beleži migracije 001–006, privatni katalog, objavu/medije, nepotpisan REST/JSON i Desktop uvoz. Kasniji trust/replika/recovery ugovori i predlozi pravne politike ispod ostaju predlozi. Ranije izjave faze 0/bootstrap-a su istorijske i ne opisuju sadašnji katalog.

Status: usvojena inženjerska osnova dizajna, ne odobrena pravna politika niti implementirana kontrola. Tačna JSON polja određuje priložena šema snimka. Ovaj dokument ne odobrava korišćenje stvarne baze.

Ažuriranje faze 0: usvojeno kao inženjerska osnova kroz [ADR 0004](../adr/0004-phase-0-engineering-baseline.md). [Evidencija spremnosti](PHASE_0_READINESS.md) daje aktuelan status; [šema/primeri validacije](CONTRACT_VALIDATION.md) i [skladište](STORAGE_AND_MIGRATIONS.md) razrađuju dokument. To ne podrazumeva pravno odobrenje, runtime proveru ili odobren autentifikovan format.

## Prvi katalog

Za prvu etapu na jednom sajtu predlaže se javni katalog. Posetioci bez naloga i Desktop čitaju istu odobrenu projekciju; privatni katalog je odložen. Administrativni podaci ostaju zaštićeni. Javan pristup sam po sebi ne daje prava redistribucije: operator mora posebno odrediti uslove za bazu i medije pre uključivanja masovnog izvoza. Proverena replikacija dolazi kasnije.

Svrha: identifikacija igrača i klubova kroz javne profile i opciono preuzimanje za prijave na turnir. U ovoj etapi ne prikupljati kontakte, tačne datume rođenja ni lične dokumente. Ne uvode se nalozi igrača, rezultati turnira ili rangiranja.

## Izbor licence baze — planirano

Administrator bira licencu baze podataka u podešavanjima dodatka u `wp-admin`: **ODbL 1.0**, **CC0 1.0**, **CC BY 4.0**, **CC BY-SA 4.0**, **All rights reserved** ili **Custom**. Za Custom administrator može da navede URL licence ili da otpremi dokument licence. Licenca baze se ne bira automatski. Tok podešavanja je implementiran; operator mora obezbediti zakonit osnov objave.

Licenca baze određuje se odvojeno od AGPL-3.0-or-later licence dodatka, odobrenja za objavu ličnih podataka i dozvola za fotografije.

## Dozvoljena javna polja

Samo izričito dozvoljena polja ulaze u javne strane, pretragu, API, snimke i budući tok promena. Projekcija ne sme nastajati iz svih sačuvanih polja uz uklanjanje poznatih tajni.

| Zapis / polje | Predloženo pravilo objave |
| --- | --- |
| UUID registra i zapisa, javna revizija, slug profila | Javni metapodaci objavljenih zapisa; nikada WordPress ID reda |
| Prikazno ime igrača | Obavezno za objavu; neprazan, pregledan običan tekst |
| Ime i prezime | Neobavezno, uz zasebno odobrenje; ne izvoditi razdvajanjem prikaznog imena |
| Godište | Neobavezno i zasebno odobreno; ne izmišljati ako nedostaje |
| Država i region | Neobavezne, pregledane šire oznake; bez kućne adrese i precizne lokacije |
| Identitet/naziv kluba i trenutno članstvo | Neobavezno; samo pregledana veza sa objavljenim klubom |
| Biografija | Neobavezan pregledan običan tekst; bez sirovog HTML-a i proizvoljnih strukturiranih dostignuća |
| Fotografija i javna atribucija | Neobavezno; traži odobrenje objave profila i dokumentovane dozvole za medij |
| Naziv kluba, skraćenice/alias-i, država/region | Naziv obavezan; ostala polja neobavezna i pregledana |
| Kredencijali, beleške moderacije, audit akteri, dokazi izvora i odobrenje objave | Privatno; isključeno sa svih javnih površina |

Detaljna istorija članstva, dostignuća i dodatna polja klubova su odloženi. Objavljen igrač ne sme otkriti neobjavljene klubove ili medije. Indeksi pretrage, brojevi, filteri i direktno pronalaženje koriste istu projekciju i ne otkrivaju nacrte.

## Odobrenje objave

Nov ili uvezen zapis počinje kao neobjavljen. Javne oznake iz uvoza ne odobravaju lokalnu objavu. Buduća proverena replika prati prihvaćenu projekciju glavnog registra prema politici replikacije; proizvoljan fajl ne postaje autoritet.

Ovlašćen administrator glavnog registra priprema polja, beleži svrhu i odobrenje, pregleda prikaz i izričito objavljuje izabranu projekciju. Aplikacija proverava dozvolu, očekivanu reviziju, reference i politiku pri svakoj izmeni. Dozvola za uređivanje ne podrazumeva dozvolu za odobrenje objave; tačni WordPress capability nazivi određuju se kasnije.

Privatni metapodaci odobrenja: verzija politike, odobren obim, odluka, odobravalac, vreme odluke i ograničeno dostupna referenca dokaza gde je potrebna. To nisu javna polja profila. Operator određuje pravni osnov i potrebne dokaze; softver ne sme zahtevati saglasnost kao jedini mogući osnov niti tvrditi da administratorski checkbox uspostavlja usklađenost.

Profili maloletnika ostaju neobjavljeni dok se ne podese posebna dokumentovana politika i odgovarajući tok odobrenja. Samo godište nije dovoljno za tačnu starost ili pravni status; nepoznat status traži pregled. Koristiti izmišljene primere dok organizaciona politika ne bude rešena.

Izmena odobrenih javnih vrednosti ili izbor novih polja zahteva izričit pregled objave. Prethodno objavljen sadržaj ostaje prihvaćena projekcija dok privatna izmena čeka pregled, osim kada administrator odmah povuče objavu. Javni izvoz čita prihvaćenu projekciju, nikada radni nacrt.

## Životni ciklus

| Radnja | Javni ishod | Privatni ishod |
| --- | --- | --- |
| Pravljenje/uvoz nacrta | Bez javnog zapisa, medija, rezultata pretrage ili tombstone-a | Zapis za uređivanje i podaci izvora |
| Objava odobrene projekcije | Profil/pretraga/API/izvoz usklađeni na jednom checkpoint-u | Zabeleženo odobrenje i audit |
| Čuvanje izmena na čekanju | Ostaje prethodna odobrena projekcija | Sačuvan nacrt i stanje pregleda |
| Arhiviranje | U prvoj etapi povlači javnu objavu | Zapis ostaje za administraciju |
| Povlačenje objave | Uklanja profil, pretragu i javni pristup medijima; minimalan tombstone ako je zapis bio objavljen | Odobrenja/istorija čuvaju se prema politici operatora |
| Purge | Događaji brisanja nemaju lična polja; ostaju samo potrebni metapodaci uklanjanja | Lični podaci/mediji brišu se prema pravilima čuvanja |
| Ponovna objava | Novo izričito odobrenje i novija javna revizija; purged identitet se ne dodeljuje drugoj osobi | Beleži odluku; zastareo uvoz je ne može pokrenuti |

Stanje objave odvojeno je od administrativnog čuvanja. Privatni storage enum-i čekaju konkretan DDL; javni brojači/revizije određeni su ugovorom snimka. Javna projekcija ne sme otkriti broj privatnih izmena. Deljen medij ostaje dostupan samo ako ga legitimno referencira druga odobrena javna projekcija. Zaštićeni originali i izvedene slike traže adapter koji ne otkriva neobjavljen Media Library URL; skriven profil ne štiti attachment.

Povlačenje ne može obrisati kopije koje su Desktop ili treća lica već preuzeli. Desktop čuva lokalne profile i istorijske snimke turnira uz podatak o povlačenju izvora. Zastareo uvoz ili izmena politike ne objavljuju povučene zapise automatski.

## Izmena politike i uslovi za izdanje

Politika operatora ima verziju. Sužavanje obima atomski uklanja obuhvaćene javne podatke i beleži checkpoint. Širenje obima ne objavljuje automatski ranije privatna polja. Javni metapodaci politike opisuju svrhu, dozvoljena polja, uslove baze/medija i obim distribucije; privatni dokazi odobrenja su isključeni.

Pre stvarne objave rešiti odgovornost operatora, pravni osnov, maloletnike, pristup/čuvanje dokaza, zahteve za povlačenje, redistribuciju baze i prava fotografija. Pre implementacije definisati normativnu šemu, ograničenja ulaza, dozvole, transakciju objave i skladištenje medija.

Planirani scenariji pregleda: nacrt nije dostupan javno; privatna izmena ne curi; zabranjena ugnježdena polja odbijena; neobjavljene reference isključene; neovlašćena objava odbijena; uvoz se ne objavljuje sam; maloletan/nepoznat status čeka pregled; povlačenje uklanja javne izvedene medije; deljeni medij ostaje samo uz legitimnu referencu; zastareli podaci ne vraćaju povučenu objavu; šira politika traži novo odobrenje. Ovo su kriterijumi prihvatanja, ne izvršeni testovi.
