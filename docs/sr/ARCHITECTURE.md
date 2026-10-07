# Arhitektura

[English](../en/ARCHITECTURE.md)

Vlasništvo interfejsa i graf bez ciklusa određuju [ugovori modula](MODULE_CONTRACTS.md), a transakcije/migracije [skladište](STORAGE_AND_MIGRATIONS.md). Razrađuju konceptualnu listu ispod.

## Bez monolitnog dizajna

Jedan dodatak za instalaciju ne znači jednu ogromnu klasu niti nerazdvojivu implementaciju. Moduli imaju izričite javne interfejse, jasne odgovornosti i zavisnosti bez ciklusa. Tanak composition root povezuje module; WordPress hook-ovi pozivaju adaptere, a oni aplikacione use case-ove.

Početni konceptualni moduli:

| Modul | Odgovornost |
| --- | --- |
| RegistryIdentity | Identitet, uloge i prihvaćeni metapodaci autoriteta |
| Players | Profili, validacija, revizije i izbor javnih podataka |
| Clubs | Identiteti klubova, nazivi/alias-i i članstva |
| Publication | Javna projekcija, prenosivi snimci i potvrđeni tok promena |
| Replication | Proveren pull, cursor-i, staging i atomska primena |
| Proposals | Predlozi replika, moderacija i ishodi |
| Recovery | Promena poverenja i prenos odobren van servera |
| Media | Prenosivi opisi medija i ograničen transport |

U svakom modulu razdvojiti domen, aplikacione portove/use case-ove i infrastrukturne adaptere. Mali zajednički value tipovi su prihvatljivi; shared folder ne sme biti gomila nepovezanih servisa. Moduli ne pristupaju internim tabelama/klasama drugih modula.

## Smer zavisnosti

WordPress UI/REST/storage/media adapteri → aplikacioni portovi/use case-ovi → domen. Dependency injection se obavlja u composition root-u. Domen ne zna za $wpdb, WP_Post, WP_User, request objekte, filesystem ili HTTP transport.

Transakcije, sat, UUID generisanje, provera potpisa, dozvole i mediji su portovi. Autorizacija je izričita na granicama use case-a; adapter daje autentifikovan kontekst aktera. Validacija postoji i bez WordPress formi.

## Skladište i hosting

WordPress custom tabele koriste lokalni prefiks; on nije deo logičkog identiteta ili izvoza. Persistence mapira domenske vrednosti u redove. Engine-i nisu automatski zamenljivi: budući server traži odgovarajući repository/transaction/media adapter i migracije.

Početno jedan registar po instalaciji. WordPress multisite izolacija i više registara po instalaciji su otvorene deployment odluke, ne obećana podrška. Aktivacija pravi samo šemu i stanje podešavanja. Deaktivacija čuva podatke. Destruktivan uninstall zahteva izričitu politiku i autorizaciju.

Javni profili koriste rute/templates nad repositories; CPT-ovi nisu source of truth. WordPress attachment ID je interna referenca adaptera, ne prenosiv javni ID.

## Jezici i standardi

Cilj razvoja je savremen podržan PHP (8.5 na datum dizajna), uz proveru deklarisane kompatibilnosti pre implementacije. Koristiti strict types, tipizirana polja/parametre/rezultate, enum-e za stanja, validirane value objekte, neizmenjive DTO-e gde je primereno, Composer PSR-4 i PHP-FIG PER Coding Style 3.1. Funkcije biramo radi ispravnosti, ne samo zato što su nove.

WordPress adapteri poštuju njegove bezbednosne/API konvencije. Odvojiti lint pravila ako se WordPress formatiranje i PER stil jezgra razlikuju; ne uvoditi platformske wrapper-e u domen. Zaključati održavane alate nakon izbora. Statička analiza i provera granica modula pripadaju planiranom CI-ju.

Bogat frontend, ako bude potreban, koristi strict TypeScript i odvojene view/state/API module. Framework i bundler još nisu izabrani. SPA ili distribuirani mikroservisi nisu potrebni samo da bismo imali modularnost.

## Plan rasporeda direktorijuma

Moduli i platformski adapteri dobijaju stvarne source direktorijume kada počne kod. Sada je repozitorijum samo dokumentacija. Ugovor su vlasništvo modula i smer zavisnosti, ne prazna prerana skeleton struktura.

## Reference

[PHP podrška](https://www.php.net/supported-versions.php), [PSR-4](https://www.php-fig.org/psr/psr-4/), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [WordPress REST rute](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/).
