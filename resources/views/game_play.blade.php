<x-app-layout>
    <x-slot name="title">Play BulletDrop | Ariatyx Gaming</x-slot>

    <x-slot name="favicon">
        {{ asset('images/War_Bird.png') }}
    </x-slot>

    @php
        $gameVersion = 'v201';
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

    {{-- Force remove old Unity/PWA cache once per version --}}
    <script>
        const BULLETDROP_VERSION = "{{ $gameVersion }}";

        async function forceUpdateOldBulletDropCache() {
            if (!("serviceWorker" in navigator)) return;

            const savedVersion = localStorage.getItem("BulletDropVersion");

            if (savedVersion !== BULLETDROP_VERSION) {
                try {
                    const registrations = await navigator.serviceWorker.getRegistrations();

                    for (const registration of registrations) {
                        await registration.unregister();
                    }

                    if ("caches" in window) {
                        const cacheNames = await caches.keys();

                        for (const name of cacheNames) {
                            if (
                                name.includes("BulletDrop") ||
                                name.includes("PWA") ||
                                name.includes("unity") ||
                                name.includes("game")
                            ) {
                                await caches.delete(name);
                            }
                        }
                    }

                    localStorage.setItem("BulletDropVersion", BULLETDROP_VERSION);
                    window.location.reload();
                } catch (error) {
                    console.warn("Cache cleanup failed:", error);
                    localStorage.setItem("BulletDropVersion", BULLETDROP_VERSION);
                }
            }
        }

        forceUpdateOldBulletDropCache();
    </script>

    {{-- Laravel to Unity Player Bridge --}}
    <script>
        @php
            $player = auth()->user();
            $playerName = trim((string) ($player?->username ?: $player?->name ?: $player?->email ?: 'Player'));
            $playerId = $player?->id ?: 'guest';
        @endphp

        window.GetLaravelUserIdFromJS = function() {
            return '{{ $playerId }}';
        };

        window.GetLaravelUsernameFromJS = function() {
            return '{{ addslashes($playerName) }}';
        };

        window.GetPlayerNameFromJS = function() {
            return '{{ addslashes($playerName) }}';
        };

        window.GetLaravelUserId = function() {
            return window.GetLaravelUserIdFromJS();
        };

        window.GetLaravelUsername = function() {
            return window.GetLaravelUsernameFromJS();
        };

        localStorage.setItem("player_id", '{{ $playerId }}');
        localStorage.setItem("player_name", '{{ addslashes($playerName) }}');
        localStorage.setItem("player_username", '{{ addslashes($playerName) }}');
        localStorage.setItem("player_last_synced_at", new Date().toISOString());

        window.LaravelPlayer = {
            id: localStorage.getItem("player_id") || "guest",
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
                navigator.serviceWorker.register("/ServiceWorker.js?v={{ $gameVersion }}", {
                    scope: "/"
                }).then(function (registration) {
                    console.log("PWA Service Worker Registered:", registration.scope);
                }).catch(function (error) {
                    console.error("PWA Service Worker Error:", error);
                });

                registerFirebaseServiceWorker();
            });

            window.addEventListener("online", registerFirebaseServiceWorker);
        }

        function registerFirebaseServiceWorker() {
            if (!navigator.onLine || !("serviceWorker" in navigator) || window.firebaseServiceWorkerRegistered) {
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

        function updateDebugPanel() {
            if (debugPanel) {
                var userId = window.GetLaravelUserIdFromJS ? window.GetLaravelUserIdFromJS() : "unknown";
                var username = window.GetLaravelUsernameFromJS ? window.GetLaravelUsernameFromJS() : "unknown";
                debugPanel.innerHTML = `👤 User: ${username}<br>🆔 ID: ${userId}<br>🎮 Unity: Loading...<br>🧩 Version: {{ $gameVersion }}`;
            }
        }

        updateDebugPanel();

        var gameVersion = "{{ $gameVersion }}";
        var buildUrl = "{{ asset('game/Build') }}";
        var loaderUrl = buildUrl + "/BulletDrop.loader.js?v=" + gameVersion;

        var config = {
            dataUrl: buildUrl + "/BulletDrop.data?v=" + gameVersion,
            frameworkUrl: buildUrl + "/BulletDrop.framework.js?v=" + gameVersion,
            codeUrl: buildUrl + "/BulletDrop.wasm?v=" + gameVersion,
            streamingAssetsUrl: "{{ asset('game/StreamingAssets') }}",
            companyName: "Ariatyx Gaming",
            productName: "BulletDrop",
            productVersion: gameVersion,
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
                    var userId = window.GetLaravelUserIdFromJS ? window.GetLaravelUserIdFromJS() : "unknown";
                    var username = window.GetLaravelUsernameFromJS ? window.GetLaravelUsernameFromJS() : "unknown";
                    debugPanel.innerHTML = `👤 User: ${username}<br>🆔 ID: ${userId}<br>🎮 Unity: Loaded ✓<br>🧩 Version: {{ $gameVersion }}`;

                    setTimeout(function() {
                        debugPanel.style.opacity = "0.5";
                    }, 5000);
                }
            }).catch(function (message) {
                console.error(message);

                if (debugPanel) {
                    debugPanel.innerHTML += `<br>❌ Error: ${message.substring(0, 50)}`;
                }

                alert(message);
            });
        };

        script.onerror = function () {
            var errorMsg = "Failed to load Unity loader. Check Build file names.";
            console.error(errorMsg);

            if (debugPanel) {
                debugPanel.innerHTML += `<br>❌ ${errorMsg}`;
            }

            alert(errorMsg);
        };

        document.body.appendChild(script);
    </script>
</x-app-layout>