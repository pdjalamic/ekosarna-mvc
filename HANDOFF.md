# HANDOFF — tekuće stanje rada (Ekošarna panel)

> Jedan fajl za „dokle smo stigli". Sekcije po temama. Najnovije gore.
> Kad nastavljamo rad, OVO se otvara prvo.

## Sadržaj
1. [Magacin — „Namenjeno za" (gradilište) + pregled loga](#1-magacin--namenjeno-za-gradiliste--pregled-loga)  · *2026-06-20* · ✅ test prošao na produkciji; komitovano
2. [Magacin — mobilni: otvaranje stavki + dokument u modalu](#2-magacin--mobilni-otvaranje-stavki--dokument-u-modalu)  · *2026-06-20* · ✅ radi (web + mob), komitovano
3. [Zadaci — notifikacije + chat + klik notifikacije + kontrola roka](#3-zadaci--notifikacije--chat-komentari)  · *2026-06-20* · ✅ radi (klik notifikacije + rok potvrđeni na uređaju)
4. [Push notifikacije — stanje](#4-push-notifikacije--stanje)  · *2026-06-16* · ✅ radi na produkciji, ostalo poliranje

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
