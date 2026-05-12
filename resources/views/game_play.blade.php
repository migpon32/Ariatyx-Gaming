<x-app-layout>
    <x-slot name="title">Play BulletDrop | Ariatyx Gaming</x-slot>

    <x-slot name="favicon">
        {{ asset('images/War_Bird.png') }}
    </x-slot>

    @php
        $gameVersion = 'v202';
        $player = auth()->user();
        $playerName = trim((string) ($player?->username ?: $player?->name ?: $player?->email ?: 'Player'));
        $playerId = $player?->id ?: 'guest';
        $offlineAssetUrls = [
            route('game.play'),
            asset('game/manifest.webmanifest') . '?v=' . $gameVersion,
            asset('game/icon-192.png') . '?v=' . $gameVersion,
            asset('game/icon-512.png') . '?v=' . $gameVersion,
            asset('game/TemplateData/style.css') . '?v=' . $gameVersion,
            asset('game/TemplateData/unity-logo-dark.png') . '?v=' . $gameVersion,
            asset('game/TemplateData/progress-bar-empty-dark.png') . '?v=' . $gameVersion,
            asset('game/TemplateData/progress-bar-full-dark.png') . '?v=' . $gameVersion,
            asset('game/Build/BulletDrop.loader.js') . '?v=' . $gameVersion,
            asset('game/Build/BulletDrop.data') . '?v=' . $gameVersion,
            asset('game/Build/BulletDrop.framework.js') . '?v=' . $gameVersion,
            asset('game/Build/BulletDrop.wasm') . '?v=' . $gameVersion,
        ];
    @endphp

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    {{-- PWA / Unity CSS --}}
    <link rel="stylesheet" href="{{ asset('game/TemplateData/style.css') }}?v={{ $gameVersion }}">
    <link rel="manifest" href="{{ asset('game/manifest.webmanifest') }}?v={{ $gameVersion }}">
    <meta name="theme-color" content="#000000">

    {{-- iPhone / iPad support --}}
    <link rel="apple-touch-icon" href="{{ asset('game/icon-192.png') }}?v={{ $gameVersion }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <style>
        .bulletdrop-stage {
            position: relative;
            min-height: 100vh;
        }

        .mode-gate {
            position: fixed;
            inset: 0;
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                linear-gradient(90deg, rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.54)),
                url('{{ asset("images/bulletdropnologo.png") }}') center / cover no-repeat;
        }

        .mode-panel {
            width: min(620px, 100%);
            border: 1px solid rgba(255, 215, 0, 0.36);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.82);
            color: #fff;
            padding: clamp(22px, 4vw, 38px);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.55);
        }

        .mode-kicker {
            color: #ffd700;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .mode-title {
            margin: 8px 0 10px;
            font-family: 'Barlow Condensed', Arial, sans-serif;
            font-size: clamp(38px, 7vw, 68px);
            font-weight: 900;
            line-height: 0.9;
            text-transform: uppercase;
        }

        .mode-copy {
            margin: 0 0 24px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 15px;
            line-height: 1.6;
        }

        .mode-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .mode-button {
            min-height: 112px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
            cursor: pointer;
            padding: 18px;
            text-align: left;
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }

        .mode-button strong {
            display: block;
            margin-bottom: 7px;
            color: #ffd700;
            font-family: 'Barlow Condensed', Arial, sans-serif;
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .mode-button span {
            display: block;
            color: rgba(255, 255, 255, 0.72);
            font-size: 13px;
            line-height: 1.45;
        }

        .mode-button:hover:not(:disabled),
        .mode-button:focus-visible:not(:disabled) {
            transform: translateY(-3px);
            border-color: rgba(255, 215, 0, 0.72);
            background: rgba(255, 215, 0, 0.12);
            outline: none;
        }

        .mode-button:disabled {
            cursor: not-allowed;
            opacity: 0.48;
        }

        .cache-status {
            min-height: 18px;
            margin-top: 18px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 640px) {
            .mode-actions {
                grid-template-columns: 1fr;
            }

            .mode-button {
                min-height: 92px;
            }
        }
    </style>

    {{-- Force remove old BulletDrop cache once per version without touching Firebase SW --}}
    <script>
        const BULLETDROP_VERSION = @json($gameVersion);
        const BULLETDROP_VERSION_STORAGE_KEY = "BulletDropVersion";

        async function cleanupOldBulletDropCaches() {
            const savedVersion = localStorage.getItem(BULLETDROP_VERSION_STORAGE_KEY);

            if (savedVersion === BULLETDROP_VERSION) {
                return;
            }

            const shouldReloadAfterCleanup = savedVersion !== null;

            try {
                if ("caches" in window) {
                    const cacheNames = await caches.keys();

                    await Promise.all(cacheNames.map(function (cacheName) {
                        const normalizedName = cacheName.toLowerCase();
                        const isFirebaseCache = normalizedName.includes("firebase");
                        const isBulletDropCache =
                            normalizedName.includes("bulletdrop") ||
                            normalizedName.includes("unity") ||
                            normalizedName.includes("game");

                        if (isBulletDropCache && !isFirebaseCache) {
                            return caches.delete(cacheName);
                        }
                    }));
                }

                if ("serviceWorker" in navigator) {
                    const registration = await navigator.serviceWorker.getRegistration("/");

                    if (registration && registration.update) {
                        await registration.update();
                    }
                }

                localStorage.setItem(BULLETDROP_VERSION_STORAGE_KEY, BULLETDROP_VERSION);

                if (shouldReloadAfterCleanup) {
                    window.location.reload();
                }
            } catch (error) {
                console.warn("BulletDrop cache cleanup failed:", error);
                localStorage.setItem(BULLETDROP_VERSION_STORAGE_KEY, BULLETDROP_VERSION);
            }
        }

        cleanupOldBulletDropCaches();
    </script>

    {{-- Laravel to Unity Player Bridge --}}
    <script>
        window.GetLaravelUserIdFromJS = function() {
            return @json((string) $playerId);
        };

        window.GetLaravelUsernameFromJS = function() {
            return @json($playerName);
        };

        window.GetPlayerNameFromJS = function() {
            return @json($playerName);
        };

        window.GetLaravelUserId = function() {
            return window.GetLaravelUserIdFromJS();
        };

        window.GetLaravelUsername = function() {
            return window.GetLaravelUsernameFromJS();
        };

        localStorage.setItem("player_id", @json((string) $playerId));
        localStorage.setItem("player_name", @json($playerName));
        localStorage.setItem("player_username", @json($playerName));
        localStorage.setItem("player_last_synced_at", new Date().toISOString());

        window.LaravelPlayer = {
            id: localStorage.getItem("player_id") || "guest",
            username: localStorage.getItem("player_username") || localStorage.getItem("player_name") || "Player",
            name: localStorage.getItem("player_name") || localStorage.getItem("player_username") || "Player",
            isLoggedIn: {{ $player ? 'true' : 'false' }}
        };

        console.log("LaravelPlayer Loaded:", window.LaravelPlayer);
    </script>

    <div id="mode-gate" class="mode-gate">
        <div class="mode-panel">
            <div class="mode-kicker">Choose Session</div>
            <h1 class="mode-title">BulletDrop</h1>
            <p class="mode-copy">
                Online mode connects live services when the network is available. Offline mode starts from the installed game cache and keeps the run local.
            </p>

            <div class="mode-actions">
                <button type="button" class="mode-button" id="play-online-btn">
                    <strong>Play Online</strong>
                    <span>Use live network features, notifications, and online services.</span>
                </button>

                <button type="button" class="mode-button" id="play-offline-btn">
                    <strong>Play Offline</strong>
                    <span>Start locally from the installed BulletDrop files.</span>
                </button>
            </div>

            <div class="cache-status" id="cache-status">Preparing offline files...</div>
        </div>
    </div>

    <div class="bulletdrop-stage py-12 bg-black min-h-screen flex flex-col items-center justify-center">
        <div id="unity-container" class="unity-desktop">
            <canvas id="unity-canvas" width="960" height="600" tabindex="-1"></canvas>

            <div id="unity-loading-bar">
                <div id="unity-logo"></div>
                <div id="unity-progress-bar-empty">
                    <div id="unity-progress-bar-full"></div>
                </div>
            </div>

            <div id="unity-warning"></div>
        </div>
    </div>

    {{-- Debug Panel --}}
    <div id="debug-panel" style="position: fixed; bottom: 10px; left: 10px; background: rgba(0,0,0,0.8); color: #0f0; padding: 8px; font-size: 11px; font-family: monospace; z-index: 9999; border-radius: 5px; pointer-events: none;">
        Loading...
    </div>

    <div id="network-status" style="position: fixed; top: 12px; right: 12px; z-index: 10000; background: rgba(0,0,0,0.82); color: #ffd700; border: 1px solid rgba(255,215,0,0.45); border-radius: 4px; padding: 7px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; pointer-events: none;" hidden>
        Offline
    </div>

    {{-- PWA + Firebase Service Workers --}}
    <script>
        const BULLETDROP_OFFLINE_ASSETS = @json($offlineAssetUrls);
        const BULLETDROP_CACHE_NAME = "BulletDrop-PWA-" + BULLETDROP_VERSION;
        const BULLETDROP_MODE_STORAGE_KEY = "BulletDropPlayMode";
        window.BulletDropPlayMode = null;
        window.BulletDropOfflineReady = false;

        function getEffectiveBulletDropMode() {
            return window.BulletDropPlayMode || "online";
        }

        window.IsBulletDropOnlineFromJS = function () {
            return getEffectiveBulletDropMode() === "online" && navigator.onLine ? "1" : "0";
        };

        window.GetBulletDropNetworkStatusFromJS = function () {
            if (getEffectiveBulletDropMode() === "offline") {
                return "Offline";
            }

            return navigator.onLine ? "Online" : "Offline";
        };

        window.GetBulletDropPlayModeFromJS = function () {
            return getEffectiveBulletDropMode();
        };

        function updateNetworkStatusBadge() {
            var networkStatus = document.getElementById("network-status");
            if (!networkStatus) return;

            var mode = getEffectiveBulletDropMode();
            var isOnlineMode = mode === "online" && navigator.onLine;
            networkStatus.textContent = isOnlineMode ? "Online" : "Offline";
            networkStatus.hidden = isOnlineMode;
        }

        function updateModeButtons() {
            var onlineButton = document.getElementById("play-online-btn");
            if (!onlineButton) return;

            onlineButton.disabled = !navigator.onLine;
            onlineButton.querySelector("span").textContent = navigator.onLine
                ? "Use live network features, notifications, and online services."
                : "Online mode needs an internet connection.";
        }

        async function warmBulletDropOfflineCache() {
            var cacheStatus = document.getElementById("cache-status");

            if (!("caches" in window)) {
                if (cacheStatus) cacheStatus.textContent = "Offline cache is not supported by this browser.";
                return false;
            }

            try {
                var cache = await caches.open(BULLETDROP_CACHE_NAME);
                var cachedCount = 0;

                for (var index = 0; index < BULLETDROP_OFFLINE_ASSETS.length; index++) {
                    var assetUrl = BULLETDROP_OFFLINE_ASSETS[index];
                    var request = new Request(assetUrl, {
                        cache: "reload",
                        credentials: "same-origin"
                    });

                    try {
                        var response = await fetch(request);

                        if (response && response.ok) {
                            await cache.put(request, response.clone());
                            cachedCount++;
                            if (cacheStatus) {
                                cacheStatus.textContent = "Preparing offline files... " + cachedCount + "/" + BULLETDROP_OFFLINE_ASSETS.length;
                            }
                        }
                    } catch (assetError) {
                        var cachedResponse = await cache.match(request);

                        if (cachedResponse) {
                            cachedCount++;
                        } else {
                            console.warn("Offline cache skipped:", assetUrl, assetError);
                        }
                    }
                }

                window.BulletDropOfflineReady = cachedCount === BULLETDROP_OFFLINE_ASSETS.length;

                if (window.BulletDropOfflineReady) {
                    localStorage.setItem("BulletDropOfflineReady", "1");
                    localStorage.setItem("BulletDropOfflineCachedAt", new Date().toISOString());
                }

                if (cacheStatus && window.BulletDropOfflineReady) {
                    cacheStatus.textContent = "Offline files ready.";
                } else if (cacheStatus) {
                    cacheStatus.textContent = "Open online once to finish installing offline files.";
                }

                return window.BulletDropOfflineReady;
            } catch (error) {
                console.warn("BulletDrop offline cache failed:", error);
                if (cacheStatus) cacheStatus.textContent = "Offline files will be available after one complete online load.";
                return false;
            }
        }

        window.addEventListener("online", updateNetworkStatusBadge);
        window.addEventListener("offline", updateNetworkStatusBadge);
        window.addEventListener("online", updateModeButtons);
        window.addEventListener("offline", updateModeButtons);
        updateNetworkStatusBadge();
        updateModeButtons();

        if ("serviceWorker" in navigator) {
            window.addEventListener("load", async function () {
                navigator.serviceWorker.register("/ServiceWorker.js?v=" + BULLETDROP_VERSION, {
                    scope: "/"
                }).then(function (registration) {
                    console.log("PWA Service Worker Registered:", registration.scope);
                    return registration.update();
                }).catch(function (error) {
                    console.error("PWA Service Worker Error:", error);
                });

                registerFirebaseServiceWorker();
                warmBulletDropOfflineCache();
            });

            navigator.serviceWorker.addEventListener("controllerchange", function () {
                console.log("PWA Service Worker controller changed.");
            });

            window.addEventListener("online", registerFirebaseServiceWorker);
        }

        function registerFirebaseServiceWorker() {
            if (window.BulletDropPlayMode !== "online" || !navigator.onLine || !("serviceWorker" in navigator) || window.firebaseServiceWorkerRegistered) {
                return;
            }

            window.firebaseServiceWorkerRegistered = true;

            navigator.serviceWorker.register("/firebase-messaging-sw.js", {
                scope: "/firebase-cloud-messaging-push-scope"
            }).then(function (registration) {
                console.log("Firebase SW Registered:", registration.scope);
            }).catch(function (error) {
                window.firebaseServiceWorkerRegistered = false;
                console.error("Firebase SW Error:", error);
            });
        }
    </script>

    {{-- Unity Exit Button Bridge --}}
    <script>
        function ExitToLauncher() {
            window.location.href = "{{ route('launcher') }}";
        }

        function ExitGame() {
            ExitToLauncher();
        }
    </script>

    {{-- Firebase Scripts --}}
    <script>
        window.RequestFirebaseNotificationPermission = window.RequestFirebaseNotificationPermission || async function () {};

        function loadScript(src) {
            return new Promise(function (resolve, reject) {
                var script = document.createElement("script");
                script.src = src;
                script.async = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        function loadFirebaseNotifications() {
            if (window.BulletDropPlayMode !== "online" || !navigator.onLine || window.firebaseNotificationsLoaded) return;

            window.firebaseNotificationsLoaded = true;

            loadScript("https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js")
                .then(function () {
                    return loadScript("https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js");
                })
                .then(function () {
                    return loadScript("{{ asset('game/firebase-notification.js') }}?v={{ $gameVersion }}");
                })
                .catch(function (error) {
                    window.firebaseNotificationsLoaded = false;
                    console.warn("Firebase notifications are unavailable right now.", error);
                });
        }

        window.addEventListener("load", loadFirebaseNotifications);
        window.addEventListener("online", loadFirebaseNotifications);
    </script>

    {{-- Unity WebGL Loader --}}
    <script>
        var container = document.querySelector("#unity-container");
        var canvas = document.querySelector("#unity-canvas");
        var loadingBar = document.querySelector("#unity-loading-bar");
        var progressBarFull = document.querySelector("#unity-progress-bar-full");
        var warningBanner = document.querySelector("#unity-warning");
        var debugPanel = document.querySelector("#debug-panel");

        function unityShowBanner(msg, type) {
            function updateBannerVisibility() {
                warningBanner.style.display = warningBanner.children.length ? "block" : "none";
            }

            var div = document.createElement("div");
            div.innerHTML = msg;
            warningBanner.appendChild(div);

            if (type === "error") {
                div.style = "background: red; padding: 10px;";
            } else {
                if (type === "warning") {
                    div.style = "background: yellow; padding: 10px;";
                }

                setTimeout(function () {
                    if (warningBanner.contains(div)) {
                        warningBanner.removeChild(div);
                    }
                    updateBannerVisibility();
                }, 5000);
            }

            updateBannerVisibility();
        }

        function getDebugHtml(unityStatus) {
            var userId = window.GetLaravelUserIdFromJS ? window.GetLaravelUserIdFromJS() : "unknown";
            var username = window.GetLaravelUsernameFromJS ? window.GetLaravelUsernameFromJS() : "unknown";

            return "User: " + username +
                "<br>ID: " + userId +
                "<br>Unity: " + unityStatus +
                "<br>Mode: " + getEffectiveBulletDropMode() +
                "<br>Network: " + window.GetBulletDropNetworkStatusFromJS() +
                "<br>Version: " + BULLETDROP_VERSION;
        }

        function updateDebugPanel() {
            if (debugPanel) {
                debugPanel.innerHTML = getDebugHtml("Loading...");
            }
        }

        updateDebugPanel();

        var buildUrl = "{{ asset('game/Build') }}";
        var loaderUrl = buildUrl + "/BulletDrop.loader.js?v=" + BULLETDROP_VERSION;

        var config = {
            dataUrl: buildUrl + "/BulletDrop.data?v=" + BULLETDROP_VERSION,
            frameworkUrl: buildUrl + "/BulletDrop.framework.js?v=" + BULLETDROP_VERSION,
            codeUrl: buildUrl + "/BulletDrop.wasm?v=" + BULLETDROP_VERSION,
            streamingAssetsUrl: "{{ asset('game/StreamingAssets') }}",
            companyName: "Ariatyx Gaming",
            productName: "BulletDrop",
            productVersion: BULLETDROP_VERSION,
            showBanner: unityShowBanner
        };

        if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
            var meta = document.createElement("meta");
            meta.name = "viewport";
            meta.content = "width=device-width, height=device-height, initial-scale=1.0, user-scalable=no, shrink-to-fit=yes";
            document.getElementsByTagName("head")[0].appendChild(meta);

            container.className = "unity-mobile";
            canvas.className = "unity-mobile";
        }

        loadingBar.style.display = "none";

        function chooseBulletDropMode(mode) {
            if (window.unityInstance || window.BulletDropUnityStarting) return;

            if (mode === "online" && !navigator.onLine) {
                updateModeButtons();
                return;
            }

            window.BulletDropPlayMode = mode;
            localStorage.setItem(BULLETDROP_MODE_STORAGE_KEY, mode);
            updateNetworkStatusBadge();
            updateDebugPanel();

            var modeGate = document.getElementById("mode-gate");
            if (modeGate) {
                modeGate.style.display = "none";
            }

            if (mode === "online") {
                registerFirebaseServiceWorker();
                loadFirebaseNotifications();
            }

            startUnity();
        }

        function startUnity() {
            window.BulletDropUnityStarting = true;
            loadingBar.style.display = "block";

            var script = document.createElement("script");
            script.src = loaderUrl;

            script.onload = function () {
                createUnityInstance(canvas, config, function (progress) {
                    progressBarFull.style.width = (100 * progress) + "%";
                }).then(function (unityInstance) {
                    window.unityInstance = unityInstance;
                    loadingBar.style.display = "none";

                    if (debugPanel) {
                        debugPanel.innerHTML = getDebugHtml("Loaded");

                        setTimeout(function() {
                            debugPanel.style.opacity = "0.5";
                        }, 5000);
                    }
                }).catch(function (message) {
                    console.error(message);

                    if (debugPanel) {
                        debugPanel.innerHTML += "<br>Error: " + String(message).substring(0, 80);
                    }

                    alert(message);
                });
            };

            script.onerror = function () {
                var errorMsg = "Failed to load Unity loader. Open online once to install the latest BulletDrop files.";
                console.error(errorMsg);

                if (debugPanel) {
                    debugPanel.innerHTML += "<br>" + errorMsg;
                }

                alert(errorMsg);
            };

            document.body.appendChild(script);
        }

        document.getElementById("play-online-btn").addEventListener("click", function () {
            chooseBulletDropMode("online");
        });

        document.getElementById("play-offline-btn").addEventListener("click", function () {
            chooseBulletDropMode("offline");
        });
    </script>
</x-app-layout>
