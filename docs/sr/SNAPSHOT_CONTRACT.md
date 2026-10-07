# Ugovor prenosivog snimka — nacrt 1

[English](../en/SNAPSHOT_CONTRACT.md)

Status: predlog ugovora faze 0 za pregled, ne objavljen format ili odobrena normativna JSON Schema. `schema_version: 1` je kandidat oznake formata, ne verzija plugina. Nacrt određuje celovit javni snimak za prvi sajt; ne određuje potpisanu replikaciju, privatni backup ili administratorski restore.

## Tipovi i envelope

UTF-8 JSON sa jednim objektom u korenu, bez duplih ključeva, sadržaja posle dokumenta ili nekonačnih brojeva. Nepoznata svojstva odbijaju se na svakom nivou; proširenja traže podržanu verziju šeme. Bez prećutnog pretvaranja tipova. UUID je kanonski string malim slovima sa crticama, bez nil vrednosti; novi ID-jevi su nasumični UUIDv4 kroz injektovan održavan generator. Uvezeni UUID-i se proveravaju i čuvaju kao poreklo, ne izvode ponovo iz imena ili URL-a.

Brojači su kanonski decimalni stringovi (`0` ili cifra različita od nule praćena ciframa), do 19 cifara i najviše 9223372036854775807. Porediti numerički, ne leksikografski; izbegava se gubitak preciznosti JavaScript brojeva. Javne revizije počinju od `"1"`, checkpoint od `"0"`. Prekoračenje brojača daje izričit neuspeh, bez vraćanja na početak. Stringovi su validan Unicode; ograničenja broje Unicode code point-e osim kada su navedeni bajtovi.

Sva svojstva envelope-a iz tabele su obavezna:

| Svojstvo | Vrednost |
| --- | --- |
| `format` | Tačno `librett-registry-public-snapshot` |
| `schema_version` | Integer `1` |
| `registry_id` | UUID izvornog registra |
| `registry_name` | Neprazan običan tekst, najviše 200 znakova |
| `authority_generation` | `null` za ovaj nepotpisan nacrt; druga vrednost traži budući posebno podržan autentifikovan ugovor |
| `checkpoint` | Brojač redosleda javnih objava |
| `exported_at` | UTC timestamp tačno `YYYY-MM-DDTHH:mm:ssZ`, validan datum; samo prikazni metapodatak |
| `publication_policy` | Objekat opisan ispod |
| `players`, `clubs`, `memberships`, `media`, `tombstones` | Nizovi; prazni su dozvoljeni, nikada `null` |

`publication_policy` ima tačno `version` (neprazan string, najviše 64), `purpose` (neprazan običan tekst, najviše 1000), `dataset_terms_url` i `media_terms_url` (HTTPS URL-ovi, najviše 2048) i `distribution_scope` (tačno `public-download`). To su izjave operatora, ne mašinski dokaz dozvola ili pravne usklađenosti. Izvoz je isključen dok nisu podešeni uslovi i odobrenja objave. URL je metapodatak; parser/uvoz ga ne preuzima automatski. Privatni dokazi i privatni brojači su zabranjeni.

## Objavljeni zapisi

Obavezna polja ne smeju nedostajati ili biti null. Neobavezna mogu nedostajati; null se odbija u ovom nacrtu. Odsustvo znači da podatak nije javno dostavljen i ne odobrava brisanje lokalne Desktop vrednosti. Prazan neobavezan tekst/reference odbijaju se; prazan niz `aliases` je dozvoljen. Snimak sadrži pune projekcije, nikada patch-eve.

| Objekat | Obavezna polja | Neobavezna polja |
| --- | --- | --- |
| Player | `id` UUID, `revision` brojač, `slug`, `display_name` | `given_name`, `family_name`, `birth_year`, `country`, `region`, `biography`, `photo_id` UUID |
| Club | `id` UUID, `revision` brojač, `slug`, `name` | `abbreviation`, niz `aliases`, `country`, `region` |
| Membership | `player_id` UUID, `club_id` UUID | Nema |
| Media | `id` UUID, `revision` brojač, `content_sha256`, `mime_type`, `byte_length`, `width`, `height`, `content_url`, `attribution` | Nema |
| Tombstone | `entity_type`, `entity_id` UUID, `revision` brojač, `removed_at_checkpoint` brojač | Nema |

