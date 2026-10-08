# Šema i primeri interoperabilnosti

[English](../en/CONTRACT_VALIDATION.md)

**Razvojno ažuriranje (2026-10-08):** [Implementirani obim](IMPLEMENTATION_STATUS.md) beleži migracije 001–006, privatni katalog, objavu/medije, nepotpisan REST/JSON i Desktop uvoz. Kasniji trust/replika/recovery ugovori i predlozi pravne politike ispod ostaju predlozi. Ranije izjave faze 0/bootstrap-a su istorijske i ne opisuju sadašnji katalog.

Status: inženjerska osnova faze 0; šema i primeri su dokumentacioni artefakti, ne implementiran importer. Engleski identifikatori su kanonski. Pogledaj [ugovor snimka](SNAPSHOT_CONTRACT.md).

## Artefakti i slojevi validacije

Lokalna [šema nepotpisanog snimka](../contracts/schemas/public-snapshot-v1.schema.json) koristi [JSON Schema 2020-12](https://json-schema.org/specification). Razrešavati samo priložene lokalne reference; ne preuzimati šeme iz ulaznog dokumenta. URI šeme je identifikator, ne adresa pronalaženja izvora.

Redosled: ograničeno parsiranje bajtova/tokena → šema → semantičke provere grafa/konteksta → dozvole/mapiranje/pregled → atomski upis. Prolaz šeme ne znači bezbedan, odobren, svež ili autentičan dokument.

| Sloj | Obavezna odgovornost |
| --- | --- |
| Transport/parser | Granica 32 MiB pre alokacije, granica dekompresovanih bajtova ako HTTP content encoding bude podržan, dubina 8, string token do 32768 kodiranih bajtova, ukupno 100000 stavki nizova, UTF-8, odbijanje duplih ključeva, celovit dokument, bez pretvaranja tipova |
| Šema | Obavezna/nepoznata polja, tipovi, granice teksta/polja, UUID oblik, konačne integer granice, decimalni brojači sa maksimumom, odbijanje null neobaveznih polja, granice pojedinačnih nizova |
| Semantika | Kalendarski datum, URL authority/userinfo/fragment i HTTPS, UUID, jedinstveni ID-jevi/slug-ovi po tipu, reference, parovi članstva, dostupnost medija kroz reference, razdvojenost živih/tombstone zapisa, checkpoint redosled, obim politike |
| Kontekst | Odobrenje objave, politika maloletnika, očekivane privatne/javne revizije, lokalna mapiranja, zastareo/izmenjen pregled, potvrde uvoza, pravila poverenja, ranije prihvaćeno uklanjanje |
| Mediji/mreža | Kasnije izričito preuzimanje; SSRF/redirect/DNS zaštite, veličina/vreme/tip/digest/dekodiranje i prava |

2020-12 `format` može biti samo anotacija. Validator mora izričito proveravati `date-time`/`uri`, uz aplikacione provere tačnog UTC zapisa, validnog gregorijanskog datuma (bez prestupne sekunde u ovom formatu), host/port sintakse i odsustva userinfo-a ili fragmenta. Regex ne zamenjuje URI parser. UUID oblik dozvoljava RFC raspored verzija 1–8 sa RFC varijantom; generisanje koristi v4. Odbiti nil i neispravne ID-jeve. Tekst odbija C0/C1 kontrole i DEL, osim LF u biografiji; neprazan ima bar jedan znak koji nije whitespace. Čuvati validna imena kako su uneta; validacija ne spaja identitete.

Jedinstvenost stavki niza prepoznaje identične objekte, ne isti ID u različitim objektima. Aplikacija proverava jedinstvenost prema tipu/identitetu i slug-u, uključujući parove članstva. Brojače porediti kao tačne integer-e. Svaka živa revizija najviše je checkpoint; revizija tombstone-a najviše je njegov removal checkpoint; neprazan skup živih/tombstone zapisa traži pozitivan checkpoint. Privatna revizija ne ulazi u JSON. Odsutno neobavezno polje znači nedostupno; null nije podržan u javnom v1 obliku. Politika i nizovi dolaze iz jednog potvrđenog pogleda.

## Izmišljeni primeri

Primeri koriste izmišljena imena i `example.invalid`. URL-ovi politike i opis/digest slike su zamenske vrednosti, ne stvarne licence, bajtovi slike ili dokazi dozvola. Ne preuzimaju se. Nema stvarne baze ili fotografije. Artefakti ugovora nasleđuju projektnu AGPL-3.0-or-later licencu.

| Dokument | Očekivan rezultat pregleda |
| --- | --- |
| [Prazan](../contracts/examples/empty.json) | Strukturno/semantički prihvatljiv prazan podešen katalog, checkpoint 0 |
| [Objavljen](../contracts/examples/published.json) | Dva različita igrača istog prikaznog imena; klub, članstvo i izmišljen opis medija; drugi igrač namerno nema godište |
| [Povučen](../contracts/examples/withdrawn.json) | Minimalan tombstone igrača, bez živog profila ili ličnih polja |
| [Veliki brojač](../contracts/examples/large-counter.json) | Čuva 9007199254740993 tačno kao string |
| [Null neobavezno polje](../contracts/examples/reject-null.json) | Šema odbija null godište |
| [Privatno polje](../contracts/examples/reject-private-field.json) | Šema odbija nepoznatu/zabranjenu privatnu belešku |
| [Prekoračen brojač](../contracts/examples/reject-counter-overflow.json) | Šema odbija vrednost iznad signed 64-bit maksimuma |
| [Dupli ključ](../contracts/examples/reject-duplicate-key.json) | Parser odbija pre šeme; bez last-key-wins parsiranja |
| [Dupli ID](../contracts/examples/reject-duplicate-id.json) | Semantika odbija iako su objekti različiti |
| [Nedostajuća referenca](../contracts/examples/reject-dangling-reference.json) | Semantika odbija klub koji ne postoji |
| [Budući tombstone](../contracts/examples/reject-future-tombstone.json) | Semantika odbija uklanjanje posle checkpoint-a |

Ovo su očekivani ishodi, ne izvršeni implementacioni testovi. Prevelik/dubok ulaz, nevalidno kodiranje, prekinute migracije i dozvole navedeni su u [evidenciji spremnosti](PHASE_0_READINESS.md); traže implementacione testove umesto velikih zlonamernih fajlova u repozitorijumu. Komanda validacije navodi se tek posle autorizovane instalacije validatora i konfiguracije.
