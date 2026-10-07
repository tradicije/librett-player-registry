# Autentifikovana objava i prenos autoriteta — pregled dizajna

[English](../en/AUTHENTICATED_PUBLICATION.md)

Status: osnova threat model-a faze 0 za kasnije etape, ne odobren wire format, implementirana kriptografija ili audit. Nepotpisana v1 šema ostaje ista i nema pouzdan autoritet. Autentifikovan envelope traži poseban format/šemu i primere pre faze 3; prenos autoriteta ostaje faza 5.

## Kandidat ugovora bajtova

Održavan Ed25519 kroz injektovan sodium adapter i [RFC 8785 JCS](https://www.rfc-editor.org/rfc/rfc8785) kroz zasebno pregledan održavan canonicalizer. Bez sopstvenog algoritma ili kanonizatora. Potpisan JSON je I-JSON, jedinstveni ključevi, validan Unicode i safe-range integer-i; revision/generation/sequence ostaju decimalni stringovi. Unicode ostaje tačan, bez normalizacije. Redosled nizova određuje potpisan payload; verifier ga ne sortira ili popravlja.

Kandidat envelope-a: tačno `format`, `envelope_version`, `algorithm` (`Ed25519`), `key_id`, `payload`, `signature`. Potpis je unpadded base64url od 64 bajta; key ID je mali SHA-256 hex sirovog javnog ključa od 32 bajta. Provera koristi JCS bajtove celog envelope-a bez samo `signature`, vezujući format/verziju/algoritam/key ID/payload. Odbiti nepoznata polja/algoritme; ključ dolazi iz zapamćenog autoriteta, ne iz samog envelope-a. Parser/šema/granice prethode kriptografiji, a semantika/kontekst slede uspešnu proveru. Nazivi su kandidati buduće normativne šeme, ne dodaci nepotpisanom v1.

Payload vezuje UUID registra, digest prihvaćene tvrdnje, generaciju, tip objekta, checkpoint/cursor i pun snimak ili uređenu stranicu promena. Stranica vezuje lower-exclusive/upper-inclusive sequence, fiksan gornji checkpoint sesije, digest prethodne stranice i sadržaj. Promena ima trajan UUID, tip/UUID, očekivanu/novu javnu reviziju i ceo upsert/minimalan tombstone. Policy-only događaj ima poseban tip bez izmišljene revizije zapisa. Bez privatnih nacrta/dokaza. Granice nisu slabije od nepotpisanog transporta; tvrdnje imaju 64 KiB po tvrdnji i dubinu lanca 64; duži lanac traži pregledan checkpoint bundle, ne skraćivanje provere.

## Početno poverenje i lanac

Pouzdan connection bundle sadrži UUID registra, recovery javni ključ/otisak, početnu potpisanu tvrdnju i endpoint-e. Otisak proveriti van izvora ili izričito označiti prvo poverenje neproverenim. HTTPS ne potvrđuje organizacioni autoritet. Manifest ne menja postojeći root. Početna tvrdnja ima generaciju 1, bez roditelja, operativni javni ključ, endpoint i početni checkpoint, potpis offline recovery autoriteta.

Sledeća tvrdnja vezuje UUID registra, digest roditelja, tačno parent generation +1, nov operativni ključ, primary endpoint, vraćen checkpoint/digest i deklaraciju usklađivanja. Digest označava pune kanonske autentifikovane envelope bajtove. Pre promene proveriti ceo kontinuitet od prihvaćenog root-a/tvrdnje. Veći potpisan generation bez prihvaćenog parent puta nije dovoljan. Endpoint preuzimanje ima iste mrežne granice; hostname nije identitet.

Rotacija operativnog ključa ide recovery-potpisanom next-generation tvrdnjom; server ključ ne daje nov root. Rotacija root-a traži prelaz koji vezuje stari/novi javni root i roditelja, potpisan obema offline root tajnama. Dugo offline klijent proverava sačuvan bundle kontinuiteta. Prvi dizajn nema alternativne recovery autoritete. Gubitak jedine tajne onemogućava autentifikovan nastavak istog identiteta; nezavisan fork dobija nov UUID.

## Replay, sukobi i vraćeni podaci

Root/tvrdnja/generacija, checkpoint, digest stranice i znanje o uklanjanju upisuju se u istoj transakciji sa projekcijama/cursor-om. Odbiti stare generacije, rollback, praznine sequence-a, isti ID/drugi bajtovi i konfliktne tvrdnje iste generacije. Isti bajtovi su idempotentni. Validan potomak ne skriva konfliktni sibling: zabeležiti sukob i zaustaviti sync do izričitog rešavanja; vreme, endpoint ili neproveren generation ne biraju pobednika.

Nov klijent bez pouzdanog skorijeg checkpoint-a ne dokazuje svežinu potpisom; prikazati ograničenje i prihvaćen izvor/checkpoint. Neuspešno preuzimanje čuva poslednju proverenu projekciju sa stale oznakom. Bez automatskog pronalaženja novog hosta ili izbora lidera.

Oporavak vraća podatke odvojeno od autoriteta. Tvrdnja navodi izvorni checkpoint/digest i moguć gubitak; operator usklađuje sa prihvaćenim stanjem svakog klijenta. Zastarela kopija traži full authenticated resync i izričit loss report; bez prećutnog smanjenja revizija/znanja o uklanjanju ili vraćanja povučenih profila. Čuvati uniju poznatih suppression uklanjanja dok ih novija izričito odobrena objava ne uskladi. Ranije živ a sada nedostupan podatak označiti nedostupnim tokom resync-a, bez izmišljanja brisanja ili menjanja Desktop istorije. Revizije nove generacije važe unutar te generacije; stari event ID ne koristi se za drugi sadržaj. Divergentno stanje klijenta može tražiti operatora pre promene čak i uz validnu tvrdnju.

Stari host ne napreduje prihvaćen klijent posle prenosa. Odvojen klijent koji nije dobio tvrdnju može videti stari autoritet; prikazati prihvaćenu tvrdnju i ručan bundle uvoz. Javni mirror ne vraća izgubljen audit/odobrenja/originalne medije. Recovery privatni ključ nikada nije na hostu, replici, javnom snimku, log-u, repozitorijumu ili običnom backup-u.

## Predlozi i remote ulaz

Predloge šalju upareni registry-site akteri sa opozivim kredencijalom ograničenim na registar, odvojeno od čitanja. Dozvola je submit/query-own, ne objava. Proveriti request UUID/payload, granice/rate limit i početnu reviziju; primary moderacija koristi normalnu transakciju objave. Privatni dokazi ostaju privatni. Desktop nema proposal kredencijal ni write pozive.

Pre remote veze odbiti private/loopback/link-local/reserved odredišta, nepodržane scheme/port-ove i userinfo; proveriti DNS/stvarnu konekciju, TLS, svaki redirect, timeout, content type, kompresovanu/dekompresovanu veličinu i dekodiranje slike. Bez automatskog preuzimanja policy URL-a. Potpisan sadržaj ostaje nepoverljiv parser/rendering ulaz. Tačne HTTP granice i potpisani interoperabilni/bezbednosni primeri su uslovi odgovarajućih implementacionih etapa.

## Obavezni scenariji pregleda

Promenjeni Unicode/brojevi/nizovi, nepoznati algoritmi, drugi key ID/kontekst, izmenjene granice stranice, dupli/promenen redosled/praznina feed-a, replay tvrdnje/stranice, zamenjen manifest root, kompromitovan server ključ, kopiran/ukraden recovery root, sibling tvrdnje, validan potomak pogrešne grane, povratak starog hosta, zastareo restore sa novijim klijentskim tombstone-ima, izgubljen root, offline rotacija i SSRF/redirect/decompression napadi. Ovo je pregled dizajna i spisak zahteva; audit/crypto testovi nisu izvršeni.
