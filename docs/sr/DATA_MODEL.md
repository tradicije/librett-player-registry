# Model podataka i životni ciklus

[English](../en/DATA_MODEL.md)

Status: konceptualni model, ne završena šema/migracija.

## Identitet

UUID registra ne zavisi od URL-a; pri autentifikovanoj replikaciji povezuje se sa zapamćenim podacima poverenja. Igrači/klubovi imaju UUID unutar registra. Spoljni identitet je (UUID registra, UUID zapisa); WordPress brojevi i javni slug-ovi nisu identifikatori. Ista imena ne znače iste osobe; različiti registri se ne spajaju prećutno.

Replika čuva ID-jeve registra/zapisa. Nov nezavisan registar dobija novi ID. Nezavisan fork mora objaviti novi identitet i eventualno poreklo; kopiranje zapisa ga ne čini glavnim izdavačem originala.

## Konceptualne custom tabele

| Logička kolekcija | Svrha |
| --- | --- |
| Metapodaci registra | Naziv, UUID, verzije šeme/protokola, politika objave |
| Igrači | UUID, revizija, polja profila, stanje objave |
| Klubovi | UUID, revizija, naziv, skraćenice/alias-i, status |
| Članstva | Veza igrača i klubova; istorijski zahtevi se dogovaraju |
| Mediji | Trajni ID, digest sadržaja, tip/veličina, prava/objava |
| Prihvaćene promene | Redosled javnih promena/checkpoint-a |
| Tvrdnje autoriteta | Prihvaćena generacija, ključevi i potpisani prenosi |
| Stanje replike | Identitet peer-a, cursor, izvor/svežina, status sinhronizacije |
| Predlozi | ID zahteva, početna revizija, patch, status moderacije |
| Privatni audit | Akteri i administratorske odluke, ne javna replikacija |

Lokalni SQL indeksi/ograničenja i transakcije idu kroz repositories; detalji se zaključuju pregledom šeme. Ne duplirati istu činjenicu u više promenljivih JSON blob-ova. Prenosivi izvoz je verzionski model, ne SQL dump.

Predložena [politika objavljivanja](PUBLICATION_POLICY.md) određuje javna polja i odvaja radne zapise od odobrenih projekcija. Nov/fajlom uvezen zapis počinje kao neobjavljen; privatne izmene traže pregled objave. Javne revizije/checkpoint-i ne smeju otkriti broj privatnih izmena. Tačne storage i JSON šeme ostaju otvorene.

## Profili igrača

Prvi kandidati polja: prikazno/ime/prezime gde postoje, neobavezno godište, država/region, klubovi, plain-text biografija i referenca fotografije. Pre stvarnog prikupljanja dogovoriti javna polja. Detaljna dostignuća i istorijska članstva traže modele, ne proizvoljan neproveren JSON.

Tačan datum rođenja, kontakti, dokumenti i profili maloletnika nisu javni po default-u. Godište online može nedostajati iako ga Desktop zahteva za validan lokalni profil; importer mora imati dopunu. Slug se menja bez promene UUID-a.

## Revizije i brisanje

Glavne izmene proveravaju očekivanu reviziju i atomski upisuju zapis, audit i javnu promenu. Sukob zahteva ponovno učitavanje/pregled. Globalni feed sequence vezan je za generaciju autoriteta; oporavak starog checkpoint-a ne sme dati isti logički change ID drugom sadržaju.

Arhiviranje skriva/označava zapis prema politici objave. Povlačenje uklanja javnu projekciju i šalje minimalan tombstone. Purge uklanja lična polja/medije prema retention pravilima; događaj brisanja ne nosi obrisane lične podatke. Replika uklanja objavu, dok desktop beleži povlačenje bez brisanja istorije turnira.

Nije moguće prinudno ukloniti svaku već preuzetu javnu kopiju. Pravila čuvanja i zakonskog brisanja traže posebnu odluku organizacije. Stari uvoz ne sme prećutno vratiti tombstone zapis.

## Nezavisnost podataka

Javni JSON snimci sadrže samo objavljene podatke, identitet šeme i provereno poverenje/poreklo kada bude uključeno. Privatni backup ima administrativne podatke; slike traže poseban prenosiv paket ili provereno preuzimanje. Backup ne sadrži tajni offline recovery ključ.

Aktivacija uključuje nula igrača/klubova. Nakon podešavanja/uvoza podaci su u bazi instalacije, van source paketa. Ažuriranje plugina menja kod, ne bazu igrača. Promena šeme zahteva prethodni bekap i politiku bezbednog neuspeha.
