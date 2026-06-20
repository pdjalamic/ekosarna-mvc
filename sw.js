// Ekošarna Service Worker — Push Notifikacije
const CACHE_NAME = 'ekosarna-v3';

// Instalacija
self.addEventListener('install', function(e) {
  self.skipWaiting();
});

self.addEventListener('activate', function(e) {
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
    requireInteraction: true,
    actions: [
      { action: 'open',   title: 'Otvori zadatak' },
      { action: 'close',  title: 'Zatvori' }
    ]
  };

  e.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Klik na notifikaciju — otvori URL
self.addEventListener('notificationclick', function(e) {
  e.notification.close();

  if (e.action === 'close') return;

  var url = (e.notification.data && e.notification.data.url)
    ? e.notification.data.url
    : '/mvc/?page=zadaci';

  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(function(clientList) {
        // Ako je prozor/tab već otvoren — fokusiraj ga i pošalji mu URL preko
        // postMessage da sama stranica izvrši navigaciju. Ovo je pouzdano i na
        // Androidu, gde client.navigate() često tiho zakaže na PWA prozoru.
        for (var i = 0; i < clientList.length; i++) {
          var client = clientList[i];
          if (client.url.indexOf('/mvc/') !== -1 && 'focus' in client) {
            return client.focus().then(function(c) {
              if (c && c.postMessage) {
                c.postMessage({ type: 'navigate', url: url });
                return c;
              }
              // Rezerva: probaj navigate(), pa otvori novi prozor
              if (c && c.navigate) {
                return c.navigate(url).catch(function() {
                  return clients.openWindow ? clients.openWindow(url) : null;
                });
              }
              return clients.openWindow ? clients.openWindow(url) : null;
            });
          }
        }
        // Nijedan prozor nije otvoren — otvori novi
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});