Imena/prikazna imena/alias-i su neprazan običan tekst do 200 znakova; skraćenica kluba do 32; država/region do 100; biografija do 5000. Običan tekst odbija kontrolne znakove osim line feed-a u biografiji; prikaz uvek escape-uje tekst. Bez izvođenja imena ili spajanja identiteta prema lokalnim jezičkim pravilima. Država/region su opisne oznake, ne standardizovana šema kodova država.

Slug: 1–200 malih ASCII slova/cifara odvojenih pojedinačnim crticama, bez početne/završne crtice. Jedinstven je unutar tipa trenutno objavljenih zapisa i može se menjati bez promene identiteta. Imena nisu jedinstvena. `birth_year` je integer od 1 do 9999; pregled objave utvrđuje smislenost i maloletnost bez poziva sata iz domenskih pravila. Desktop može zahtevati strožu validaciju ili dopunu.

Članstva opisuju trenutne javne veze, bez istorije ili značenja primarnog kluba. Par `(player_id, club_id)` je jedinstven i oba zapisa moraju biti uključena. ID-jevi igrača/klubova jedinstveni su u svojim tipovima; ID-jevi medija unutar medija. Reference se rešavaju prema tipu, ne imenu.

Medij opisuje javnu proverenu izvedenu sliku, ne original ili attachment ID. `content_sha256` ima tačno 64 mala heksadecimalna znaka. `mime_type` je `image/jpeg` ili `image/png`. Broj bajtova je integer 1–5242880; širina/visina integer 1–4096, do 16777216 dekodiranih piksela. `content_url` je apsolutan HTTPS URL do 2048 znakova bez userinfo-a ili fragmenta. `attribution` je neprazan običan tekst do 500 znakova. Svaka photo referenca vodi do uključenog medija; svaki medij referencira objavljen igrač. Bajtovi slike nisu ugrađeni. Uvoz opisa ne preuzima automatski; kasnije ovlašćeno preuzimanje proverava mrežnu politiku, digest, stvarni tip, dimenzije i granice dekodiranja. Promena URL-a traži novu javnu reviziju medija.

`entity_type` tombstone-a je `player`, `club` ili `media`. Nema imena, razloga, aktera ili drugog ličnog polja. Checkpoint je pozitivan i nije veći od checkpoint-a snimka. Isti `(entity_type, entity_id)` ne može biti i živ i tombstoned. Čuvati poslednji tombstone povučenog identiteta; izričita ponovna objava ga zamenjuje novijom živom revizijom. Nema tombstone-a za nikada objavljen nacrt. Sažimanje tombstone-a i čuvanje feed-a su odloženi; ograničenja ne smeju prećutno odbaciti znanje o uklanjanju.

## Redosled i ograničenja

Izvozni nizovi su uređeni po UUID-u (`players`, `clubs`, `media`), po UUID-u igrača pa kluba (`memberships`) i po tipu pa UUID-u (`tombstones`). Klijenti prihvataju drugi redosled; ovo nije kriptografska kanonizacija. Redosled svojstava objekta nema značenje.

Predložene čvrste granice uvoza/izvoza: 32 MiB UTF-8 bajtova, dubina 8 (koren objekat dubina 1; svaki ugnježden niz/objekat dodaje 1), ukupno 100000 stavki svih nizova, 20000 igrača, 5000 klubova, 40000 članstava, 20000 medija i 50000 tombstone-a. Ukupna i pojedinačna ograničenja važe zajedno, uključujući alias-e; najviše 20 jedinstvenih alias-a po klubu. Pojedinačan JSON string token ograničen je na 32768 kodiranih bajtova uz ograničenje znakova polja. Kompresovani fajlovi/ugrađene arhive nisu podržani. Ovo je osnova za pregled, ne izmerena tvrdnja o kapacitetu; operator može smanjiti granice, ne zaobići ih. Prevelik izvoz ne proizvodi nepotpun snimak; veći/straničen format traži nov pregledan ugovor.

## Revizije i transakcije

Privatni `edit_revision` odvojen je od javnog `revision`. Čuvanje nacrta povećava samo privatnu reviziju i audit. Objava proverava očekivanu privatnu reviziju, trenutnu javnu reviziju i verziju politike; uspešna javna promena povećava javnu reviziju zapisa i globalni checkpoint i atomski upisuje projekciju, događaje uklanjanja/upsert-a i privatni audit. Odobrenje bez javne promene ne pravi javnu reviziju/checkpoint. Prva objava ima reviziju 1; povlačenje i izričita ponovna objava je povećavaju, bez resetovanja posle purge-a. Purged UUID se ne dodeljuje drugoj osobi.

