const gameVersion = new URL(self.location.href).searchParams.get("v") || "v202";
const cacheName = "BulletDrop-PWA-" + gameVersion;
const versionQuery = "?v=" + gameVersion;

const cacheableShellAssets = [
    "/game/manifest.webmanifest" + versionQuery,
    "/game/icon-192.png" + versionQuery,
    "/game/icon-512.png" + versionQuery,
    "/game/TemplateData/style.css" + versionQuery,
    "/game/TemplateData/unity-logo-dark.png",
    "/game/TemplateData/progress-bar-empty-dark.png",
    "/game/TemplateData/progress-bar-full-dark.png"
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
});

self.addEventListener("fetch", function (event) {
    if (event.request.method !== "GET") return;

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const acceptHeader = event.request.headers.get("accept") || "";
    const isNavigation = event.request.mode === "navigate" || acceptHeader.includes("text/html");
    const shouldNeverCache =
        isNavigation ||
        requestUrl.pathname === "/game-play" ||
        requestUrl.pathname === "/ServiceWorker.js" ||
        isUnityBuildFile(requestUrl.pathname);

    if (shouldNeverCache) {
        event.respondWith(
            fetch(event.request, {
                cache: "no-store",
                credentials: "same-origin"
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
