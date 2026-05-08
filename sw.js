// Ekošarna Service Worker — Push Notifikacije
const CACHE_NAME = 'ekosarna-v1';

// Instalacija
self.addEventListener('install', function(e) {
  self.skipWaiting();
});

self.addEventListener('activate', function(e) {
  e.waitUntil(clients.claim());
});

// Prima push notifikaciju
self.addEventListener('push', function(e) {
  if (!e.data) return;

  var data = e.data.json();
  var title   = data.title   || 'Ekošarna';
  var body    = data.body    || '';
  var url     = data.url     || '/mvc/';
  var icon    = data.icon    || '/mvc/public/icon-192.png';
  var badge   = data.badge   || '/mvc/public/icon-192.png';
  var tag     = data.tag     || 'ekosarna-notif';

  var options = {
    body:    body,
    icon:    icon,
    badge:   badge,
    tag:     tag,
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
        // Ako je tab već otvoren — fokusiraj ga
        for (var i = 0; i < clientList.length; i++) {
          var client = clientList[i];
          if (client.url.indexOf('/mvc/') !== -1 && 'focus' in client) {
            client.focus();
            client.navigate(url);
            return;
          }
        }
        // Inače otvori novi tab
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});
