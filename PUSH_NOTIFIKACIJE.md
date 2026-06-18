# Push notifikacije — stanje rada (handoff)

> Poslednje ažurirano: 2026-06-16. Cilj: PWA web push notifikacije za Ekošarna panel.

## TL;DR — gde smo stali

✅ **Push RADI na produkciji.** Test notifikacija je stigla na iPhone (potvrđeno).
⚠️ Ostao samo jedan „kozmetički" problem: Chrome ume da prikaže **„Possible spam"** upozorenje.
🔜 Sutra: testirati **pravu** notifikaciju (jedan stvarni zadatak) i odlučiti web push vs native app.

---

## Šta je bio glavni problem (rešen)

Slanje je tiho padalo (`poslato: 0`) jer je `webpush/` biblioteka (Minishlink) zahtevala
Composer pakete kojih nema u projektu (`GuzzleHttp\Client`, `Base64Url\Base64Url`,
`Jose\Component\...`). Projekat **nema Composer/vendor** — pa biblioteka nikad nije ni mogla da šalje.

Prava greška (potvrđena na produkciji): `Error: Class "Base64Url\Base64Url" not found`.

**Rešenje:** napisan samostalni sender koji koristi samo PHP ugrađene ekstenzije
(`openssl`, `curl`, `hash_hkdf`) — bez ijedne spoljne biblioteke.

Produkcijsko okruženje (provereno): PHP **8.2.31**, `openssl` ✓, `curl` ✓,
`openssl_pkey_derive()` ✓, `hash_hkdf()` ✓. (`gmp` ne treba.)

---

## Izmenjeni / novi fajlovi (lokalno; treba prebaciti na produkciju)

| Fajl | Status | Šta radi |
|---|---|---|
| `app/Core/PushSender.php` | **NOV** | Samostalno slanje: VAPID ES256 potpis + `aes128gcm` šifrovanje (RFC 8291/8188) + `curl`. Briše pretplatu na HTTP 404/410. |
| `app/Controllers/PushController.php` | izmenjen | `sendToSubscription()` sada koristi `Core\PushSender` umesto mrtve biblioteke; loguje pravi razlog. |
| `app/Views/layout/footer.php` | izmenjen | Pri svakom učitavanju sinhronizuje pretplatu sa serverom (upsert). Ako je stara pretplata sa drugim ključem → obriše je i napravi novu. |
| `sw.js` | izmenjen | Dodat `fetch` handler (uslov za PWA instalaciju), `CACHE_NAME` = `ekosarna-v2`. |
| `push_test.php` | **NOV (privremen)** | Admin test stranica: `…/mvc/push_test.php` (sebi) ili `?uid=N`. Prikazuje broj pretplata, rezultat slanja, PHP okruženje i tačan HTTP status/razlog. |

Postojeći (nepromenjen, koristi se): `app/Models/PushSubscription.php`, `push_cron.php`.

---

## Ključevi / konfiguracija

- **Pravi VAPID ključevi su na produkciji**, u `/home/ekosarna/.env` (iznad `public_html`).
  `Config.php` bira PRVI postojeći `.env` od tri kandidata → na produkciji je to baš taj.
- **Lokalni `.env`** ima placeholder `123` — to je OK, push se testira na produkciji (HTTPS),
  jer ne radi sa `http://localhost`.
- Ikonice: `public/icon-192.png` (crveni „E") i `public/icon-512.png` — postoje i dostupne (HTTP 200).

---

## Otvoreno za sutra (TODO)

1. **Test prave notifikacije** — napraviti jedan stvarni zadatak u aplikaciji i videti da li
   notifikacija stigne čisto (bez „Possible spam"). Pretpostavka: „spam" oznaku je izazvala
   provala identičnih TEST poruka na novoj pretplati, ne pravi sadržaj.
2. **`push_test.php` šalje DUPLO** (jednom kroz `notifyUsers`, jednom u dijagnostici) —
   popraviti da šalje samo jednom (predloženo, NIJE još urađeno).
3. **`sw.js` poliranje** — razmotriti `lang:'sr'`, da li `requireInteraction` ostaje
   (predloženo, NIJE još urađeno).
4. **Odluka: web push vs native Android app (FCM).**
   - „Possible spam" je Chrome-ova funkcija samo za WEB notifikacije; native app je ne dobija.
   - Web push već ide preko FCM-a (`fcm.googleapis.com`); razlika je sajt vs instalirana aplikacija.
   - Native put = više posla (Capacitor/TWA omotač + Firebase + Google Play $25; iOS zaseban + APNs $99/god).
   - Plan ako se ide native: skicirati Capacitor + FCM (reciklira postojeći sajt).
5. **Čišćenje kad sve proradi:** obrisati `push_test.php` i mrtav `webpush/` folder.

---

## Kako testirati (podsetnik)

1. Prebaci izmenjene fajlove na produkciju (čuvaj strukturu foldera).
2. Na telefonu/desktopu otvori `https://ekosarna.com/mvc/?page=home`, hard refresh (Ctrl+F5),
   prihvati „Aktiviraj" baner i dozvolu → u konzoli treba `[Push] Uređaj sačuvan na serveru ✓`.
3. Otvori `https://ekosarna.com/mvc/push_test.php` → očekuje se
   `subscription-a: 1`, `Uspeh: DA | HTTP status: 201`.
4. Log slanja: `logs/push_send.log`. PHP greške idu u `error_log`.

## Napomena (iskreno)

„Possible spam" kontroliše Google ML model na uređaju — **ne postoji header/podešavanje**
kojim se garantuje da se nikad neće prikazati. Smanjuje se: pravim/specifičnim sadržajem,
brendiranom ikonicom, retkim slanjem i realnom upotrebom (klikovi grade reputaciju).
