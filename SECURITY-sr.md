# Bezbednosna politika

[English](SECURITY.md)

Inženjerske odluke i ograničenja faze 0 su u [evidenciji spremnosti](docs/sr/PHASE_0_READINESS.md). Javna šema zahteva i [parser/semantičke provere](docs/sr/CONTRACT_VALIDATION.md); potpisani formati imaju [zasebne kasnije preglede](docs/sr/AUTHENTICATED_PUBLICATION.md).

## Podržane verzije

Još nema izvršivog izdanja niti podržane produkcione verzije. Repozitorijum sadrži predlog dizajna, ne auditovane kontrole. Pre izdanja treba izabrati runtime verzije, implementirati zahteve modela pretnji i objaviti stvarnu matricu podrške.

## Prijavljivanje

Ako je projekat objavljen na GitHub-u i uključene su privatne prijave ranjivosti, koristi Security karticu za privatnu prijavu. Ovaj fajl ne uključuje tu opciju. Ako privatni kanal nije dostupan, kontaktiraj maintainer-a ili otvori minimalan javni zahtev za privatni kanal, bez detalja napada ili ličnih podataka. Privatni email i rok odgovora još nisu određeni.

Navedi komponentu/verziju, korake sa izmišljenim podacima, posledice i predlog ispravke ako ga imaš. Ne šalji ključeve za oporavak, lozinke, cele baze igrača ili nejavne fotografije. Ne napadaj tuđe instalacije.

## Planirane granice poverenja

Pročitaj [poverenje i oporavak](docs/sr/TRUST_AND_RECOVERY.md). WordPress hosting, admin sesije, spoljne registre, JSON/medije, replike i desktop uvoz tretiramo kao odvojene granice. Kompromitovan hosting može zaobići lokalne provere; potpisane objave štite klijente koji proveravaju autoritet, ne filesystem hosting vlasnika od samog vlasnika.

Javni izvozi sadrže samo javnu projekciju. Administrativni izvozi/bekapi imaju druge privatne podatke i zahtevaju ograničen pristup. Ključ autoriteta za oporavak ne kopira se na replike niti čuva u javnim zapisima; operativni serverski ključevi štite se posebno.

Uvoz zahteva proveru strukture i značenja, ograničenja resursa i transakcije. Mrežno preuzimanje zahteva zaštitu od SSRF-a/redirect/DNS zloupotrebe, TLS proveru i ograničenja veličine/vremena. Potpis ne čini sadržaj bezbednim za prikaz niti staru kopiju ažurnom.

Dozvole se proveravaju na serveru. WordPress nonce nije zamena za capability proveru. Oporavak ne sme zahtevati da originalni server radi i mora prepoznati zastarele, ponovljene i sukobljene tvrdnje o autoritetu. Ne pravimo sopstvene kriptografske algoritme.

## Privatnost i objava

Profili i fotografije mogu biti lični podaci, uključujući podatke maloletnika. Pre produkcije definiši šta se prikuplja, svrhu, ko sme da objavi i šta javne replike smeju da distribuiraju. Javna replikacija ne može garantovati uklanjanje svake preuzete kopije. Arhivski uvoz ne sme zaobići pravila povlačenja/brisanja podataka.

Brisanje, čuvanje, redakcija i zakonita upotreba baze zahtevaju pregled organizacije pre stvarnog uvoza. AGPL ne daje pravni osnov za objavu ličnih podataka. Ovaj dokument definiše inženjerske zahteve, ne potvrdu pravne usklađenosti.
