const cacheName = "BulletDrop-PWA-v3";

const contentToCache = [
    "/",
    "/game-play",

    "/game/manifest.webmanifest",
    "/game/icon-192.png",
    "/game/icon-512.png",

    "/game/Build/BulletDrop.loader.js",
    "/game/Build/BulletDrop.framework.js",
    "/game/Build/BulletDrop.data",
    "/game/Build/BulletDrop.wasm",

    "/game/TemplateData/style.css"
];

self.addEventListener("install", function (event) {
    console.log("[Service Worker] Installing...");

    event.waitUntil(
        caches.open(cacheName)
            .then(function (cache) {
                return cache.addAll(contentToCache);
            })
            .then(function () {
                return self.skipWaiting();
            })
    );
});

self.addEventListener("activate", function (event) {
    console.log("[Service Worker] Activating...");

    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.map(function (key) {
                    if (key !== cacheName) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener("fetch", function (event) {
    if (event.request.method !== "GET") return;

    event.respondWith(
        caches.match(event.request).then(function (cachedResponse) {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request)
                .then(function (networkResponse) {
                    return caches.open(cacheName).then(function (cache) {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                })
                .catch(function () {
                    return caches.match("/game-play");
                });
        })
    );
});