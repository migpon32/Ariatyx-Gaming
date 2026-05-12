const gameVersion = new URL(self.location.href).searchParams.get("v") || "v203";
const cacheName = "BulletDrop-PWA-" + gameVersion;
const versionQuery = "?v=" + gameVersion;

const cacheableShellAssets = [
    "/game-play",
    "/launcher",
    "/dashboard",
    "/game/manifest.webmanifest",
    "/game/manifest.webmanifest" + versionQuery,
    "/game/icon-192.png",
    "/game/icon-192.png" + versionQuery,
    "/game/icon-512.png",
    "/game/icon-512.png" + versionQuery,
    "/images/War_Bird.png",
    "/game/TemplateData/style.css" + versionQuery,
    "/game/TemplateData/unity-logo-dark.png",
    "/game/TemplateData/progress-bar-empty-dark.png",
    "/game/TemplateData/progress-bar-full-dark.png",
    "/build/assets/app-CBIXHEl1.css",
    "/build/assets/app-BdKX2mS3.js",
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

const cacheableNavigationPaths = new Set([
    "/game-play",
    "/launcher",
    "/dashboard"
]);

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

function isLocalStaticAsset(pathname) {
    return pathname.startsWith("/build/assets/") ||
        pathname.startsWith("/game/TemplateData/") ||
        pathname.startsWith("/images/");
}

function cacheResponse(cache, request, response) {
    if (!response || !response.ok) {
        return Promise.resolve(response);
    }

    return cache.put(request, response.clone()).then(function () {
        return response;
    });
}

function isExpectedNavigationResponse(response, expectedPathname) {
    if (!response || !response.ok) {
        return false;
    }

    const responseUrl = new URL(response.url || expectedPathname, self.location.origin);

    return responseUrl.origin === self.location.origin &&
        responseUrl.pathname === expectedPathname;
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
                        if (
                            cacheableNavigationPaths.has(assetUrl) &&
                            !isExpectedNavigationResponse(response, assetUrl)
                        ) {
                            return;
                        }

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

    if (requestUrl.pathname === "/ServiceWorker.js") {
        event.respondWith(
            fetch(event.request, {
                cache: "no-store",
                credentials: "same-origin"
            })
        );
        return;
    }

    if (isNavigation && cacheableNavigationPaths.has(requestUrl.pathname)) {
        event.respondWith(
            caches.open(cacheName).then(function (cache) {
                return fetch(event.request, {
                    cache: "no-store",
                    credentials: "same-origin"
                }).then(function (networkResponse) {
                    if (isExpectedNavigationResponse(networkResponse, requestUrl.pathname)) {
                        return cacheResponse(cache, requestUrl.pathname, networkResponse);
                    }

                    return networkResponse;
                }).catch(function () {
                    return cache.match(requestUrl.pathname).then(function (cachedResponse) {
                        return cachedResponse || new Response(
                            "BulletDrop is unavailable offline until this page has been opened once while online.",
                            {
                                status: 503,
                                headers: {
                                    "Content-Type": "text/plain; charset=utf-8"
                                }
                            }
                        );
                    });
                });
            })
        );
        return;
    }

    if (isNavigation) {
        return;
    }

    if (
        isUnityBuildFile(requestUrl.pathname) ||
        isLocalStaticAsset(requestUrl.pathname) ||
        cacheableShellPaths.has(requestUrl.pathname)
    ) {
        event.respondWith(
            caches.open(cacheName).then(function (cache) {
                return cache.match(event.request, {
                    ignoreSearch: true
                }).then(function (cachedResponse) {
                    if (cachedResponse) return cachedResponse;

                    return fetch(event.request, {
                        cache: "reload",
                        credentials: "same-origin"
                    }).then(function (networkResponse) {
                        return cacheResponse(cache, event.request, networkResponse);
                    });
                });
            })
        );
        return;
    }
});
