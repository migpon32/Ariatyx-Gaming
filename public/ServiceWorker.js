const cacheName = "BulletDrop-PWA-v100";
const gamePageUrl = "/game-play";

const contentToCache = [
    "/game/manifest.webmanifest",
    "/game/icon-192.png",
    "/game/icon-512.png",

    "/game/TemplateData/style.css",
    "/game/TemplateData/unity-logo-dark.png",
    "/game/TemplateData/progress-bar-empty-dark.png",
    "/game/TemplateData/progress-bar-full-dark.png"
];

self.addEventListener("install", function (event) {
    console.log("[Service Worker] Installing new version:", cacheName);
    self.skipWaiting();

    event.waitUntil(
        caches.open(cacheName).then(function (cache) {
            return Promise.all(
                contentToCache.map(function (url) {
                    return fetch(url, {
                        cache: "reload",
                        credentials: "same-origin"
                    })
                    .then(function (response) {
                        if (!response.ok) return;
                        return cache.put(url, response);
                    })
                    .catch(function (error) {
                        console.warn("[Service Worker] Failed to cache:", url, error);
                    });
                })
            );
        })
    );
});

self.addEventListener("activate", function (event) {
    console.log("[Service Worker] Activating:", cacheName);

    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.map(function (key) {
                    if (key !== cacheName) {
                        console.log("[Service Worker] Deleting old cache:", key);
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

    const requestUrl = new URL(event.request.url);
    const acceptHeader = event.request.headers.get("accept") || "";
    const isNavigation = event.request.mode === "navigate" || acceptHeader.includes("text/html");

    const isUnityBuildFile =
        requestUrl.pathname.startsWith("/game/Build/") ||
        requestUrl.pathname.endsWith(".data") ||
        requestUrl.pathname.endsWith(".wasm") ||
        requestUrl.pathname.endsWith(".framework.js") ||
        requestUrl.pathname.endsWith(".loader.js");

    if (isNavigation || isUnityBuildFile) {
        event.respondWith(
            fetch(event.request, {
                cache: "no-store",
                credentials: "same-origin"
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function (cachedResponse) {
            if (cachedResponse) return cachedResponse;

            return fetch(event.request).then(function (networkResponse) {
                if (!networkResponse || !networkResponse.ok) {
                    return networkResponse;
                }

                return caches.open(cacheName).then(function (cache) {
                    cache.put(event.request, networkResponse.clone());
                    return networkResponse;
                });
            });
        })
    );
});