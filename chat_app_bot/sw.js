// sw.js — Service Worker สำหรับ Web Push Notifications
// เทศบาลนครรังสิต Chat Bot — น้องรังสิตา

const CACHE_NAME = 'rungsit-chat-v1';
const API_BASE   = '/chat_app/chat_app_bot/chat_api.php';

// ── Install & Activate ────────────────────────────
self.addEventListener('install',  () => self.skipWaiting());
self.addEventListener('activate', e  => e.waitUntil(clients.claim()));

// ── Push Event ────────────────────────────────────
self.addEventListener('push', event => {
  const defaultOpts = {
    body:      'มีข้อความใหม่จากน้องรังสิตา',
    icon:      '/chat_app/chat_app_bot/uploads/logo/site_logo.png',
    badge:     '/chat_app/chat_app_bot/uploads/logo/site_logo.png',
    tag:       'rungsit-chat',
    renotify:  true,
    vibrate:   [200, 100, 200],
    data:      { url: self.registration.scope },
  };

  event.waitUntil(
    fetch(API_BASE + '?action=push_notification_data', { credentials: 'include' })
      .then(r => r.ok ? r.json() : null)
      .then(d => {
        const opts = { ...defaultOpts };
        if (d?.message) opts.body = d.message;
        if (d?.sender)  opts.body = d.sender + ': ' + opts.body;
        return self.registration.showNotification('น้องรังสิตา 🏛️', opts);
      })
      .catch(() => self.registration.showNotification('น้องรังสิตา 🏛️', defaultOpts))
  );
});

// ── Notification Click ────────────────────────────
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || self.registration.scope;
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
      for (const c of list) {
        if (c.url.includes('chat_widget') && 'focus' in c) return c.focus();
      }
      return clients.openWindow(targetUrl);
    })
  );
});
