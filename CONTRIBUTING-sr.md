# Doprinos projektu

[English](CONTRIBUTING.md)

Pročitaj [README](README-sr.md), [plan](docs/sr/PLAN.md), [arhitekturu](docs/sr/ARCHITECTURE.md) i [AGENTS.md](AGENTS.md). Još nema izvršive implementacije; predlozi dizajna i dokumentovani scenariji su dobrodošli.

Inženjerske odluke i ograničenja faze 0 su u [evidenciji spremnosti](docs/sr/PHASE_0_READINESS.md). Javna šema zahteva i [parser/semantičke provere](docs/sr/CONTRACT_VALIDATION.md); potpisani formati imaju [zasebne kasnije preglede](docs/sr/AUTHENTICATED_PUBLICATION.md).

## Predloži promenu

Opiši problem korisnika, trenutno/planirano ponašanje, konkretne primere i neuspešne slučajeve. Razdvoji potvrđene odluke od otvorenih pitanja. Veće arhitektonske promene zabeleži u docs/adr/ pre implementacije. Ne predstavljaj neimplementiranu funkciju kao funkcionalnu.

## Granice koje čuvamo

- Svaka organizacija može napraviti prazan nezavisan registar; podaci nisu deo dodatka.
- WordPress funkcije ostaju u adapterima. Identifikatori i JSON ugovori su prenosivi.
- Javni profili ne otkrivaju automatski sva sačuvana polja.
- Desktop preuzimanje je jednosmerno. Lokalne izmene i istorijski snimci turnira opstaju posle ažuriranja.
- Upisi sa replike su predlozi koje odobrava glavni registar; oporavak izričito prenosi autoritet.
- AGPL prava odnose se na softver; prava nad korisničkim bazama i medijima su posebna.

## Izmene i pregled

Čuvaj nepovezane izmene i fokusiraj patch. Ažuriraj CHANGELOG.md i pogođenu englesku/srpsku dokumentaciju. Koristi izmišljene/anonimizovane podatke; ne commit-uj baze saveza, fotografije bez dozvole, kredencijale niti ključeve za oporavak.

Kada je provera zatražena, obuhvati ispravne/neispravne uvoze, duplikate, migracije, prekide sinhronizacije, replay, neuspešne autorizacije, brisanje i sukobe oporavka. Navedi stvarne komande/rezultate i ograničenja. Alati i komande stižu sa implementacijom; trenutno ih nema.

Korisnički tekst podržava srpski i engleski. Koristi stabilne ključeve prevoda; ne prevodi sačuvane identifikatore/stanja. Imena ostaju onako kako su uneta.

## Bezbednosne prijave

Prati [SECURITY-sr.md](SECURITY-sr.md); ne objavljuj tajne niti lične podatke u javnim issue-ima.

## Licenca

Doprinosi moraju biti kompatibilni sa AGPL-3.0-or-later. Čuvaj oznake autora i navedi izvore/licence trećih lica. Trenutno ne zahtevamo prenos autorskih prava niti poseban ugovor sa contributor-om. Dozvola za doprinos koda nije dozvola za objavu tuđih ličnih podataka.
