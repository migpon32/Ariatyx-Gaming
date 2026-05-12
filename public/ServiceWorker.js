const gameVersion = new URL(self.location.href).searchParams.get("v") || "v202";
const cacheName = "BulletDrop-PWA-" + gameVersion;
const versionQuery = "?v=" + gameVersion;

const cacheableShellAssets = [
    "/game-play",
    "/game/manifest.webmanifest" + versionQuery,
    "/game/icon-192.png" + versionQuery,
    "/game/icon-512.png" + versionQuery,
    "/game/TemplateData/style.css" + versionQuery,
    "/game/TemplateData/unity-logo-dark.png" + versionQuery,
    "/game/TemplateData/progress-bar-empty-dark.png" + versionQuery,
    "/game/TemplateData/progress-bar-full-dark.png" + versionQuery
];

const offlineGameAssets = [
    ...cacheableShellAssets,
    "/game/Build/BulletDrop.loader.js" + versionQuery,
    "/game/Build/BulletDrop.data" + versionQuery,
    "/game/Build/BulletDrop.framework.js" + versionQuery,
    "/game/Build/BulletDrop.wasm" + versionQuery
];

const cacheableShellPaths = new Set(
    cacheableShellAssets.map(function (assetUrl) {
        return new URL(assetUrl, self.location.origin).pathname;
    })
);

function isBulletDropCache(cacheKey) {
    const normalizedKey = cacheKey.toLowerCase();

    return !normalizedKey.includes("firebase") && (
        normalizedKey.includes("bulletdrop") ||
        normalizedKey.includes("unity") ||
        normalizedKey.includes("game")
    );
}

function isUnityBuildFile(pathname) {
    return pathname.startsWith("/game/Build/") ||
        pathname.endsWith(".data") ||
        pathname.endsWith(".wasm") ||
        pathname.endsWith(".framework.js") ||
        pathname.endsWith(".loader.js");
}

self.addEventListener("install", function (event) {
    console.log("[Service Worker] Installing BulletDrop version:", gameVersion);
    self.skipWaiting();

    event.waitUntil(
        caches.open(cacheName).then(function (cache) {
            return Promise.all(
                cacheableShellAssets.map(function (assetUrl) {
                    return fetch(assetUrl, {
                        cache: "reload",
                        credentials: "same-origin"
                    }).then(function (response) {
                        if (!response || !response.ok) return;
                        return cache.put(assetUrl, response);
                    }).catch(function (error) {
                        console.warn("[Service Worker] Failed to cache:", assetUrl, error);
                    });
                })
            );
        })
    );
});

self.addEventListener("activate", function (event) {
    console.log("[Service Worker] Activating BulletDrop version:", gameVersion);

    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.map(function (key) {
                    if (key !== cacheName && isBulletDropCache(key)) {
                        console.log("[Service Worker] Deleting old BulletDrop cache:", key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener("message", function (event) {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }

    if (event.data && event.data.type === "CACHE_BULLETDROP_OFFLINE") {
        const assets = Array.isArray(event.data.assets) && event.data.assets.length
            ? event.data.assets
            : offlineGameAssets;

        event.waitUntil(
            caches.open(cacheName).then(function (cache) {
                return Promise.all(
                    assets.map(function (assetUrl) {
                        return fetch(assetUrl, {
                            cache: "reload",
                            credentials: "same-origin"
                        }).then(function (response) {
                            if (!response || !response.ok) return;
                            return cache.put(assetUrl, response);
                        }).catch(function (error) {
                            console.warn("[Service Worker] Failed to warm offline cache:", assetUrl, error);
                        });
                    })
                );
            })
        );
    }
});

self.addEventListener("fetch", function (event) {
    if (event.request.method !== "GET") return;

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const acceptHeader = event.request.headers.get("accept") || "";
    const isNavigation = event.request.mode === "navigate" || acceptHeader.includes("text/html");
    const isGamePlayNavigation = isNavigation && requestUrl.pathname === "/game-play";
    const shouldNeverCache =
        requestUrl.pathname === "/ServiceWorker.js" ||
        (isNavigation && !isGamePlayNavigation);

    if (shouldNeverCache) {
        event.respondWith(
            fetch(event.request, {
                cache: "no-store",
                credentials: "same-origin"
            })
        );
        return;
    }

    if (isGamePlayNavigation) {
        event.respondWith(
            fetch(event.request, {
                cache: "no-store",
                credentials: "same-origin"
            }).then(function (networkResponse) {
                if (!networkResponse || !networkResponse.ok) {
                    return networkResponse;
                }

                return caches.open(cacheName).then(function (cache) {
                    cache.put("/game-play", networkResponse.clone());
                    return networkResponse;
                });
            }).catch(function () {
                return caches.match("/game-play", {
                    ignoreSearch: true
                });
            })
        );
        return;
    }

    if (isUnityBuildFile(requestUrl.pathname)) {
        event.respondWith(
            caches.match(event.request).then(function (cachedResponse) {
                if (cachedResponse) return cachedResponse;

                return fetch(event.request, {
                    cache: "reload",
                    credentials: "same-origin"
                }).then(function (networkResponse) {
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
        return;
    }

    if (!cacheableShellPaths.has(requestUrl.pathname)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function (cachedResponse) {
            if (cachedResponse) return cachedResponse;

            return fetch(event.request, {
                cache: "reload",
                credentials: "same-origin"
            }).then(function (networkResponse) {
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
