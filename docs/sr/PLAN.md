# Plan razvoja

[English](../en/PLAN.md)

Status: predložene implementacione etape; aplikacioni kod još ne postoji.

## Obim

Jedan modularan dodatak podržava prazan nezavisan registar ili repliku, javne profile igrača/klubova, administraciju, prenosivi JSON i jednosmerno preuzimanje u LibreTT Desktop. Kasnije etape dodaju proverene replike, moderirane predloge i oporavak sa odobrenjem van starog servera. Paket ne sadrži bazu saveza.

## Trenutni rad

Dokumentaciona priprema faze 0 je završena; pogledaj [evidenciju spremnosti](PHASE_0_READINESS.md) i [ADR 0004](../adr/0004-phase-0-engineering-baseline.md). Politika objave, nepotpisana strukturna JSON šema/primeri, skladište/migracije, interfejsi modula i pregledani runtime/alati čine inženjersku osnovu. Runtime/zavisnosti/izvršenje primera pripadaju implementaciji; odobrenje stvarnih podataka i pregled bezbednosti autentifikovanih formata ostaju deployment/feature uslovi. Nema aplikacionog koda, zavisnosti ili testova. Sledeći je bootstrap faze 1 iz [ugovora modula](MODULE_CONTRACTS.md), kada implementacija bude zatražena.

## Faza 0 — ugovori i tehnička osnova

- Potvrditi polja za objavu/privatnost, prava nad bazom/fotografijama i da li je prvi katalog samo javan.
- Usvojiti granice modula, zavisnosti bez ciklusa i izričite portove; bez ogromnog plugin fajla, univerzalnog servisa ili kontrolera.
- Razvojni cilj: PHP 8.5, najviša podržana grana na PHP sajtu 7. oktobra 2026. Proveriti WordPress/bazu i minimalne deployment verzije pre tvrdnje o podršci. Ponovo proveriti verzije na početku implementacije.
- Composer PSR-4, strict types, tipizirani DTO/value objekti i aktuelni PHP-FIG PER Coding Style (3.1 na datum pregleda). Podršku starim runtime-ovima uvodimo samo namernom odlukom.
- Izabrati statičku analizu, formatter, testove i audit zavisnosti; navesti verzije/komande kada se alati instaliraju. Sada ništa nije instalirano.
- Pregled šeme/JSON verzionisanja, UUID identiteta, transakcija, sukoba, ograničenog uvoza i oporavka migracija.
- Pregled početnog poverenja i potpisanih poruka pre tvrdnje da su replike autentifikovane.

Izlaz: pregledani ugovori, runtime matrica i ADR-i; planirane funkcije nisu predstavljene kao implementirane.

## Faza 1 — registar na jednom sajtu

- Aktivacija pravi šemu, capabilities i stanje podešavanja, bez demo igrača ili automatskog identiteta registra.
- Podešavanje pravi novi registar i trajne ID-jeve. Razdvojiti javne i administrativne podatke.
- CRUD igrača/klubova, pretraga, validacija, pregled duplikata, arhiviranje/brisanje i audit.
- WordPress admin i javne rute/templates, bez CPT vlasništva nad zapisima registra.
- Adapter za medije sa proverom upload-a, autorstvom/dozvolama i prenosivim referencama.
- Javna projekcija i celovit prenosiv JSON uvoz/izvoz, proveren pre primene.
- Deaktivacija čuva podatke; izričit uninstall/purge ima pravila čuvanja i potvrdu.

Izlaz: prazna instalacija, izmišljeni profili, JSON round trip, migracija/restart, dozvole, javna projekcija i brisanje provereni. Replike još nisu uključene.

## Faza 2 — desktop preuzimanje

- Podesiv HTTPS izvor ili JSON fajl; domen nije identitet registra.
- Čuvati mapiranje registra/igrača, poslednju preuzetu projekciju i odluke o lokalnim izmenama polja.
- Pregled novih zapisa/promena/sukoba pre potvrde; bez slanja registru.
- Čuvati ručne lokalne igrače, veze klubova, istorijske snimke turnira i offline rad.
- Neobavezno godište/fotografije sa sajta ne smeju zaobići desktop validaciju.

Izlaz: prvi/ponovljeni uvoz, promenjen domen, lokalne izmene, zastarela kopija i uklonjen online profil pravilno obrađeni.

## Faza 3 — proverene replike

- Pouzdan fajl/manifest povezivanja, ključevi objave, potpisani snimci/tokovi i ograničenja migracija.
- Replay/rollback zaštita, revizije po generaciji autoriteta, atomski cursor-i i full resync.
- Javni profili i desktop API iz proverene lokalne kopije, uz izvor i vreme ažuriranja.
- Ponovljeni zahtevi, prekid stranice, brisanja, nestali mediji, rotacije i sukobi autoriteta.
- Direktne izmene zajedničkih zapisa odbijene server-side; bez lažne garancije protiv vlasnika hostinga koji menja sopstvene fajlove.

Izlaz: neautentifikovane/neispravne/zastarele objave ne menjaju prihvaćeno stanje; ponovljena sinhronizacija konvergira. Pregled poverenja obavezan pre javnog puštanja.

## Faza 4 — predlozi

- Uparivanje replika i dodela/opoziv kredencijala za predloge ograničene na registar.
- Predlog dodavanja/izmene/uklanjanja, početna revizija, idempotence, rate limit i moderacija.
- Samo prihvatanje na glavnom registru pravi zajedničku reviziju. Privatni dokazi se ne repliciraju javno.

Izlaz: neovlašćeni predlozi odbijeni, zastareli pregledani, retry pravi jedan predlog/promenu, pošiljalac vidi ishod.

## Faza 5 — prenos autoriteta i oporavak

- Autoritet za oporavak van servera odobrava novi operativni ključ i adresu glavnog sajta.
- Nezavisno vraćanje podataka i potpisan prenos; stari server nije potreban.
- Čuvati prihvaćenu generaciju; odbijati rollback i sukobljene tvrdnje iste generacije.
- Rotacija, kompromitovan operativni ključ, izgubljen recovery ključ i ručno obaveštavanje replika.
- Oporavak zastarele kopije jasno navodi checkpoint; ne koristi ponovo konfliktne revizije.

Izlaz: gubitak glavnog sajta, njegov povratak, odvojene replike, replay i paralelni pokušaji oporavka daju jasne ispravne ishode.

## Faza 6 — priprema izdanja

Dvojezični admin/javni UI, pristupačni responzivni profili, performanse, kompatibilan izvoz, poreklo izdanja prema načinu distribucije, privatni kanal za bezbednost i uputstva. ZIP dodatka objavljuje se nakon stvarnih runtime provera. Folder dokumentacije nije installer.

## Kasnije

Istovremeno izmenjivi glavni sajtovi, automatski izbor lidera, spajanje identiteta iz različitih registara, slanje desktop izmena, rezultati turnira, sudijski telefon, računanje rangiranja i korisnički nalozi igrača. Pravila pojedinih saveza i urednički tokovi traže poseban dogovor.

## Izvori i otvorene odluke

[PHP podrška](https://www.php.net/supported-versions.php), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [PSR-4](https://www.php-fig.org/psr/psr-4/). Struktura/granice nepotpisanog snimka v1, prvi javni katalog i inženjerski ciljevi dati su osnovom faze 0. Konkretne REST rute, instalirane zavisnosti/parser, proverena runtime podrška, privatno pakovanje medija i normativni signed/key-ceremony formati pripadaju implementaciji ili kasnijim etapama; pogledaj uslove spremnosti.
