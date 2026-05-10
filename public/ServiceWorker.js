const cacheName = "BulletDrop-PWA-v22";
const gamePageUrl = "/game-play";

const contentToCache = [
    gamePageUrl,

    "/game/manifest.webmanifest",
    "/game/icon-192.png",
    "/game/icon-512.png",

    "/game/TemplateData/style.css",
    "/game/TemplateData/unity-logo-dark.png",
    "/game/TemplateData/progress-bar-empty-dark.png",
    "/game/TemplateData/progress-bar-full-dark.png",

    "/game/Build/BulletDrop.loader.js",
    "/game/Build/BulletDrop.framework.js",
    "/game/Build/BulletDrop.data",
    "/game/Build/BulletDrop.wasm",

    "/build/assets/app-BdKX2mS3.js",
    "/build/assets/app-CBIXHEl1.css"
];

self.addEventListener("install", function (event) {
    console.log("[Service Worker] Installing...");

    event.waitUntil(
        caches.open(cacheName)
            .then(function (cache) {
                return Promise.all(
                    contentToCache.map(function (url) {
                        return fetch(url, {
                            cache: "reload",
                            credentials: "same-origin"
                        }).then(function (response) {
                            if (!response.ok || (url === gamePageUrl && response.redirected)) {
                                return;
                            }

                            return cache.put(url, response);
                        }).catch(function (error) {
                            console.warn("[Service Worker] Failed to cache:", url, error);
                        });
                    })
                );
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

    const requestUrl = new URL(event.request.url);
    const acceptHeader = event.request.headers.get("accept") || "";
    const acceptsHtml = acceptHeader.includes("text/html");
    const isNavigation = event.request.mode === "navigate" || acceptsHtml;
    const isGamePageRequest = requestUrl.origin === self.location.origin && requestUrl.pathname === gamePageUrl;

    if (isNavigation) {
        if (!isGamePageRequest) {
            event.respondWith(fetch(event.request));
            return;
        }

        event.respondWith(
            fetch(event.request)
                .then(function (networkResponse) {
                    if (networkResponse.ok && !networkResponse.redirected) {
                        return caches.open(cacheName).then(function (cache) {
                            cache.put(gamePageUrl, networkResponse.clone());
                            return networkResponse;
                        });
                    }

                    return networkResponse;
                })
                .catch(function () {
                    return caches.match(gamePageUrl).then(function (cachedResponse) {
                        return cachedResponse || Response.error();
                    });
                })
        );
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
                    return caches.match(event.request).then(function (cachedResponse) {
                        return cachedResponse || Response.error();
                    });
                });
        })
    );
});
