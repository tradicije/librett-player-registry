# Skladište, transakcije i migracije

[English](../en/STORAGE_AND_MIGRATIONS.md)

**Razvojno ažuriranje (2026-10-08):** [Implementirani obim](IMPLEMENTATION_STATUS.md) beleži migracije 001–006, privatni katalog, objavu/medije, nepotpisan REST/JSON i Desktop uvoz. Kasniji trust/replika/recovery ugovori i predlozi pravne politike ispod ostaju predlozi. Ranije izjave faze 0/bootstrap-a su istorijske i ne opisuju sadašnji katalog.

Migracije 001–006 su implementirane: identitet; privatni nacrti/audit; članstva/alias-i; politika/projekcija/ledger/events/audit objave; opisi/veze/audit privatnih medija; pripremljeni uvoz/mapiranja/potvrde/lock. Nova šema ima 22 prazne InnoDB tabele. Definicije/checksum 001/002 su zadržani. Advisory lock, nastavak DDL-a, tačna strukturna provera i izjava operatora o backup-u štite dodatne izmene. Opšti privatni restore/down-migration runner ne postoji.

Sledeći odeljci čuvaju dizajn faze 0; konkretan kod i provereni obim imaju prvenstvo za razvojni katalog.

Kasniji trust/storage ugovori ostaju planirani; multisite i netransakcioni engine-i nisu obuhvaćeni.

## Vlasništvo i logički ključevi

UUID-i su nepromenljivi logički ključevi; SQL surrogate je interni podatak adaptera, bez izvoza. UUID čuvati kao ASCII `CHAR(36)` sa binarnim poređenjem, brojače kao nenegativan `BIGINT` u signed opsegu, tekst kao `utf8mb4` u ograničenim kolonama. Kolacija imena ne određuje identitet. Tabele koriste lokalni WordPress prefiks i `librett_registry_` namespace; konkretan DDL dolazi sa implementacijom.

| Vlasnik | Logičke tabele / ograničenja |
| --- | --- |
| RegistryIdentity | Singleton podešavanja/UUID-a registra, uloga, privatna verzija politike i životni ciklus; bez automatskog identiteta pri aktivaciji |
| Players | Nacrti polja, privatna edit revizija, aktivno/arhivirano čuvanje; jedinstven UUID; privatna odobrenja/dokazi |
| Clubs | Nacrti klubova, alias-i i trenutna članstva; jedinstven UUID kluba i par `(player_uuid, club_uuid)` |
| Media | UUID, privatne storage reference, digest/dimenzije, prava, staging/dostupnost/uklanjanje; attachment ID opcion privatni podatak |
| Publication | Odobrene projekcije igrača/klubova/medija i članstva, javne revizije/slug indeksi, tombstone-i, događaji i singleton checkpoint/politika |
| Aplikacioni koordinator | Poslovi/staging/potvrde uvoza i mapiranja kroz posebne portove; `(source_registry, type, source_uuid)` jedinstven u lokalnom registru; request UUID jedinstven |
| Privatni audit port | Ovlašćen akter/vreme/radnja/ishod i ograničene reference dokaza; bez javnih payload-a ili kredencijala |
| Adapter migracija | Verzija šeme, ID/checksum migracije, započeti/završeni/neuspešni koraci i vlasništvo lock-a |

Kasniji moduli dodaju svoje authority/replica/proposal tabele tek pri implementaciji. Modul ne čita tuđe tabele. Javna projekcija je neizmenjiva odobrena kopija, ne drugi promenljiv radni profil. Clubs drži članstva; Publication samo odobrene kopije. DDL određuje foreign-key/indeks ograničenja; repositories proveravaju reference. Cascade brisanje ne sme prećutno ukloniti audit/tombstone/poreklo uvoza.

## Transakcioni ugovor

Svi uključeni repositories i Publication portovi koriste isti transaction manager/session. Uspešan use case ima jedan commit; izuzetak daje rollback. HTTP, dekodiranje fajla i dug transfer medija ne drže SQL lock. Redosled lock-a: registar/checkpoint → UUID-i po tipu → zavisna članstva/mediji/projekcije → potvrde/audit. Deadlock je retryable neuspeh; ograničen retry koristi isti request ID i ponovo proverava revizije.

