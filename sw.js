// Ekošarna Service Worker — Push Notifikacije
const CACHE_NAME = 'ekosarna-v7';

// Instalacija
self.addEventListener('install', function(e) {
  self.skipWaiting();
});

self.addEventListener('activate', function(e) {
  console.log('[SW] Aktivan:', CACHE_NAME);
  e.waitUntil(clients.claim());
});

// Fetch handler (pass-through) — neophodan da bi Chrome dozvolio instalaciju PWA
self.addEventListener('fetch', function(e) {
  // Ne presrećemo ništa; samo prisustvo ovog handlera je uslov za instalaciju.
  return;
});

// Prima push notifikaciju
self.addEventListener('push', function(e) {
  if (!e.data) return;

  var data = e.data.json();
  var title   = data.title   || 'Ekošarna';
  var body    = data.body    || '';
  var url     = data.url     || '/mvc/';
  var icon    = data.icon    || '/mvc/public/icon-192.png';
  var badge   = data.badge   || '/mvc/public/badge-72.png';
  // Jedinstven tag po notifikaciji — inače Android tiho zameni prethodnu
  // (koja zbog requireInteraction ostaje u traci) umesto da iskoči gore.
  var tag     = data.tag     || ('ekosarna-' + Date.now());

  var options = {
    body:    body,
    icon:    icon,
    badge:   badge,
    tag:     tag,
    renotify: true,
    data:    { url: url },
    vibrate: [200, 100, 200],
    // requireInteraction:false + bez action-dugmića → cela notifikacija je jedan
    // klik-cilj (telo), što je na Androidu najpouzdanije za otvaranje app-a,
    // i lakše iskoči kao baner (heads-up).
    requireInteraction: false
  };

  e.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Klik na notifikaciju — otvori URL
self.addEventListener('notificationclick', function(e) {
  e.notification.close();

  if (e.action === 'close') return;

  var raw = (e.notification.data && e.notification.data.url)
    ? e.notification.data.url
    : '/mvc/?page=zadaci';

  // Ignoriši šemu/host koje je server upisao (može biti http:// iza proksija) i
  // uvek otvori na trenutnom https originu — uzmi samo putanju + query.
  var url = raw;
  try { var u = new URL(raw, self.location.origin); url = u.pathname + u.search + u.hash; } catch (err) {}

  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(function(clientList) {
        // Ako je prozor/tab već otvoren — pošalji mu URL preko postMessage PRE
        // fokusa (ako focus() na Androidu tiho zakaže, navigacija je već poslata),
        // pa fokusiraj. Ako i fokus padne — otvori novi prozor kao rezervu.
        for (var i = 0; i < clientList.length; i++) {
          var client = clientList[i];
          if (client.url.indexOf('/mvc/') !== -1) {
            try { client.postMessage({ type: 'navigate', url: url }); } catch (err) {}
            if ('focus' in client) {
              return client.focus().catch(function() {
                return clients.openWindow ? clients.openWindow(url) : null;
              });
            }
            return client;
          }
        }
        // Nijedan prozor nije otvoren — otvori novi (svež start pročita ?openz=)
        return clients.openWindow ? clients.openWindow(url) : null;
      })
  );
});
