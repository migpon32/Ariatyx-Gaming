const cacheName = "BulletDrop-PWA-v2";

const contentToCache = [
    "./",
    "./Build/BulletDrop.loader.js",
    "./Build/BulletDrop.framework.js",
    "./Build/BulletDrop.data",
    "./Build/BulletDrop.wasm",
    "./TemplateData/style.css",
    "./manifest.webmanifest",
    "./icon-192.png",
    "./icon-512.png"
];

self.addEventListener("install", function (event) {
    console.log("[Service Worker] Installing...");

    event.waitUntil(
        caches.open(cacheName)
            .then(function (cache) {
                console.log("[Service Worker] Caching app files...");
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
                        console.log("[Service Worker] Removing old cache:", key);
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
    if (event.request.method !== "GET") {
        return;
    }

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
                    console.log("[Service Worker] Offline and file not cached:", event.request.url);
                });
        })
    );
});