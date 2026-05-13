importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyD4QZ4jHI_TvxzHdEtTVoz_9-dOxoaCquw",
    authDomain: "push-notif-49af1.firebaseapp.com",
    projectId: "push-notif-49af1",
    storageBucket: "push-notif-49af1.firebasestorage.app",
    messagingSenderId: "409604966307",
    appId: "1:409604966307:web:803d07cdc6435bfe20bc16",
    measurementId: "G-BGRYMT1KW2"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    const notification = payload.notification || {};
    const data = payload.data || {};
    const title = notification.title || data.title || "Ariatyx Gaming";

    self.registration.showNotification(title, {
        body: notification.body || data.body || "You have a new BulletDrop notification.",
        icon: notification.icon || "/game/icon-192.png",
        badge: "/game/icon-192.png",
        data: {
            url: data.url || "/launcher",
            ...data
        }
    });
});

self.addEventListener("notificationclick", function (event) {
    event.notification.close();

    const targetUrl = new URL(event.notification.data?.url || "/launcher", self.location.origin).href;

    event.waitUntil(
        clients.matchAll({
            type: "window",
            includeUncontrolled: true
        }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url === targetUrl && "focus" in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