Nacrt proverava `edit_revision`; promena nacrta veza povećava edit reviziju obuhvaćenog igrača. Objava proverava privatnu/javnu reviziju, verziju politike i capabilities, pa zajedno upisuje projekcije, zavisna uklanjanja, događaje, checkpoint i audit. No-op nema javni događaj. Izvoz koristi read-only konzistentan InnoDB pogled i završava pre predaje bajtova odgovoru; ne meša checkpoint-e nizova. Po potrebi stream-uje u ograničen privatni staging, nikada javni delimičan fajl.

Singleton checkpoint serijalizuje dodelu. Svaka promenjena projekcija/događaj dobija uzastopan sequence; promena samo politike takođe ga povećava. Neuspešna dodela rollback-uje bez javnih praznina. Purge čuva nepersonalnu evidenciju identiteta/revizije i trenutni tombstone; mapiranja se rediguju prema pravilima čuvanja umesto zadržavanja ličnih payload-a. Bez imena u uklanjanju. Povlačenje kluba/medija atomski ažurira sve javne reference igrača.

Privatan staging uvoza ima job UUID, digest ulaza i normalizovanog sadržaja, režim/mapiranja i očekivane revizije/politiku. Potvrda ponovo proverava aktera i stanje. Neuspešan/otkazan/istekao posao ne primenjuje se i čuva samo ograničenu dijagnostiku do čišćenja. Potvrda se upisuje sa nacrtima; retry ne duplira efekte. Tombstone izvora je kandidat za pregled, ne automatsko brisanje lokalnog primary zapisa. Privatni backup/log nema offline recovery tajnu.

## Mediji i spoljni efekti

Proveriti sliku van transakcije u privatnom staging-u. Transakcija rezerviše medij; retryable finalizacija posle commit-a čini proveren sadržaj dostupnim. Objavljuje se samo finalizovan medij. Neuspeh ostavlja privatni zapis i razumljivu grešku. Čišćenje orphan-a je izričito i proverava reference. Pre odobrenja nema javnog original/derivative URL-a; povlačenje transakciono zatvara delivery pristup, a trajni privatni cleanup red briše bajtove. Neuspešan unlink ne vraća pristup. Adapter uzima u obzir hosting/CDN cache i ne obećava brisanje preuzetih kopija.

## Bezbednost migracija

Aktivacija proverava runtime/ekstenzije/engine, uzima ekskluzivan migration lock i pravi praznu šemu/setup. Migracija ima nepromenljiv ID/checksum i ciljnu verziju. SQL DDL može implicitno commit-ovati: ne obećavati rollback promene šeme. Preferirati aditivne nastavljive expand/backfill/verify/contract korake sa evidencijom. Završena verzija menja se tek kada prođu potrebne provere podataka/ograničenja.

Pre upgrade-a zahtevati proverenu kopiju baze, zaštićenih medija i konfiguracije (bez recovery tajne) sa opisom restore-a. Tokom migracije zabraniti izmene; kompatibilnu javnu kopiju služiti samo kada je dokazano bezbedno, inače maintenance. Bez delimičnog staging prikaza. Posle prekida nastaviti zabeležen korak, proveriti objekte/checksum i odbiti dvosmisleno stanje. Neuspeh ostavlja maintenance uz dijagnostiku bez PII/tajni.

Nema automatskih destruktivnih down migracija. Vraćanje starog privatnog backup-a je izričit offline maintenance tok za odgovarajući kod/šemu/medije. Kod nepotpisanog single-site registra nastavak objave sa starijeg checkpoint-a traži nov identitet registra, osim kada postoji kasniji autentifikovan generation-transfer ugovor; ne koristiti stare javne ID-jeve/brojače za konfliktno stanje. Javni snimak ne vraća nacrte, odobrenja ili audit.

Deaktivacija čuva podatke i capabilities. Podrazumevan uninstall čuva tabele/medije; brisanje traži izričit ovlašćen opt-in, pregled backup-a/čuvanja i poseban purge. Ponovna aktivacija koristi postojeći registar. Update menja kod, bez demo podataka.

## Obavezni implementacioni scenariji

Dva pravljenja šeme, prazna aktivacija, neuspešan preflight, paralelan setup, zastarele privatne/javne revizije, paralelan checkpoint, rollback zavisnog povlačenja, prekinut uvoz pre/posle commit-a, neuspeh finalizacije/unlink-a, prekid svakog koraka migracije, drugačiji checksum, pun disk pri backup-u i vraćanje usklađene šeme/medija. Testovi ovog dizajna nisu pokrenuti.
