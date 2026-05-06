const cacheName = "DefaultCompany-BulletDrop-1.0";
const contentToCache = [
    "Build/BulletDrop.loader.js",
    "Build/BulletDrop.framework.js",
    "Build/BulletDrop.data",
    "Build/BulletDrop.wasm",
    "TemplateData/style.css"
];

self.addEventListener("install", function (e) {
    console.log("[Service Worker] Install");

    e.waitUntil((async function () {
        const cache = await caches.open(cacheName);
        console.log("[Service Worker] Caching Unity files");
        await cache.addAll(contentToCache);
    })());
});

self.addEventListener("fetch", function (e) {
    e.respondWith((async function () {
        let response = await caches.match(e.request);

        if (response) {
            return response;
        }

        response = await fetch(e.request);

        const cache = await caches.open(cacheName);
        cache.put(e.request, response.clone());

        return response;
    })());
});