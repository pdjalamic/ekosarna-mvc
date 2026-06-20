# HANDOFF — tekuće stanje rada (Ekošarna panel)

> Jedan fajl za „dokle smo stigli". Sekcije po temama. Najnovije gore.
> Kad nastavljamo rad, OVO se otvara prvo.

## Sadržaj
1. [Zadaci — notifikacije + chat + klik notifikacije + kontrola roka](#1-zadaci--notifikacije--chat-komentari)  · *2026-06-20* · ✅ radi (klik notifikacije + rok potvrđeni na uređaju)
2. [Push notifikacije — stanje](#2-push-notifikacije--stanje)  · *2026-06-16* · ✅ radi na produkciji, ostalo poliranje

---

## 1. Zadaci — notifikacije + chat komentari

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

## 2. Push notifikacije — stanje

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
