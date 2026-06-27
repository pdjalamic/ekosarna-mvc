# HANDOFF — tekuće stanje rada (Ekošarna panel)

> Jedan fajl za „dokle smo stigli". Sekcije po temama. Najnovije gore.
> Kad nastavljamo rad, OVO se otvara prvo.

## Sadržaj
> ▶ **ZADNJE:** Z. (Zadaci: više članova + pozivanje + alarm + fajlovi) — kod gotov, deployed; 3 nova SQL + cron. Vidi sekciju Z (najnoviji Update: mobilni raspored kartice).

Z. [Zadaci — više članova + pozivanje + alarm + fajlovi](#z-zadaci--vise-clanova--pozivanje--alarm--fajlovi)  · *2026-06-27* · ✅ kod gotov/deployed; 3 nova SQL + cron · *Update 2026-06-27:* mobilni raspored kartice
M. [Magacin + Evidencija — utrošak, stanje, brisanje ulaza](#m-magacin--evidencija--utrosak-stanje-brisanje-ulaza)  · *2026-06-26* · ✅ radi/deployed; komitovano (`6c6d32d`/`f3e9a7c`/`671a457`)
V. [Danas — obavezno radno vreme (od–do) pri unosu](#v-danas--obavezno-radno-vreme-oddo-pri-unosu)  · *2026-06-21* · ✅ radi/deployed (uklj. serversku branu); komitovano (`0050d3e`)
E. [Evidencija — „Dnevni pregled" (sumar po danu / timu / gradilištu)](#e-evidencija--dnevni-pregled-sumar-po-danu--timu--gradilištu)  · *2026-06-21* · ✅ radi/deployed (dizajn + boje po maketi); komitovano (`0050d3e`)
P. [Poruke — redizajn u chat (WhatsApp/Viber stil)](#p-poruke--redizajn-u-chat-whatsappviber-stil)  · *2026-06-21* · ✅ radi, komitovano (`3cd6738`), na produkciji
0000000. [Raspored — nacrt samo kreatoru (#1) + zabrana unazad (#2)](#0000000-raspored--nacrt-samo-kreatoru-1--zabrana-unazad-2)  · *2026-06-21* · ✅ radi, komitovano (`bdfdd9a`), na produkciji
000000. [Raspored — poruke (chat) na zadatku: mobilni + notifikacije svuda](#000000-raspored--poruke-chat-na-zadatku-mobilni--notifikacije-svuda)  · *2026-06-21* · ✅ radi (web/Android/iOS), komitovano (`b2aa8d3`), na produkciji
00000. [Raspored — odgovoran za materijal (vođe + notifikacije + Danas)](#00000-raspored--odgovoran-za-materijal-vodje--notifikacije--danas)  · *2026-06-21* · ✅ radi, komitovano (`44ae11e`), na produkciji
0000. [Raspored — zakazano (scheduled) + logo badge](#0000-raspored--zakazano-scheduled--logo-badge)  · *2026-06-21* · ✅ radi (cron postavljen), komitovano (`44ae11e`), na produkciji
000. [Raspored — Android push za dodelu (klik otvara Danas)](#000-raspored--android-push-za-dodelu-klik-otvara-danas)  · *2026-06-20* · ✅ radi, komitovano (`a498338`), na produkciji
00. [Magacin — izmena bez reload-a (osveži samo red)](#00-magacin--izmena-bez-reload-a-osvezi-samo-red)  · *2026-06-20* · ✅ test prošao, komitovano, na produkciji
0. [Raspored — nacrt (draft) pre objave](#0-raspored--nacrt-draft-pre-objave)  · *2026-06-20* · ✅ radi, na produkciji (SQL pokrenut); prošireno u sekciji 0000000
1. [Magacin — „Namenjeno za" (gradilište) + pregled loga](#1-magacin--namenjeno-za-gradiliste--pregled-loga)  · *2026-06-20* · ✅ test prošao na produkciji; komitovano
2. [Magacin — mobilni: otvaranje stavki + dokument u modalu](#2-magacin--mobilni-otvaranje-stavki--dokument-u-modalu)  · *2026-06-20* · ✅ radi (web + mob), komitovano
3. [Zadaci — notifikacije + chat + klik notifikacije + kontrola roka](#3-zadaci--notifikacije--chat-komentari)  · *2026-06-20* · ✅ radi (klik notifikacije + rok potvrđeni na uređaju)
4. [Push notifikacije — stanje](#4-push-notifikacije--stanje)  · *2026-06-16* · ✅ radi na produkciji, ostalo poliranje

---

## Z. Zadaci — više članova + pozivanje + alarm + fajlovi

**Datum:** 2026-06-27 · **Status:** ✅ kod gotov, `php -l` čist, deployed (testirano kroz faze). **3 nova SQL + 1 cron.**

Veliko proširenje internih Zadataka u 4 faze. Ranije: zadatak je imao **jednog** dodeljenog (`dodeljeno_id`) i **jednog** prihvatioca (`prihvaceno_id`). Sada: **više članova, svako prihvata zasebno.**

### Model podataka (3 nove tabele)
- `zadaci_clanovi (zadatak_id, korisnik_id, prihvatio, prihvatio_at, pozvao_id, dodat_at)` — članovi (M:N). Seed iz starih `dodeljeno_id`/`prihvaceno_id` ide kroz **`zadaci_clanovi.sql`** (bitan — taj seed NIJE u `migrate()`; same tabele `migrate()` pravi).
- `zadaci_alarmi (zadatak_id, korisnik_id [NULL=ceo tim], postavio_id, send_at, poruka, poslato)` — podsetnici.
- `zadaci_fajlovi (zadatak_id, naziv, putanja, tip, velicina, dodao_id, dodat_at)` — fajlovi.
- Stare kolone `dodeljeno_id`/`prihvaceno_id` **ostaju** (kompatibilnost; prvi dodeljeni/prvi prihvatilac se i dalje pune).

### Faza 1 — više članova + NOVA vidljivost
- `zadatak_add` prima `dodeljeno_id[]` → svi u `zadaci_clanovi`, notifikacija svima.
- `zadatak_prihvati` je **po članu** (svako prihvata svoje); status → `u_toku` na prvo prihvatanje; kreator notifikovan.
- **Vidljivost (PROMENA ponašanja):** Direktor vidi sve; ostali vide **samo** zadatke kojih su član ili koje su kreirali. **Neprihvaćen zadatak vidi samo onaj kome je namenjen** (ranije su svi videli sve neprihvaćene). Filter „Prihvatio" i redosled računaju se iz članova.
- View: **checkbox multi-select** pri kreiranju; čipovi članova (✓ prihvatio / ⏳ čeka); „Prihvati" se vidi članu koji nije prihvatio.

### Faza 2 — „➕ Pozovi"
- `zadatak_pozovi` (poziva član/kreator/Direktor) → dodaje nove članove + **push/Telegram „Pozvan si na zadatak"**, deep-link `?page=zadaci&openz=<id>`. Modal izostavlja već dodate. **Komentari** idu svim članovima + kreatoru.

### Faza 3 — Alarm/podsetnik (push/Telegram — NIJE „budilnik")
- **Lični** (svako ko vidi zadatak) + **„ceo tim"** (samo kreator ili Direktor/Inženjer). Modal: `datetime-local` + brzi dugmići (za 1h / sutra 08:00 / na dan roka) + napomena; lista zakazanih sa 🗑.
- Na kartici (meta-red, vidljivo i kad je skupljena) **čip „⏰ DD.MM. HH:MM"** za svaki nadolazeći vidljivi podsetnik (timski + lični tvoji); „· tim" za timski; pun datum/napomena u tooltipu.
- `zadaci_cron.php` (cPanel cron `*/5`) zove **`ZadaciController::posaljiAlarme()`**; preskače završene/obrisane; URL **host-nezavisan** `/mvc/...` (kao raspored). `htaccess` izuzetak `^zadaci_cron\.php$`.
- ⚠️ **Web push ne može glasan alarm-zvuk** (service worker ne sme audio; `Notification.sound` ukinut; zvuk/jačinu bira korisnik u Android kanalu). Pravi „budilnik" = samo native app. **Odlučeno: idemo sa push/Telegram.** Vidi [[push-notifikacije-stanje]].

### Faza 4 — Fajlovi (max **25 MB**, višestruki upload)
- **Kače** svi na zadatku (član/kreator/Direktor). **Skidaju samo oni koji su PRIHVATILI** (+ kreator, Direktor, i onaj ko je okačio).
- Skladište `uploads/zadaci/` sa **nasumičnim** imenom; folder + `.htaccess` (Deny) prave se **automatski** pri prvom uploadu → direktan URL = 403; pristup samo kroz **PHP kapiju** `?page=zadaci&dl=<id>` (`streamFajl`). `?download=1` = attachment, inače inline (pregled kroz deljeni `openModal`, vidi [[deljeni-fajl-modal]]).
- `zadatak_fajl_add` prima `fajlovi[]` (više odjednom; validacija veličine/tipa po fajlu); **notifikacija jednom** prihvatiocima + kreatoru („📎 Novi fajl na zadatku…"). `zadatak_fajl_delete` (uploader/kreator/Direktor).
- UI: dugme **„📎 Fajlovi (N)"** u redu akcija (pored ➕ Pozovi) = **indikator + otvara** sekciju (proširi + skroluj). **Dodavanje/brisanje rade DOM-update BEZ reload-a** (zadatak se ne skuplja).

### Izmenjeni / novi fajlovi
| Fajl | |
|---|---|
| `zadaci_clanovi.sql` / `zadaci_alarmi.sql` / `zadaci_fajlovi.sql` | **NOVI** (run once; bitan je clanovi seed) |
| `zadaci_cron.php` | **NOV** (+ cPanel cron `*/5`) |
| `app/Models/InterniZadatak.php` | nove tabele u `migrate()` + metode (članovi / alarmi / fajlovi) |
| `app/Controllers/ZadaciController.php` | vidljivost, AJAX akcije (add/prihvati/pozovi/alarm/fajl), kapija `?dl`, `posaljiAlarme()` |
| `app/Views/zadaci/index.php` | multi-select, čipovi članova, dugmad Pozovi/⏰/📎, modali (pozovi/alarm), sekcija fajlova |
| `htaccess` | izuzetak `zadaci_cron.php` |

### Deploy (redosled)
1. SQL (run once): `zadaci_clanovi.sql` (seed!), `zadaci_alarmi.sql`, `zadaci_fajlovi.sql`.
2. Upload: model + controller + view + `zadaci_cron.php` + `.htaccess`.
3. cPanel cron `*/5` za `zadaci_cron.php`.
4. Produkcija: `upload_max_filesize`/`post_max_size` **≥ 25M** (za velike fajlove). `uploads/zadaci/` mora biti upisiv.

#### Update 2026-06-27 — popravka mobilnog rasporeda kartice (regresija)
Dodavanjem dugmadi (➕ Pozovi / 📎 Fajlovi / ⏰) header kartice je na telefonu pucao: akcije su (inline `flex`, `nowrap`) otimale širinu pa se naslov cedio na ~jednu reč po redu. Popravka **samo CSS, 1 fajl** `app/Views/zadaci/index.php`: header dobio klasu `z2-card-head`, blok akcija `z2-card-actions`; dodat `@media (max-width:720px)` koji dozvoli prelom (`flex-wrap`) i spušta akcije u **ceo red ispod naslova** (`width:100%`, levo poravnato). Desktop (>720px) netaknut. *(Probali smo i `order:-1` da dugmad budu IZNAD naslova — naručilac odbio kao neprirodno; vraćeno na dugmad ispod.)* **Deploy:** upload tog 1 fajla, bez SQL-a; hard-refresh na telefonu (inline `<style>`).

---

## M. Magacin + Evidencija — utrošak, stanje, brisanje ulaza

**Datum:** 2026-06-26 · **Status:** ✅ sve testirano, na produkciji, komitovano. **Potrebnan 1 SQL na produkciji** (vidi M1).

Tri odvojene izmene u toku jedne sesije. Pozadina: stanje magacina = `SUM(magacin_promet)` (knjiga prometa); **nema trigera ni FK** ka primke/stavke → sinhronizacija stanja se radi **ručno u kodu**.

### M1. Evidencija — ručni unos utroška materijala nije mogao da se sačuva (`6c6d32d`)
**Simptom:** klik „Sačuvaj" u „Dodaj utrošak" → ništa (modal ostaje), a red se ipak upisivao (nema transakcije). **Tri uzroka** (lanac):
1. `EvidencijaController::loguj()` 4. param je bio `array $staro`, a za akciju `'unos'` se šalje `null` → **TypeError 500**. Lek: `?array $staro`.
2. `evidencija_log.akcija` je **ENUM('izmena','brisanje')** a MySQL je u `STRICT_TRANS_TABLES` → upis `'unos'` puca (1265). Lek: **`ALTER TABLE evidencija_log MODIFY akcija ENUM('izmena','brisanje','unos') NOT NULL;`** (mora i na produkciji; nije u migrate()).
3. JS `sacuvajDodajMat()` bez `.catch()` → 500 prolazio „tiho". Lek: dodat `.catch` + provera HTTP statusa + disable dugmeta.

| Fajl | Izmena |
|---|---|
| `app/Controllers/EvidencijaController.php` | `loguj(..., ?array $staro, ...)`. |
| `app/Views/evidencija/index.php` | `.catch` + HTTP/JSON provera u `sacuvajDodajMat`. |

**Deploy:** ta 2 fajla **+ obavezni ALTER** (bez ALTER-a se 500 ponavlja). Napomena: rani „tihi" pokušaji su ostavili fantomske duplikate u `raspored_materijal`+`magacin_promet` (čiste se kroz Evidenciju 🗑 ili SQL po grupama).

### M2. Magacin „Stanje zaliha" — sakriti redove sa negativnim saldom (`f3e9a7c`)
Utrošak/prenos mogu ostaviti negativan saldo grupe (npr. `-100,00`). Traženo: ne prikazivati taj red, ali ga **ostaviti u bazi za računanje**. `getStanjePoLokaciji()`: `HAVING stanje <> 0` → **`HAVING stanje > 0`**. Samo prikaz; svi `SUM` proračuni i provere (zaseban kod) netaknuti. Važi za **sve** lokacije. **Deploy:** 1 fajl `MagacinController.php`, bez SQL-a.

### M3. Magacin — brisanje ulaza (primke) sad skida količinu + zaštita (`671a457`)
**Pre:** `magacin_obrisi_primku` je brisao samo `magacin_primke`; `magacin_stavke` kaskadira (FK `fk_ms_primka`), **ali `magacin_promet` `ulaz` redovi NE** (nema FK/trigera) → roba je ostajala na stanju (bug). **Sad** (opcija „sa zaštitom"):
1. Po grupama (lokacija/naziv/JM/namena) izračuna šta je primka donela; ako bi uklanjanje ulaza gurnulo trenutno stanje u **minus** (deo robe već potrošen/prenet) → **blokira** brisanje uz poruku koji artikal smeta.
2. Ako je čisto → obriše `ulaz` redove iz `magacin_promet` (pre kaskade), pa primku (kaskada briše stavke), PDF, log.

Frontend `obrisiPrimku` već radi `alert(d.err)` — poruka se vidi, view nije menjan. **Deploy:** 1 fajl `MagacinController.php`, bez SQL-a.

> Memorije: [[magacin-stanje-promet]], [[evidencija-utrosak-loguj]].

---

## V. Danas — obavezno radno vreme (od–do) pri unosu

**Datum:** 2026-06-21 · **Status:** 🔜 kod napisan, `php -l` čist. **Bez baze.** Test preostaje.

### Problem
Pri unosu radnih sati na „Danas" (Upiši vreme → 🤖 Analiziraj → Potvrdi), ako tekst ne sadrži vreme, AI vrati prazno `vreme_od/do/ukupno_sati`, preview ga nije ni prikazivao (uslov `if (data.vreme_od||…)`), pa se zapis sačuvao **bez radnog vremena**.

### Popravka (2 fajla, bez baze)
| Fajl | Izmena |
|---|---|
| `app/Views/danas/index.php` | Preview za „vreme" **uvek** prikaže editabilna polja **Radno vreme od–do** (popunjena AI vrednošću; prazna ako nema) — mora se potvrditi ili uneti. `osveziAiSati()` računa sate iz od/do i ažurira `_danasAiData`. Čuvanje (`danasAiSacuvaj`) blokira ako od/do nisu uneti (poruka „Niste uneli radno vreme…" + fokus) ili ako „do" nije posle „od". Helperi `normTime/satiIzmedju`. |
| `app/Controllers/DanasController.php` | Serverska kontrola u `danas_upisi_vreme`: odbij ako `vreme_od/vreme_do` prazni ili `ukupno_sati<=0` („Radno vreme (od–do) je obavezno."). |

### Deploy / test
Upload ta 2 fajla. Test: na „Danas" upiši opis BEZ vremena → Analiziraj → polja od–do prazna + upozorenje → „Potvrdi" blokiran dok ne uneseš → posle unosa čuva sa satima. Sa vremenom u tekstu → polja prepopunjena, može potvrditi ili izmeniti.

#### Update 2026-06-22 — zasivljavanje ikonica + upozorenje pre snimanja
- `DanasController` računa `vreme_upisano`/`materijal_upisan` po **stavci+korisnik** (BEZ datuma — zadatak je jednodnevni, a unos se snima sa današnjim datumom; vezivanje za `datum=$d` je ostavljalo 🕐 aktivnim kad se dan prikaza i datum unosa ne poklope). View ih već koristi (`disabled`). Posle uspešnog unosa stranica se **osveži** (`location.reload()` ~1.1s) → ikonica odmah neaktivna, **sprečen dupli unos**.
- Materijal ide u **više zapisa** (INSERT u petlji po artiklu) → nema „jedan zapis za edit"; zato u preview-u (vreme i materijal) crveno upozorenje „⚠️ Dobro proveri unos — nakon snimanja nema naknadne izmene sa terena." Izmenu radi kancelarija u Evidenciji.
- **Serverska brana protiv duplikata** (`danas_upisi_vreme`/`danas_upisi_materijal`): ako za `stavka_id+radnik_id` već postoji unos → odbij („…već uneto. Izmenu radi kancelarija."). Tako se duplo brojanje sati/materijala ne može desiti ni sa zastarelom stranom. Postojeći duplikati se brišu ručno u Evidenciji (🗑).
- Fajlovi: `app/Controllers/DanasController.php`, `app/Views/danas/index.php`.

**Datum:** 2026-06-21 · **Status:** 🔜 kod napisan, `php -l` čist. **Bez izmene baze.** Test preostaje.

### Cilj
Treći tab na Evidenciji (uz Radni sati / Utrošak): **dnevni sumar po ekipama**. Tim = jedan zadatak iz rasporeda tog dana. Po timu: gradilište + zadatak, članovi (ime, sati, opis šta su uneli), **ukupno sati za tim**, i **ukupan utrošak materijala za ceo tim** (zbirno, NE po članu). Inicijalno: izabrani dan, grupisano **po ekipi**; prekidač na **po gradilištu** (gradilište-zaglavlje + zbir sati); dan se menja poljem „Od".

### Izmene (2 fajla, bez baze)
| Fajl | Izmena |
|---|---|
| `app/Controllers/EvidencijaController.php` | `dnevniPregled($dan,$filter_grad)` **vodi se iz STVARNIH unosa** tog dana (`raspored_vreme` + `raspored_materijal`), grupiše po `stavka_id` (0/`g:`gradilište = van rasporeda); task/gradilište reši preko `LEFT JOIN raspored_stavke` (bez uslova dana/statusa — zato radi i kad stavka nije tog dana/objavljena). Članovi: sati (SUM) + opis (spojene napomene); materijal zbirno. Prosleđuje `$sumar/$grupisanje/$sumar_dan`. **Tab samo za kancelariju.** *(Prva verzija je vukla iz `raspored_dani` pa je bila prazna kad stavka ne ispunjava uslov — ispravljeno.)* |
| `app/Views/evidencija/index.php` | Tab „📋 Dnevni pregled" (vidljiv kancelariji). Dizajn po maketi: **sklopive kartice** po timu; RADNI SATI tabela (Zaposleni / Uloga / Opis aktivnosti / Sati) + badge „Ukupno: Xh"; UTROŠENI MATERIJAL tabela (Materijal / Opis / Količina / Jedinica, žuti redovi); futer „Ukupno timova / Ukupno radnika / Ukupno sati". Prekidač Po ekipi / Po gradilištu. Kontroler vraća i `uloga` radnika i `opis` (komentar) materijala. |

### Napomene / deploy
- Dan = polje „Od" (na ovom tabu „Do"/„Osoba" se ne koriste). Filter „Gradilište" radi i ovde.
- Deploy: upload ta 2 fajla. Test: izaberi dan sa rasporedom → vidi timove (gradilište/zadatak, članovi+sati+opis, ukupno sati, utrošak), prebaci „Po gradilištu".

**Datum:** 2026-06-21 · **Status:** ✅ radi (web/Android/iOS), komitovano (`3cd6738`), na produkciji (SQL pokrenut).

### Cilj
„Poruke" su imale legacy tabove (Inbox/Zadaci/Nabavka/Gradilišta/Izveštaji) koji zbunjuju — ostatak od ranije; prave funkcije su odavno na zasebnim stranicama (Zadaci=`interni_zadaci`, Nabavka=`nabavka_zahtevi`, Gradilišta=`GradilistaController`). Traženo: obične poruke kao Viber/WhatsApp — pošalji članu Tima ili **grupi „Ekošarna" (svi)**, vidi sve prepiske, notifikacija preko kanala (web/Android push, iOS Telegram), klik otvara baš tu prepisku.

### Odluke (potvrđeno)
Grupa „Ekošarna" = **pravi grupni chat** (svako piše, svi vide). Legacy poruke u bazi i legacy view fajlovi **ostaju** (ne brišu se; samo se ne rutiraju).

### Model (reupotreba `poruke` tabele)
Poruka = red `tip='poruka'`, `roditelj_id` NULL, `posiljalac_id`=autor, `primalac_id`=osoba **ili NULL = grupa**. Razgovor sa X = sve gde (ja↔X). Grupa = sve `primalac_id IS NULL`. Nepročitano preko nove tabele `poruke_procitano(korisnik_id, sagovornik_id, procitano_do)` (sagovornik 0 = grupa) — isti obrazac kao `raspored_vidjeno`.

### Izmenjeni / novi fajlovi
| Fajl | Izmena |
|---|---|
| `poruke_chat.sql` | **NOV.** `CREATE TABLE IF NOT EXISTS poruke_procitano`. Idempotentno. **Pokrenuti JEDNOM PRE deploya.** |
| `app/Controllers/PorukeController.php` | `dispatch()` → samo `chat()`; nove metode `getRazgovori/getChatPoruke/oznaciProcitano/notifikujChat`; AJAX `poruke_konverzacije/_thread/_send/_seen`; `neprocitane()` na novu logiku. Notifikacija preko `PushController::notifyKanali` + deep-link `?page=poruke&sa=<id>`/`&grupa=1`. **Legacy metode ostavljene u fajlu (nedostupne).** |
| `app/Views/poruke/inbox.php` | **Rewrite** u chat UI (lista razgovora: grupa „🏢 Ekošarna" + članovi; prepiska + mehurići + slanje; responsivno web/mob; poll 10s; deep-link auto-otvara razgovor). |
| `check_notifications.php` | Upit nepročitanih (poll 10s za badge/toast) usklađen sa novom `poruke_procitano` logikom. |

### Napomene
- Prvi ulazak: stare broadcast/direktne poruke bez `poruke_procitano` reda broje se kao nepročitane (badge može biti veći) — očisti se čim se razgovor otvori. Ako smeta, seed-ovati `procitano_do=NOW()` za sve pri deployu.
- `sw.js` se ne menja — deep-link `?page=poruke&...` se normalizuje i otvara razgovor.

### Deploy
1. Pokreni `poruke_chat.sql` na produkciji. 2. Upload `PorukeController.php`, `app/Views/poruke/inbox.php`, `check_notifications.php`. 3. Test: pošalji članu i grupi → primaoci dobiju notifikaciju (web/Android/iOS) → klik otvara prepisku → odgovore → svi vide. Badge/nepročitano radi.

---

## 0000000. Raspored — nacrt samo kreatoru (#1) + zabrana unazad (#2)

**Datum:** 2026-06-21 · **Status:** ✅ radi, komitovano (`bdfdd9a`), na produkciji. **Bez novog SQL-a** (`kreator_id` već dodat u prethodnom koraku — `raspored_kreator.sql`).

### #1 — Nacrt vidi samo kreator
`index()` sada nacrt-stavku stavlja u gornji blok **samo ako je `kreator_id == ja`** (legacy nacrti bez kreatora = vidljivi svima, fallback); tuđi nacrt se **ne prikazuje nikome** (ni u glavnoj tabeli). Objavljene stavke ostaju vidljive svima. Koristi `raspored_stavke.kreator_id`.

### #2 — Zabrana pravljenja rasporeda unazad
- **Server (izvor istine):** `raspored_dodaj_stavku` odbija dan `< danas`; `raspored_init_nedelja` odbija nedelju koja je cela u prošlosti (`datum_do < danas`).
- **View:** postojeći `$je_prosla` je bio **po nedelji** (dozvoljavao juče u tekućoj nedelji); dodat **per-dan** `$dan_prosli` koji sakriva „+ Nova stavka"/„+" na prošlim danima i u tekućoj nedelji. JS `openDodajStavku` dodatno blokira (`datum < danasYMD()`) za slučaj strane otvorene preko ponoći.

### Izmenjeni fajlovi (2, bez SQL-a)
| Fajl | Izmena |
|---|---|
| `app/Controllers/RasporedController.php` | #1 filter nacrta po `kreator_id`; #2 guard u `raspored_dodaj_stavku` i `raspored_init_nedelja`. |
| `app/Views/raspored/index.php` | #2 `$dan_prosli` (sakriva „+“ na prošlim danima) + JS guard u `openDodajStavku`. |

**Deploy:** uploaduj ta 2 fajla. (SQL `raspored_kreator.sql` je već pokrenut.) Test #1: napravi nacrt kao korisnik A → korisnik B ga ne vidi. Test #2: u tekućoj nedelji „+“ se ne vidi na prošlim danima; pokušaj direktno → server odbije.

---

## 000000. Raspored — poruke (chat) na zadatku: mobilni + notifikacije svuda

**Datum:** 2026-06-21 · **Status:** ✅ radi (web/Android/iOS), komitovano (`b2aa8d3`), na produkciji.

### Traženo
Poruke po zadatku na rasporedu su se na webu videle/slale, ali na Androidu „samo obaveštenje koje se ne vidi i nema ikonice". Treba: poruka → **svi sa zadatka (radnici + odgovoran za materijal) dobiju notifikaciju** na **web/Android/iPhone** → iz notifikacije se otvara baš taj thread → odgovore → svi opet dobiju.

### Nalaz
- 💬 dugme na „Danas" je **bilo namerno skriveno** (`display:none`); thread modal i `danas_poruke_get/add` su radili.
- `danas_poruka_add` je obaveštavao **samo ranije pisce** i kroz lokalni `notifikuj` koji radi **samo iOS/Telegram** → Android/web ništa.
- Bez deep-linka na konkretan thread; pisanje dozvoljeno samo radnicima (odgovoran ne-radnik blokiran).

### Izmene (3 fajla, bez SQL-a)
| Fajl | Izmena |
|---|---|
| `app/Controllers/RasporedController.php` | Nova **statička** `notifikujPorukaRasporeda($stavka_id,$autor_id,$tekst)`: primaoci = radnici + odgovoran + raniji pisci (minus autor); `PushController::notifyKanali` (web/Android push + iOS Telegram) sa deep-linkom `?page=danas&datum=…&openporuke=<stavka_id>`. `raspored_poruka_add` (web) je koristi. |
| `app/Controllers/DanasController.php` | `danas_poruka_add`: pristup radnik **ili** odgovoran; zove deljenu `RasporedController::notifikujPorukaRasporeda`. Uklonjen mrtav/pokvaren lokalni `notifikuj` (bio samo iOS). |
| `app/Views/danas/index.php` | 💬 dugme **otkriveno** (plavo); na učitavanju `?openporuke=<id>` automatski otvori taj thread (klik na dugme). |

### Deploy (bez baze)
Uploaduj `RasporedController.php`, `DanasController.php`, `app/Views/danas/index.php`. Test: pošalji poruku na zadatku → svi sa zadatka (uklj. odgovornog) dobiju push/Telegram na sve 3 platforme → klik otvori thread → odgovori → svi opet dobiju. `tag=raspored-poruka-<id>` (odvojeno od obaveštenja o dodeli).

#### Update 2026-06-21 — kreator zadatka dobija notifikaciju za poruke
**Problem:** poruke nisu stizale osobi koja je NAPRAVILA zadatak na rasporedu (na webu) — jer raspored, za razliku od Zadataka, nije imao „kreatora" pa nije bio u listi primalaca (samo radnici + odgovoran + raniji pisci). **Popravka:** nova kolona `kreator_id` na `raspored_stavke`; upisuje se pri kreiranju (`raspored_dodaj_stavku`) i kopiranju (`raspored_kopiraj`); `notifikujPorukaRasporeda` dodaje kreatora u primaoce. Ista kolona služi i za **#1** (nacrt vidljiv samo kreatoru).
| Fajl | Izmena |
|---|---|
| `raspored_kreator.sql` | **NOV.** `ADD COLUMN IF NOT EXISTS kreator_id INT UNSIGNED NULL` na `raspored_stavke`. Idempotentno. **Pokrenuti JEDNOM na produkciji PRE deploya.** |
| `app/Controllers/RasporedController.php` | Upis `kreator_id=Auth::id()` u dodaj/kopiraj; kreator dodat u primaoce poruka. |
**Napomena:** stari zadaci (pre kolone) imaju `kreator_id` NULL → kreator se ne notifikuje za njih; testirati na NOVOM zadatku.

---

## 00000. Raspored — odgovoran za materijal (vođe + notifikacije + Danas)

**Datum:** 2026-06-21 · **Status:** ✅ radi, komitovano (`44ae11e`), na produkciji. **Bez izmene baze.**

### Traženo (3 stavke)
1. Kad se neko dodeli na zadatak I označi kao odgovoran za materijal → **jedna** spojena poruka (već radi za jedno snimanje). Skidanje/dodavanje odgovornosti → onaj koga se tiče dobija notifikaciju — **i kad nije radnik** na zadatku.
2. Padajući meni „odgovoran za materijal" (web+mob): pored dodatih radnika **uvek** ponuditi 4 uloge (Inženjer na gradilištu, Rukovodilac operative, Monter poslovođa, Zamenik montera poslovođe), osim ako su **zauzete na drugom zadatku istog dana**.
3. Osoba koja je odgovoran a nije radnik **vidi** zadatak na „Danas" (da unese materijal) + dobije notifikaciju da je određena.

### Izmenjeni fajlovi (4) — bez SQL-a
| Fajl | Izmena |
|---|---|
| `app/Core/Auth.php` | Nova konstanta `ULOGE_ODGOVORAN_MAT` (4 „vođe") — jedini izvor istine, ne hardkoduje se po view-u. |
| `app/Controllers/RasporedController.php` | `index()` označi `vodja` flag na listi. `raspored_vreme_elektricara` prima `iskljuci_stavku` (zauzetost na DRUGOM zadatku istog dana). Notifikacije: dodaj/izmena/brisanje/objava sada obaveste i **ne-radnika** odgovornog (određen/skinut/otkazan), bez duplikata za radnike. Helperi `obavestiIliZakazi()`, `porukaOdgovoran()`. Brisanje **nacrta** više ne šalje „otkazan" (ekipa ga nije ni videla). |
| `app/Controllers/DanasController.php` | „Danas" upit: prikaži stavku ako je korisnik radnik **ILI** `odgovoran_id` (LEFT JOIN radnika); + filter `status='objavljeno'` (nacrti se ne prikazuju). |
| `app/Views/raspored/index.php` | `azurirajOdgovornaSelect`: radnici (👷) + vođe (🛠️, osim zauzetih) + uvek zadrži trenutno izabranog; wrap se vidi i bez radnika. `openDodajStavku`/`openIzmeniStavku` učitaju zauzeća dana (izmena: bez svoje stavke) pa grade meni. |

### Napomena o „dve poruke + 10 min"
Nije postojala zasebna „odgovoran" notifikacija — dve poruke su nastajale kad se odgovornost doda **naknadno, drugim editovanjem** (razmak = vreme između snimanja). Jedno snimanje = jedna spojena poruka.

### Deploy (bez baze)
Uploaduj `app/Core/Auth.php`, `app/Controllers/RasporedController.php`, `app/Controllers/DanasController.php`, `app/Views/raspored/index.php`. Test: meni odgovornog nudi vođe (i bez radnika); izaberi vođu koja nije radnik → ona dobije „Određen si…", vidi zadatak na „Danas", može da unese materijal; skini je → dobije „Više nisi…".

---

## 0000. Raspored — zakazano (scheduled) + logo badge

**Datum:** 2026-06-21 · **Status:** ✅ radi, komitovano (`44ae11e`), na produkciji (SQL pokrenut, cPanel cron postavljen, zakazano + badge potvrđeni).

### #4 — „Zakaži obaveštenje" sada radi (pravi uzrok nađen)
Tabela `raspored_obavestenja` je čuvala **samo** `nedelja_id+send_at` — bez primaoca i bez teksta — a **nijedan cron je nije čitao** (`grep`: pisana na 1 mestu, čitana nigde). Zato zakazano slanje nikad nije odlazilo (npr. zakazano uveče za 07:00). Plus: `datetime-local` daje `...T07:00`, a MariaDB traži razmak.

**Popravka:**
| Fajl | Izmena |
|---|---|
| `raspored_zakazano.sql` | **NOV.** `ADD COLUMN IF NOT EXISTS stavka_id, radnik_id, poruka, datum` u `raspored_obavestenja` + indeks `(poslato, send_at)`. Idempotentno (MariaDB). **Pokrenuti JEDNOM na produkciji PRE deploya.** |
| `app/Controllers/RasporedController.php` | `zakaziObavestenje()` puni nove kolone + normalizuje `T→razmak`/+sekunde + prima `$datum`; svi pozivi (dodaj/izmena ×2) prosleđuju datum. Novi **javni** `posaljiZakazane()` šalje dospele (`poslato=0 AND send_at<=NOW()`), markira `poslato=1`. |
| `raspored_cron.php` | **NOV.** Bootstrap kao `push_cron.php`; zove `(new RasporedController())->posaljiZakazane()`; loguje u `logs/raspored_cron.log`. |
| `htaccess` | Dodat izuzetak `RewriteRule ^raspored_cron\.php$ - [L]` (doslednost; CLI cron ga ionako ne traži). |

**cPanel cron (OBAVEZNO — bez ovoga zakazano ne radi):**
```
Minute: */5  Hour: *  Day: *  Month: *  Weekday: *
Command: /usr/local/bin/php /home/CPANEL_USER/public_html/mvc/raspored_cron.php
```
(zameni `CPANEL_USER` pravim nalogom; proveri putanju kao kod `push_cron.php`).

**Deploy redosled:** 1) `raspored_zakazano.sql` na produkciji. 2) Upload `RasporedController.php` + `raspored_cron.php` + `htaccess`. 3) Dodaj cron. 4) Test: zadaj stavku sa „Zakaži obaveštenje" za +5 min → posle cron ciklusa stigne push/Telegram; log u `logs/raspored_cron.log`.

#### Update 2026-06-21 (posle prvog testa) — 2 ispravke
- **Klik na zakazanu notifikaciju → 404 (rešeno).** Zakazane šalje cron (CLI) gde `BASE_URL` (auto-detect iz `$_SERVER`) ispadne smeće (`http://localhost/home/.../mvc`), pa je klik vodio na `ekosarna.com/home/.../mvc/...` = 404. `notifikuj()` sada šalje **putanju nezavisnu od hosta** (`/mvc/?page=danas...` i `icon` `/mvc/public/...`) — sw.js je re-bazira na origin. Radi i za trenutno i za zakazano. **Isti bug ispravljen i u `push_cron.php`** (dnevni podsetnik Zadataka 08:00): `url` → `/mvc/?page=zadaci&openz=<id>` (klik otvara baš taj zadatak), `icon` → `/mvc/public/...`.
- **Izmena stavke sada pokazuje da je slanje zakazano (rešeno).** `raspored_get_stavku` vraća `zakazano_at` (najraniji neposlati `send_at`, format `datetime-local`); `openIzmeniStavku` pre-selektuje radio „Zakaži obaveštenje" + popuni vreme. Uz to: izmena i brisanje stavke **otkažu** ranije zakazane (neposlate) za tu stavku (`DELETE ... poslato=0`) — bez duplikata i bez slanja poruke o već obrisanom/izmenjenom zadatku.

#### Update 2026-06-21 (varijanta B) — nacrt pamti izbor obaveštenja, šalje tek na „Objavi"
Ranije je nacrt forsirao „ne obaveštavaj" pa se uneto zakazано vreme **tiho gubilo** (pri izmeni se nije videlo). Naručilac bira **B**: nacrt **pamti** izbor (odmah/zakazano+vreme/ne) ali **NIŠTA ne šalje/zakazuje dok se ručno ne klikne „Objavi"** (tek tada — odmah ili za to vreme). Razlog: ne sme da „odleti" nešto što se ne očekuje sa drafta.
| Fajl | Izmena |
|---|---|
| `raspored_draft_obavesti.sql` | **NOV.** `ADD COLUMN IF NOT EXISTS obavesti_tip ENUM('odmah','zakazano','ne'), obavesti_at DATETIME` na `raspored_stavke`. Idempotentno. **Pokrenuti JEDNOM na produkciji PRE deploya.** |
| `app/Controllers/RasporedController.php` | Dodaj/izmena: čuva `obavesti_tip`+`obavesti_at` (izbor korisnika), ali za **nacrt** je stvarno slanje `'ne'` (ne šalje/ne zakazuje). `objaviStavkuInterno()` na „Objavi" poštuje zapamćeni izbor (ne → bez obaveštenja; zakazano+buduće → zakaži; inače → odmah). `get_stavku` vraća `obavesti_tip`+`obavesti_at` (objavljeno sa neposlatim zakazanim je merodavno). Novi helper `normDatetime()`. |
| `app/Views/raspored/index.php` | `openIzmeniStavku` postavlja radio po `obavesti_tip` (sva 3 stanja) + vreme za zakazano. |

**Deploy:** 1) `raspored_draft_obavesti.sql` na produkciji. 2) Upload `RasporedController.php` + `raspored/index.php`. 3) Test: napravi nacrt sa „Zakaži obaveštenje"+vreme → ekipa NIŠTA ne dobije; izmeni nacrt → vreme se vidi; „Objavi" → tek tada se zakaže za to vreme i odleti kroz cron.

### #5 — Logo silueta u statusnoj traci (badge)
Statusna traka na Androidu uvek prikazuje **belu siluetu** (OS pravilo, ne naš kod) — boja je nemoguća; pun logo u boji se vidi tek kad se notifikacija razvuče (`icon-192`). Napravljena čistija bela silueta pravog logoa (zakošeno „E" sa nodovima) iz `icon-192` → `public/badge-72.png` (96×96, belo na providnom). `sw.js` ga već koristi kao podrazumevani `badge` → primenjuje se na SVE push notifikacije bez izmene koda. **Deploy:** upload `public/badge-72.png`; vidi se na sledećoj notifikaciji (nije keširan kao `sw.js`).

---

## 000. Raspored — Android push za dodelu (klik otvara Danas)

**Datum:** 2026-06-20 · **Status:** ✅ radi, komitovano (`a498338`), na produkciji.

### Uzrok
`RasporedController::notifikuj()` je za Android imao samo `// TODO: Push za android` — slao je **isključivo** Telegram za iOS. Zato dodeljena osoba na Androidu nije dobijala obaveštenje kad joj se zada/izmeni zadatak u rasporedu.

### Popravka (1 fajl: `app/Controllers/RasporedController.php`, bez baze, bez `sw.js`)
- `notifikuj()` sada honoriše **kanale iz Tima** (`platforma` + `platforma2`, oba do 2 kanala):
  - `android` / `web` → **web push** preko `PushController::notifyUsers()` (isti dokazani put kao interni Zadaci);
  - `ios` → **Telegram** (kao i pre).
- Naslov = 1. linija poruke, telo = ostatak. `tag = 'raspored-'.$stavka_id` (više dodela = više notifikacija; otkazivanje sa istim tagom zameni staru).
- **Klik otvara zadatak:** URL = `?page=danas&datum=<datum>` — Danas sme svaka uloga (`requireLogin`) i prikazuje radniku baš taj zadatak za taj dan (Raspored stranicu teren ne sme). `sw.js` već normalizuje URL na origin i navigira (linije 27, 54–87) — proveren put.
- Dodati parametri `$datum` i `$stavka_id` u `notifikuj()` i prosleđeni sa **svih 6** poziva: dodavanje, izmena (uklonjen + nov/izmenjen radnik), brisanje/otkaz, nova poruka, objava nacrta.

### Mapiranje kanala (Tim → ponašanje)
| Tim opcija | platforma | Ponašanje |
|---|---|---|
| 🤖 Android (Push notifikacije) | `android` | web push |
| 🌐 Web only | `web` | web push |
| 🍎 iOS (Telegram notifikacije) | `ios` | Telegram |

### Test
- Korisnik sa kanalom Android (i aktiviranom push pretplatom na svom telefonu): zadaj mu stavku u rasporedu („Objavi"/„Obavesti odmah") → stigne push → klik → otvara se **Danas** na tom danu sa zadatkom.
- Provera kanala: ako ne stigne, prvo u Timu vidi da li mu je kanal `android`/`web` i da li je uopšte aktivirao push na uređaju (`logs/push_send.log`).

---

## 00. Magacin — izmena bez reload-a (osveži samo red)

**Datum:** 2026-06-20 · **Status:** ✅ test prošao, komitovano (`e483f34`), na produkciji.

### Cilj
Izmena artikla u **Stanju zaliha** i u **Ulazu robe** je radila `location.reload()` cele strane → sporo kad lokacija ima 50+ artikala (npr. masovno postavljanje „namenjeno za"). Treba osvežiti **samo taj red**.

### Rešenje
- **Ulaz robe** (`magacin_uredi_stavku`): uvek bezbedno (svaka stavka = svoj red). JS ažurira ćelije reda (naziv/količina/JM/lokacija/„namenjeno" čip) + `data-*` atribute dugmeta iz vrednosti forme. Server nepromenjen.
- **Stanje zaliha** (`magacin_izmeni_stanje`): ažurira red u mestu. **2 strukturna slučaja → pun reload** (vraća `reload:true`): (a) preimenovanje/JM (kaskadira na sve lokacije), (b) promena „namenjeno za" u grupu koja na toj lokaciji već ima stanje (spajanje redova). Detekcija u kontroleru pre korekcija.

### Izmenjeni fajlovi (2)
| Fajl | Izmena |
|---|---|
| `app/Controllers/MagacinController.php` | `magacin_izmeni_stanje`: `$renamed` + provera ciljne namena-grupe (`stanjeArtikla`) → `$strukturna`; vraća `['ok'=>true,'reload'=>$strukturna]`. |
| `app/Views/magacin/index.php` | `openIzmena`/`openUrediStavku` pamte referencu na red (`_magIzmenaRow`/`_magUrediRow`/`_magUrediBtn`); `sacuvajIzmena` (reload fallback + in-place) i `sacuvajUrediStavku` (in-place) umesto `location.reload()`; novi helper `fmtBroj()` (format „1.234,56", `sr-RS`). |

### Napomene / test
- Prenos / „Premesti lokaciju" / brisanje primke i dalje rade reload (strukturne radnje) — namerno.
- Test: u Stanju postavi „namenjeno za" na artikl bez namene → red se trenutno osveži (čip), bez skoka na vrh strane; promeni samo količinu → trenutno; preimenuj artikl → pun reload (očekivano). U Ulazu izmeni stavku → red se osveži u mestu.
- Nema izmene baze.

---

## 0. Raspored — nacrt (draft) pre objave

**Datum:** 2026-06-20 · **Status:** ✅ radi, na produkciji (SQL pokrenut, komitovano `376dac7`). Prošireno u sekciji 0000000 (#1).

### Cilj
Inženjer pravi dnevni raspored uz prekide. Treba mu da snima raspored kao **nacrt** dok ne završi — **bez slanja obaveštenja ekipi** — pa da na kraju **objavi** (tek tada obaveštenja odlete).

### Odluke (potvrđeno sa naručiocem)
- Vizuelno: **odvojen blok „📝 NACRTI" na vrhu** strane, grupisan po danu (objavljeni raspored ostaje čist u postojećoj tabeli ispod).
- Objava: **po danu** („📢 Objavi N nacrta") **+ pojedinačno** („Objavi" na svakoj nacrt-stavki).
- U modalu: „Sačuvaj" → **dva dugmeta**: `💾 Snimi privremeno` (nacrt, bez obaveštenja) i `📢 Objavi` (objavljeno + obaveštenje kao do sada).

### Izmenjeni / novi fajlovi (3)
| Fajl | Izmena |
|---|---|
| `raspored_nacrt.sql` | **NOV.** `ALTER TABLE raspored_stavke ADD COLUMN IF NOT EXISTS status ENUM('nacrt','objavljeno') NOT NULL DEFAULT 'objavljeno'`. Idempotentno (MariaDB). **Pokrenuti JEDNOM na produkciji PRE deploya** — kontroler radi `INSERT ... status`, pa bez kolone unos stavke puca. |
| `app/Controllers/RasporedController.php` | `index()` razdvaja nacrte (`$nacrti`, grupisano po danu) od objavljenih (glavna tabela). `raspored_dodaj_stavku`/`raspored_izmeni_stavku` primaju `status`; za `nacrt` se `obavesti_tip` forsira na `'ne'` (bez obaveštenja) + `status` u INSERT/UPDATE. Nove akcije `raspored_objavi_stavku` i `raspored_objavi_dan` + helper `objaviStavkuInterno()` (status→objavljeno + obaveštenje ekipi, idempotentno). `raspored_kopiraj` čuva `status`. |
| `app/Views/raspored/index.php` | Gornji blok „📝 NACRTI" (po danu, sa „Objavi N nacrta" + po stavci Objavi/✏️/🗑) + CSS. Modal: dva dugmeta. JS: `sacuvajStavku(status)`, `objaviStavku(id)`, `objaviDan(danId, broj)`. |

Router nije menjan — `raspored_*` ide po prefiksu (`Router.php:158`), nove akcije se automatski dispatchuju.

### Ponašanje / edge-case
- Dan koji ima samo nacrte → u glavnoj tabeli pokazuje „Nema zadataka" (nacrti su u gornjem bloku). Očekivano.
- Editovanje objavljene stavke + „Snimi privremeno" = vraća je u nacrt (bez de-notifikacije već poslatih). Logički dosledno; OK.
- Radio „Obavesti odmah/Zakaži/Ne" ostaje i važi za **Objavi**; za nacrt se ignoriše.

### Deploy redosled
1. Pokreni `raspored_nacrt.sql` na produkciji (i lokalno za test).
2. Uploaduj `app/Controllers/RasporedController.php` + `app/Views/raspored/index.php`.
3. Test: dodaj stavku „Snimi privremeno" → pojavi se u NACRTI bloku, ekipa NIJE obaveštena → „Objavi" (stavka/dan) → prelazi u glavnu tabelu + ekipa dobije obaveštenje.

### Sledeći korak
- Lokalni/produkcioni test po gornjem; ako OK → commit (1 SQL + 2 fajla). Predlog poruke: `Raspored: nacrt (draft) pre objave — Snimi privremeno vs Objavi + blok Nacrti`.

---

## 1. Magacin — „Namenjeno za" (gradilište) + pregled loga

**Datum:** 2026-06-20 · **Status:** ✅ test prošao na produkciji; SQL pokrenut, kod komitovan i pušovan.

### Cilj
Objediniti na jednom mestu: **gde roba fizički jeste** (`lokacija`) i **za koje gradilište je namenjena** (`namenjeno_gradiliste_id`, nezavisno polje — sa otpremnice). Plus pregled audit loga.

### Odluke (potvrđeno sa naručiocem)
„Namenjeno za" je **po stavci**, **opciono**; u stanju se prikazuju **odvojeni redovi po nameni**.

### Faza 1 — Baza · `mvc/magacin_namenjeno.sql`
`ALTER` dodaje `namenjeno_gradiliste_id INT UNSIGNED NULL` u `magacin_promet` i `magacin_stavke` (+ indeks). Idempotentno (`IF NOT EXISTS`, MariaDB). **Pokrenuti JEDNOM na produkciji PRE deploya koda** — kontroler čita tu kolonu pri svakom otvaranju Magacina, pa bez nje Magacin puca.

### Faza 2 — `app/Controllers/MagacinController.php`
- Čuva „namenjeno za": novi ulaz (po stavci), izmena stavke, izmena stanja, prenos. Premeštanje cele lokacije zadržava namenu svakog artikla.
- `getStanjePoLokaciji()` grupiše po `+ namenjeno_gradiliste_id` (odvojeni redovi) i povlači ime gradilišta; `stanjeArtikla()` validira po tačnoj grupi (uklj. NULL).
- Izmena stanja: promena namene = prebacivanje količine iz stare u novu grupu.
- Log proširen: izmene nose „namenjeno za" (staro/novo, kao **ime** gradilišta preko `gradNaziv()`); dodato logovanje **kreiranja** i **brisanja** ulaza.

### Faza 3 — `app/Views/magacin/index.php`
- „🎯 Namenjeno za" (narandžasto izdvojeno, klasa `.mag-namenjeno`) u: novi ulaz (podrazumevano + po stavci), prenos (odredište, nasleđuje izvor), izmena stanja, izmena stavke ulaza.
- Prikaz: čip „🎯 gradilište" u listi stanja + nova kolona u stavkama ulaza.

### Faza 4 — Pregled loga (tab „🕘 Istorija")
- Novi tab u Magacinu, **vidljiv samo Direktor/AT/AF** (`Auth::isAdmin()`); non-admin ga ne vidi i `?tab=log` mu se svede na `stanje` (i u kontroleru i u view-u).
- Kartice: akcija (obojena), tip, korisnik + datum-vreme, „Pre → Posle" (JSON snapshot čitljivo). Limit 300, najnovije gore.

### Deploy redosled
1. Pokreni `magacin_namenjeno.sql` na produkciji.
2. Uploaduj `app/Controllers/MagacinController.php` + `app/Views/magacin/index.php`.
3. Test: novi ulaz sa namenom → stanje (odvojeni redovi + čip) → prenos/izmena menjaju namenu → tab „Istorija" (kao admin) prikazuje izmene.

### Otvoreno
- `magacin_tabele.sql` je zastareo (nema `magacin_promet`/`magacin_log`/`gradiliste_id`); nije usklađivan sada — produkcija ide preko `magacin_namenjeno.sql`.

---

## 2. Magacin — mobilni: otvaranje stavki + dokument u modalu

**Datum:** 2026-06-20 · **Status:** ✅ potvrđeno na web-u i telefonu; komitovano. Sve u `app/Views/magacin/index.php`.

Tab „Ulaz robe" (`?page=magacin&tab=primke`) je jedna `<table class="rs-tabela mag-card mag-primke">` koju `@media (max-width:720px)` pretvara u kartice.

1. **Klik na dobavljača nije otvarao stavke na mobilnom.** `togglePrimka()` je red sa stavkama postavljao na `display:table-row`, što se ne renderuje u „card" (block) layoutu. Lek: JS sada postavlja `''` (pusti CSS), + media-query daje detalj redu `display:block`. **Glavna zamka:** pravilo `.mag-card > tbody > tr > td:first-child { display:none }` (skriva redni broj) je sakrivalo i jedinu `colspan` ćeliju detalj-reda → dodato `display:block !important` na tu ćeliju.
2. **Prikaz dokumenta.** Pre: `<a target="_blank">` (na mobilnom otvori preko celog ekrana bez „zatvori", back izbaci iz app-a). Sada: dugme zove **deljeni modal** `openModal(url, type)` (modal `#fajl-modal` je u `layout/footer.php`, JS u `public/js/app.js`) — X gore desno + „⬇ Preuzmi", isto na web-u i mobilnom. Tip se bira po ekstenziji (jpg/png… → `img`, ostalo → `pdf`). Vidi [[deljeni-fajl-modal]].

---

## 3. Zadaci — notifikacije + chat komentari

**Datum:** 2026-06-20 · **Status:** ✅ klik notifikacije i kontrola roka POTVRĐENI na uređaju; komitovano.

### Update 2026-06-21 — Zadaci: inicijalni prikaz po ulozi (default view)
`ZadaciController::index()`, samo DEFAULT (bez izabrane „Prihvatio" osobe):
- **Vidljivost:** **Direktor** vidi sve; **ostali** (uklj. AT/AF) vide **neprihvaćene + samo one koje su LIČNO prihvatili** — tuđi prihvaćeni se **NE prikazuju** (`$vidljivoDefault`; primenjen i na listu i na brojače).
- **Redosled:** Direktor — (0) neprihvaćeni → (1) prihvaćeni; ostali — (0) neprihvaćeni → (1) moji prihvaćeni. Unutar grupe **id opadajuće** (najnoviji prvi).
- **Filteri ostaju:** kad se izabere „Prihvatio: <osoba>", `$uScope` ograničava na tu osobu a `$vidljivoDefault` propušta (pa se i tuđi vide kroz filter). `rok` sort, status, pretraga, kategorija, paginacija netaknuti. 1 fajl, bez baze.

### Update 2026-06-21 — Zadaci notifikacije sada poštuju kanal (iPhone/Telegram)
**Uzrok:** Zadaci su slali samo `PushController::notifyUsers()` = **samo web push**; iPhone korisnici (kanal `ios`) idu preko **Telegrama**, pa nisu dobijali ništa. **Popravka:** nova kanalno-svesna metoda `PushController::notifyKanali($userIds, $payload, $tgText=null)` (android/web → web push; ios → Telegram, tekst iz title+body+url). Sva 3 Zadaci slanja (`zadatak_add`, `zadatak_prihvati`, `zadatak_komentar`) je koriste. 2 fajla: `PushController.php` + `ZadaciController.php`, bez baze. *(Napomena: iPhone mora imati kanal `ios` u Timu i povezan Telegram (`telegram_subscriptions.aktivan=1`). Raspored `notifikuj()` i dalje ima svoju kopiju logike — može se kasnije svesti na `notifyKanali`.)*

### Update 2026-06-21 — komentar-notifikacija svim učesnicima razgovora
Komentar-notifikacija je išla samo `kreirao_id` + `prihvaceno_id`; promašivala je (a) dodeljenog dok ne prihvati i (b) **treću osobu** uključenu u prepisku (npr. admin/inženjer koji nije ni kreator ni dodeljeni). `zadatak_komentar` sada cilja `kreirao_id` + `dodeljeno_id` + `prihvaceno_id` + **sve koji su ranije komentarisali** (`DISTINCT autor_id` iz `zadaci_komentari`), dedup, osim autora trenutnog komentara. `openz` deep-link (otvara zadatak proširen) je već radio. 1 fajl: `ZadaciController.php`, bez baze.

### Update 2026-06-20 (kasnije) — klik notifikacije REŠEN + kontrola roka

**Klik na notifikaciju (mobilni) — uzrok i lek.** Klik ranije „ništa nije radio" / „ekran blinke pa ništa". Dva uzroka, oba rešena u `sw.js`:
1. **`url` je bio `http://`** (BASE_URL na hostingu iza proksija pogrešno detektuje šemu) → PWA na `https` ne otvara `http` adresu. Lek: u `notificationclick` se URL **normalizuje na trenutni origin** (`new URL(raw, self.location.origin)` → uzmi samo `pathname+search+hash`).
2. **Action-dugmići** („Otvori zadatak"/„Zatvori") — otvaranje prozora preko action-dugmeta je na Androidu nepouzdano (blink pa ništa). Lek: **uklonjeni `actions`**, cela notifikacija je jedan klik-cilj (telo). Dodato i `requireInteraction:false` (lakši heads-up).
- `CACHE_NAME` = `ekosarna-v7`. `activate` loguje `[SW] Aktivan: <verzija>`. (Usput korišćen debug build v6 sa verzijom u naslovu da se golim okom potvrdi koja je verzija aktivna na telefonu — uklonjeno.)
- Dijagnostika ubuduće: ako klik opet zataji, prvo proveri da naslov/`activate` log pokazuje očekivanu verziju (znači da je nov SW zaista aktivan), pa tek onda diraj logiku.

**Heads-up baner („samo zvuk + slovo E gore"):** slovo „E" u statusnoj traci je normalna Android mono-ikonica (ne može „već otvoreno"). Da notifikacija iskoči kao baner = Android **importance HIGH** za kanal te (PWA) aplikacije — podešava **korisnik** u Android: Podešavanja → Aplikacije → Ekošarna → Obaveštenja → „Iskači na ekran". Web push nema pouzdan API da to natera iz koda.

**Kontrola roka (`app/Views/zadaci/index.php`):** rok izvršenja ne može pre današnjeg dana. `min="<?= $today ?>"` na `#z-rok` i `#z-edit-rok` + JS provera u `dodajZadatak()` i `sacuvajZadatak()`. Kod izmene blokira samo ako se rok **promeni** na prošlost (zakasneli zadatak sa već prošlim rokom se i dalje čuva — poredi se sa `window._zEditRokOrig`). Poruka: „Rok izvršenja ne može biti raniji od današnjeg datuma. Izaberite današnji ili neki budući datum."

> **Deploy podsetnik:** posle commita ipak treba ručno uploadovati `sw.js` (v7) i `app/Views/zadaci/index.php` na cPanel — git push NE deplojuje. `sw.js` se na telefonu osvežava zatvaranjem/otvaranjem PWA (ili reinstalacijom).

### Originalni opis (prva tura istog dana)

### Cilj (šta je traženo)
1. Klik na notifikaciju otvara konkretan zadatak **proširen** (ceo tekst + komentari). Na mobilnom klik ranije nije radio ništa.
2. Kad neko **prihvati** zadatak → zadavalac dobije: `[ime] je prihvatio zadatak: <prvih 100 znakova>`. Klik otvara taj zadatak.
3. Kad neko ostavi **komentar** → druga strana (zadavalac + onaj koji je prihvatio, osim autora) dobije: `Komentar na zadatak od [ime]: <prvih 50 znakova>`. Klik otvara zadatak sa porukama.
4. **Bug:** pri unosu komentara na web-u pojavljivao se `NaN` i **dupla** poruka. Popravljeno.
5. Chat prikaz (Viber/SMS): pošiljalac desno, primalac levo, uz ime. (Već postojalo — ujednačen render.)

### Uzrok bug-a (NaN + duplikat)
Sve iz `app/Views/zadaci/index.php`:
- **Duplikat:** `posaljiZKomentar()` je optimistički dodavao mehurić ali NIJE pomerao poll-kursor `_zOtvoreni[id]`, pa je polling (5s) povlačio isti komentar i dodavao ga PONOVO.
- **NaN:** polling badge-update je hvatao PRVI `<span>` u dugmetu (strelicu ▶/▼) i radio `parseInt('▼') → NaN`, pa upisivao "NaN" umesto strelice.
- Popravka: namenski `#zkom-count-<id>` span za brojač + pomeranje poll-kursora na server `created_at`.

### Izmenjeni fajlovi (4) — putanje relativno na `mvc/`
| Fajl | Izmena |
|------|--------|
| `app/Controllers/ZadaciController.php` | `zadatak_prihvati`: push zadavaocu; `zadatak_komentar`: vraća upisani komentar + push drugoj strani; `zadatak_add`: URL sa `&openz=<id>`. |
| `app/Views/zadaci/index.php` | NaN+duplikat fix; `renderKomBubble()`/`setKomCount()`/`bumpKomCount()` helperi; `?openz=<id>` na učitavanju proširi i skroluje do zadatka. |
| `sw.js` | `notificationclick`: fokus prozora + `postMessage({type:'navigate',url})` (pouzdano na Androidu); `openWindow` fallback. `CACHE_NAME` → `ekosarna-v3`. |
| `app/Views/layout/footer.php` | `navigator.serviceWorker` `message` listener → `window.location.href = url`. |

### Prebacivanje na produkciju
- Uploaduj gornja **4 fajla**. **Nema izmena baze.** **NE treba re-login** — samo **refresh**.
- **`sw.js` je keširan:** novi worker preuzme posle 1–2 reload-a (`skipWaiting()`+`clients.claim()`). Telefon: zatvori pa otvori PWA. Ako ne povuče: Chrome DevTools → Application → Service Workers → Update/Unregister.

### Test ček-lista
- [ ] Web, jedan nalog: unesi komentar → nema `NaN`, nema duplikata (sačekaj ~5s, poll ciklus).
- [ ] Chat izgled: tvoje poruke desno (plavo), tuđe levo (sivo) sa imenom.
- [ ] Dva naloga: A zada/dodeli → B dobije „Novi zadatak"; klik otvara zadatak proširen.
- [ ] B prihvati → A dobije „[ime] je prihvatio zadatak: …"; klik otvara zadatak.
- [ ] Komentar od A → B dobije „Komentar na zadatak od [ime]: …" (i obrnuto); klik otvara zadatak sa porukama.
- [ ] Mobilni: klik na notifikaciju fokusira app na tom zadatku (posle osvežavanja SW-a).

### Poznata ograničenja
- `?openz=<id>` proširuje zadatak samo ako je on na trenutnoj strani/filteru (završeni skriveni, paginacija 15/str). Skorašnji otvoreni/u toku su na 1. strani.
- Mobilni klik nije mogao da se verifikuje lokalno — testirati na uređaju posle deploy-a.

### Sledeći korak
1. Otestirati po ček-listi.
2. Ako OK → **komit** (4 fajla). Predlog poruke: `Zadaci: notifikacije za prihvatanje i komentare + fix NaN/duplikat + klik notifikacije otvara zadatak`.
3. Ako nešto ne valja — javiti pa doraditi.

---

## 4. Push notifikacije — stanje

**Datum:** 2026-06-16 · **Status:** ✅ Push RADI na produkciji (potvrđeno na iPhone). Ostalo poliranje + odluka web vs native.

### TL;DR — gde smo stali
- ✅ Push radi na produkciji.
- ⚠️ Ostao „kozmetički" problem: Chrome ume da prikaže **„Possible spam"** upozorenje.
- 🔜 Testirati pravu notifikaciju (stvarni zadatak) i odlučiti web push vs native app.

### Glavni problem (rešen)
Slanje je tiho padalo (`poslato: 0`) jer je `webpush/` (Minishlink) tražio Composer pakete kojih nema (`GuzzleHttp\Client`, `Base64Url\Base64Url`, `Jose\Component\...`). Projekat **nema Composer/vendor**. Prava greška: `Class "Base64Url\Base64Url" not found`.
**Rešenje:** samostalni sender samo na PHP ekstenzijama (`openssl`, `curl`, `hash_hkdf`) — bez spoljnih biblioteka.
Produkcija (provereno): PHP **8.2.31**, `openssl` ✓, `curl` ✓, `openssl_pkey_derive()` ✓, `hash_hkdf()` ✓.

### Izmenjeni / novi fajlovi
| Fajl | Status | Šta radi |
|---|---|---|
| `app/Core/PushSender.php` | **NOV** | Samostalno slanje: VAPID ES256 potpis + `aes128gcm` (RFC 8291/8188) + `curl`. Briše pretplatu na HTTP 404/410. |
| `app/Controllers/PushController.php` | izmenjen | `sendToSubscription()` koristi `Core\PushSender`; loguje pravi razlog. |
| `app/Views/layout/footer.php` | izmenjen | Pri svakom učitavanju sinhronizuje pretplatu (upsert); stara sa drugim ključem → obriše i napravi novu. |
| `sw.js` | izmenjen | `fetch` handler (uslov za PWA instalaciju). *(Napomena: od 2026-06-20 `CACHE_NAME` = `ekosarna-v3`, vidi sekciju 1.)* |
| `push_test.php` | **NOV (privremen)** | Admin test: `…/mvc/push_test.php` (sebi) ili `?uid=N`. Broj pretplata, rezultat slanja, PHP okruženje, HTTP status/razlog. |

Nepromenjeni (koriste se): `app/Models/PushSubscription.php`, `push_cron.php`.

### Ključevi / konfiguracija
- **Pravi VAPID ključevi su na produkciji**, u `/home/ekosarna/.env` (iznad `public_html`). `Config.php` bira prvi postojeći `.env`.
- **Lokalni `.env`** ima placeholder `123` — OK, push se testira na produkciji (HTTPS); ne radi sa `http://localhost`.
- Ikonice: `public/icon-192.png`, `public/icon-512.png` (HTTP 200).

### TODO
1. **Test prave notifikacije** — stvarni zadatak, da stigne čisto (bez „Possible spam"). Pretpostavka: „spam" je izazvalo gomilanje identičnih TEST poruka na novoj pretplati.
2. **`push_test.php` šalje DUPLO** (kroz `notifyUsers` + u dijagnostici) — popraviti da šalje jednom. *(NIJE urađeno.)*
3. **`sw.js` poliranje** — razmotriti `lang:'sr'`, da li `requireInteraction` ostaje. *(NIJE urađeno.)*
4. **Odluka: web push vs native (FCM).** „Possible spam" je Chrome funkcija samo za WEB. Native = više posla (Capacitor/TWA + Firebase + Play $25; iOS + APNs $99/god). Plan ako native: Capacitor + FCM nad postojećim sajtom.
5. **Čišćenje kad proradi:** obrisati `push_test.php` i mrtav `webpush/` folder.

### Kako testirati (podsetnik)
1. Prebaci izmenjene fajlove na produkciju (čuvaj strukturu).
2. `https://ekosarna.com/mvc/?page=home`, hard refresh (Ctrl+F5), prihvati „Aktiviraj" + dozvolu → konzola: `[Push] Uređaj sačuvan na serveru ✓`.
3. `https://ekosarna.com/mvc/push_test.php` → `subscription-a: 1`, `Uspeh: DA | HTTP status: 201`.
4. Log: `logs/push_send.log`. PHP greške → `error_log`.

### Napomena (iskreno)
„Possible spam" kontroliše Google ML model na uređaju — **ne postoji header/podešavanje** koje garantuje da se neće prikazati. Smanjuje se: pravim/specifičnim sadržajem, brendiranom ikonicom, retkim slanjem, realnom upotrebom.
