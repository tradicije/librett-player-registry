# API i prenosivi JSON — predlog

[English](../en/PROTOCOL.md)

Ovo nije implementiran API. Nazivi/polja su predlog; normativna šema i interoperabilni primeri moraju biti odobreni pre koda.

## Transport i pronalaženje

WordPress adapter može izložiti `librett-registry/v1` preko REST API-ja. Prenosivi ugovor opisuje operacije, ne obaveznu `/wp-json/` putanju. Link/fajl povezivanja navodi adrese endpoint-a, UUID registra, naziv, verzije šeme/protokola, javni obim i podatke poverenja kada budu uključeni.

UUID sam ne pronalazi izvor i ne dokazuje identitet. Prva veza zahteva pouzdano dobijen otisak/fajl van tog izvora ili izričito prihvatanje prvog poverenja uz njegova ograničenja. HTTPS štiti transport, ali ne dokazuje koja organizacija poseduje kopirani UUID. Nikad prećutno ne zameniti zapamćen root key novim manifestom.

Predložene operacije:

| Operacija | Korisnici | Autorizacija |
| --- | --- | --- |
| Manifest/capabilities | Sajt/desktop | Javno ili politika čitanja kataloga |
| Pretraga/lista javnih igrača/klubova | Publika/desktop | Obim objave |
| Konzistentan celovit snapshot | Replika/desktop | Pravila čitanja/replikacije |
| Stranica promena nakon cursor-a | Replika/desktop | Ista pravila; proveren autoritet kada bude uključen |
| Javni opis/sadržaj medija | Klijenti | Pravila objave medija |
| Slanje/pregled predloga | Uparena replika | Kredencijal ograničen na predloge |
| Moderacija/CRUD/admin backup | Administratori | Server-side capabilities |
| Uvoz potpisanog prenosa autoriteta | Oporavak/admin | Postojeće recovery poverenje + lokalni admin |

Desktop samo čita/preuzima. Nikada ne poziva predloge ili CRUD. Isti API namespace na više sajtova ne znači isti registar.

## Snapshot envelope

Predložena logička polja: `format`, `schema_version`, `registry_id`, `authority_generation`, `checkpoint`, `exported_at`, `publication_policy`, `players`, `clubs`, `memberships`, `media`, `tombstones` i odvojen/autentifikovan envelope kada je potpisivanje uključeno. Datum je prikazni podatak, ne jedini dokaz svežine/redosleda. SQL ID-jevi, lozinke, privatni audit, dokazi predloga i recovery tajne nisu javni.

Snapshot je konzistentan na jednom checkpoint-u. Nedostajuće polje i izričit null imaju dogovorena različita značenja; odsustvo javnog polja ne odobrava brisanje lokalnog desktop podatka. Šema mora odrediti ograničenja veličine/broja/dubine/stringova/medija i obavezna/neobavezna polja.

Nepotpisan fajl može se izričito uvesti kao neproveren lokalni izvor; ne uspostavlja pouzdan identitet glavnog registra. Potpisan uvoz proverava envelope, kanonske bajtove, lanac odobrenja i šemu pre primene.

## Tok promena

Svaka potvrđena promena ima identitet registra, generaciju autoriteta, uređen sequence, trajan change ID, tip/UUID zapisa, očekivanu/novu reviziju i upsert ili minimalan tombstone. Potpisana stranica/checkpoint vezuje redosled i autoritet; obična JSON serijalizacija nije specifikacija potpisa.

Sesija ima stabilan gornji checkpoint. Straničenje ne sme preskakati zapise dok nastaju novi upisi. Stranicu i cursor primeniti u istoj transakciji; pri grešci sačuvati poslednji cursor, a replay učiniti idempotentnim. Praznine ili istek čuvanja feed-a traže novi konzistentan snapshot; ne preskakati nepoznate rupe.

Nova generacija traži izričito usklađivanje sa vraćenim checkpoint-om. Sam sequence ne bira pobednika posle oporavka. Novi glavni sajt ne koristi stari publication ključ niti konfliktne change ID-jeve posle rollback-a.

## Predlog potpisa

Proceniti održavanu Ed25519 biblioteku i preciznu kanonsku JSON reprezentaciju (npr. RFC 8785). Definisati context/verziju envelope-a i potpisati ID registra, generaciju, tip objekta i ceo kanonski payload. Dogovoriti Unicode, brojeve, null/odsutna polja i redosled nizova. Ovo je dizajn protokola, ne dozvola za ručnu implementaciju kriptografije.

Izvori: [EdDSA / RFC 8032](https://www.rfc-editor.org/rfc/rfc8032), [JSON canonicalization / RFC 8785](https://www.rfc-editor.org/rfc/rfc8785).

## Predlozi

Zahtev ima UUID peer-a/zahteva, ID registra, operaciju, UUID cilja ili novi profil, početnu reviziju i proverene željene vrednosti. Pending predlog je odvojen od objavljenog zapisa. Isti request ID/payload vraća isti ishod; isti ID sa drugim sadržajem odbija se. Prihvatanje proverava trenutnu reviziju i pravi jednu zvaničnu promenu. Zastareo predlog traži ljudski pregled; odbijanje ne pravi reviziju igrača.

## Provera i greške

Ograničiti download/parsing pre pune alokacije. Proveriti reference, UUID-e, duple ID-jeve, enum-e, revizije i digest medija. Velik uvoz ide u staging i atomski se uključuje; delimični profili se ne prikazuju kao završena kopija. Neispravan/nepodržan ulaz ne menja prihvaćeno stanje.

Stabilni error kodovi razlikuju autentifikaciju, dozvolu, sukob, nepoznatu šemu, nepouzdan autoritet, zastareo checkpoint, neispravne podatke i limits. UI prevodi poruke; kodovi ne zavise od jezika. HTTP detalji zaključuju se sa šemom.

## Prenosivost i dozvole

Isti logički snapshot dostupan je kao fajl i download. URL se menja; identitet/poverenje ostaju zapamćeni. Mirror prikazuje samo proverenu javnu projekciju i čuva izvor/checkpoint. Prava objave/replikacije baze odvojena su od AGPL-a; manifest opisuje obim i pravila mirroring-a.