Transakcija sa više javnih zapisa dodeljuje poseban uzastopan sequence svakoj promenjenoj projekciji; snimak hvata završni potvrđen checkpoint. Promena članstva povećava reviziju projekcije igrača; brisanje/povlačenje kluba uklanja javna članstva i povećava revizije obuhvaćenih igrača. Povlačenje medija atomski uklanja photo reference i povećava revizije igrača. Ako cela promena zavisnosti ne uspe, nema delimičnog javnog upisa. Privatne izmene ne određuju javni sequence.

Promena javnih metapodataka politike povećava checkpoint i bez promene polja zapisa; sužavanje obima atomski ažurira projekcije. Privatni dokazi ne povećavaju checkpoint. Čitanje/izvoz koristi konzistentan pogled baze na projekcije, politiku i checkpoint. Interna šema događaja i budući potpisan feed ostaju otvoreni.

## Režimi uvoza i identitet

1. **Nov nezavisan primary iz fajla:** nov UUID registra i novi lokalni UUID-i zapisa; sačuvati izričita mapiranja/poreklo i prepisati veze. Sve ide u neobjavljene nacrte. Brojači izvora nisu lokalni brojači, niti su metapodaci autoriteta fajla lokalni autoritet.
2. **Spajanje fajla sa postojećim primary-jem:** čuvati lokalni identitet registra, pripremiti kandidate i zahtevati izričita mapiranja; ranije uvezeni ID-jevi koriste postojeća mapiranja. Ista imena ne spajaju automatski. Javne projekcije ostaju iste do odobrenja. Isti sadržaj/mapiranja ne prave duple nacrte ili audit izmene; nov sadržaj izvora traži pregled i očekivane lokalne edit revizije. Nepotpisane revizije ne dokazuju svežinu.
3. **Desktop čitanje/uvoz (kasnije):** odvojeno čuvati remote identitet i lokalno mapiranje, sačuvati izričite lokalne izmene i istorijske snimke, pregledati promene i odsutna polja tretirati kao nedostupna. Uvoz ne autentifikuje izvor.

Privatni restore koji čuva identitet registra je drugi tok i ne može se rekonstruisati javnim snimkom. Proverena primena replike nije dostupna ovim nacrtom. Kopiran nepotpisan fajl ne uspostavlja primary autoritet niti prepisuje zapamćen pouzdan izvor. Promena URL-a ne menja remote identitet.

Ograničiti ulaz pre parsiranja; proveriti ceo graf i granice u staging-u; pregledati brojeve, poreklo, sukobe i mapiranja; na potvrdi ponovo proveriti dozvole, očekivane revizije i politiku; atomski upisati prihvaćene nacrte i potvrdu uvoza. Greška/otkazivanje čuva prethodne podatke i stanje uvoza. Potvrda koristi lokalni request UUID i digest payload-a: isti retry vraća prvobitan rezultat; isti request UUID sa drugim bajtovima odbija se. Semantičko ponavljanje prepoznaje se prema validiranom normalizovanom sadržaju i mapiranju, ne timestamp-u ili redosledu ključeva. Relevantna lokalna promena poništava pregled i traži nov. Parsiranje ne izvršava HTML, prati URL-ove niti šalje podatke.

## Primeri pregleda i preostali rad

Prazan registar ima checkpoint `"0"`, prazne nizove i podešenu politiku. Prva objava igrača ima reviziju `"1"` i checkpoint `"1"`; privatna izmena ne menja nijedan. Povlačenje uklanja živog igrača i daje tombstone revizije `"2"`, checkpoint `"2"`. Nova odobrena objava koristi reviziju `"3"` i zamenjuje tombstone. Remote revizije nepotpisanog fajla ostaju neprovereni metapodaci.

Pregled mora obuhvatiti duple JSON ključeve/UUID-e, nedostajuće reference, null neobavezna polja, nepoznata polja, nevalidan UTF-8, granice, velike brojače, dupla članstva, privatne podatke, ista imena/različite ID-jeve, ponovljen uvoz, isti request UUID/drugi payload, zastareo pregled, prekinut upis, povlačenje zavisnosti i očuvanje offline Desktop podataka. To su predloženi slučajevi, ne već izvršeni testovi ili interoperabilni primeri.

Sledeće: pregled nacrta, normativna JSON Schema i izmišljeni interoperabilni primeri, privatno skladište/oporavak migracija, ograničen parser/alati i deployment matrica, zaseban pregled autentifikovanih envelope-a. Ne tvrdi se runtime kompatibilnost ili bezbednosna verifikacija.
