<x-app-layout>
    <x-slot name="title">Play BulletDrop | Ariatyx Gaming</x-slot>

    <x-slot name="favicon">
        {{ asset('images/War_Bird.png') }}
    </x-slot>

    @php
        $gameVersion = 'v205';
        $player = auth()->user();
        $playerName = trim((string) ($player?->username ?: $player?->name ?: $player?->email ?: 'Player'));
        $playerId = $player?->id ?: 'guest';
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
        localStorage.setItem("laravel_user_id", @json((string) $playerId));
        localStorage.setItem("player_name", @json($playerName));
        localStorage.setItem("player_username", @json($playerName));
        localStorage.setItem("player_last_synced_at", new Date().toISOString());

        window.LaravelPlayer = {
            id: localStorage.getItem("laravel_user_id") || localStorage.getItem("player_id") || "guest",
            username: localStorage.getItem("player_username") || localStorage.getItem("player_name") || "Player",
            name: localStorage.getItem("player_name") || localStorage.getItem("player_username") || "Player",
            isLoggedIn: {{ $player ? 'true' : 'false' }}
        };

        console.log("LaravelPlayer Loaded:", window.LaravelPlayer);
    </script>

    <div class="py-12 bg-black min-h-screen flex flex-col items-center justify-center">
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
        window.IsBulletDropOnlineFromJS = function () {
            return navigator.onLine ? "1" : "0";
        };

        window.GetBulletDropNetworkStatusFromJS = function () {
            return navigator.onLine ? "Online" : "Offline";
        };

        function updateNetworkStatusBadge() {
            var networkStatus = document.getElementById("network-status");
            if (!networkStatus) return;

            networkStatus.textContent = navigator.onLine ? "Online" : "Offline";
            networkStatus.hidden = navigator.onLine;
        }

        window.addEventListener("online", updateNetworkStatusBadge);
        window.addEventListener("offline", updateNetworkStatusBadge);
        updateNetworkStatusBadge();

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
            });

            navigator.serviceWorker.addEventListener("controllerchange", function () {
                console.log("PWA Service Worker controller changed.");
            });

            window.addEventListener("online", registerFirebaseServiceWorker);
        }

        function registerFirebaseServiceWorker() {
            if (!navigator.onLine || !("serviceWorker" in navigator)) {
                return Promise.resolve(null);
            }

            if (window.firebaseMessagingServiceWorkerRegistration) {
                return Promise.resolve(window.firebaseMessagingServiceWorkerRegistration);
            }

            if (window.firebaseMessagingServiceWorkerRegistrationPromise) {
                return window.firebaseMessagingServiceWorkerRegistrationPromise;
            }

            window.firebaseMessagingServiceWorkerRegistrationPromise = navigator.serviceWorker.register("/firebase-messaging-sw.js", {
                scope: "/firebase-cloud-messaging-push-scope/"
            }).then(function (registration) {
                console.log("Firebase SW Registered:", registration.scope);
                window.firebaseMessagingServiceWorkerRegistration = registration;
                return registration;
            }).catch(function (error) {
                window.firebaseMessagingServiceWorkerRegistrationPromise = null;
                console.error("Firebase SW Error:", error);
                throw error;
            });

            return window.firebaseMessagingServiceWorkerRegistrationPromise;
        }

        window.GetFirebaseMessagingServiceWorkerRegistration = registerFirebaseServiceWorker;
    </script>

    {{-- Unity Exit Button Bridge --}}
    <script>
        window.BulletDropLauncherUrl = @json(route('launcher'));

        function isBulletDropStandalonePwa() {
            return window.matchMedia("(display-mode: standalone)").matches ||
                window.matchMedia("(display-mode: fullscreen)").matches ||
                window.navigator.standalone === true;
        }

        function navigateToLauncher() {
            window.location.assign(window.BulletDropLauncherUrl);
        }

        window.ExitToLauncher = function () {
            navigateToLauncher();
        };

        window.ExitGame = function () {
            if (!isBulletDropStandalonePwa()) {
                navigateToLauncher();
                return;
            }

            var fallbackTimer = window.setTimeout(navigateToLauncher, 250);

            try {
                window.open("", "_self");
                window.close();
            } catch (error) {
                console.warn("Window close was blocked. Returning to launcher.", error);
            }

            window.setTimeout(function () {
                window.clearTimeout(fallbackTimer);

                if (!document.hidden) {
                    navigateToLauncher();
                }
            }, 350);
        };
    </script>

    {{-- Firebase Scripts --}}
    <script>
        window.FirebaseTokenStoreUrl = @json($player ? route('firebase.token.store') : null);
        window.FirebaseNotificationPermissionRequestedEarly = false;
        window.RequestFirebaseNotificationPermission = window.RequestFirebaseNotificationPermission || async function () {
            window.FirebaseNotificationPermissionRequestedEarly = true;
            window.FirebaseNotificationPermissionNeedsTap = true;
            return null;
        };

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
            if (!navigator.onLine || window.firebaseNotificationsLoaded) return;

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

        loadFirebaseNotifications();
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
            var errorMsg = "Failed to load Unity loader. Check Build file names.";
            console.error(errorMsg);

            if (debugPanel) {
                debugPanel.innerHTML += "<br>" + errorMsg;
            }

            alert(errorMsg);
        };

        document.body.appendChild(script);
    </script>
</x-app-layout>
