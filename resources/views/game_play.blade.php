<x-app-layout>
    <x-slot name="title">Play BulletDrop | Ariatyx Gaming</x-slot>

    <x-slot name="favicon">
        {{ asset('images/War_Bird.png') }}
    </x-slot>

    {{-- PWA / Unity CSS --}}
    <link rel="stylesheet" href="{{ asset('game/TemplateData/style.css') }}">
    <link rel="manifest" href="{{ asset('game/manifest.webmanifest') }}">
    <meta name="theme-color" content="#000000">

    {{-- iPhone / iPad support --}}
    <link rel="apple-touch-icon" href="{{ asset('game/icon-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    {{-- Laravel to Unity Player Bridge --}}
    <script>
        const serverPlayer = {
            id: @json(auth()->id()),
            username: @json(auth()->check() ? (auth()->user()->username ?? auth()->user()->name) : null),
            name: @json(auth()->check() ? (auth()->user()->username ?? auth()->user()->name) : null)
        };

        const hasServerPlayer = Boolean(serverPlayer.id && (serverPlayer.username || serverPlayer.name));

        if (hasServerPlayer) {
            const accountName = String(serverPlayer.username || serverPlayer.name);

            localStorage.setItem("player_id", String(serverPlayer.id));
            localStorage.setItem("player_name", accountName);
            localStorage.setItem("player_username", accountName);
            localStorage.setItem("player_last_synced_at", new Date().toISOString());
        } else {
            if (!localStorage.getItem("player_id")) {
                localStorage.setItem("player_id", "guest");
            }

            if (!localStorage.getItem("player_name")) {
                localStorage.setItem("player_name", "Player");
            }

            if (!localStorage.getItem("player_username")) {
                localStorage.setItem("player_username", localStorage.getItem("player_name") || "Player");
            }
        }

        window.LaravelPlayer = {
            id: localStorage.getItem("player_id") || "guest",
            username: localStorage.getItem("player_username") || localStorage.getItem("player_name") || "Player",
            name: localStorage.getItem("player_name") || localStorage.getItem("player_username") || "Player",
            isLoggedIn: hasServerPlayer
        };

        function GetPlayerNameFromJS() {
            return localStorage.getItem("player_name") || "Player";
        }

        function GetLaravelUserIdFromJS() {
            return localStorage.getItem("player_id") || "guest";
        }

        function GetLaravelUsernameFromJS() {
            return localStorage.getItem("player_username") || localStorage.getItem("player_name") || "Player";
        }

        function GetLaravelUserId() {
            return GetLaravelUserIdFromJS();
        }

        function GetLaravelUsername() {
            return GetLaravelUsernameFromJS();
        }

        console.log("LaravelPlayer:", window.LaravelPlayer);
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

    {{-- Correct PWA + Firebase Service Workers --}}
    <script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", async function () {
                navigator.serviceWorker.register("/ServiceWorker.js", {
                    scope: "/"
                }).then(function (registration) {
                    console.log("PWA Service Worker Registered:", registration.scope);
                }).catch(function (error) {
                    console.error("PWA Service Worker Error:", error);
                });

                navigator.serviceWorker.register("/firebase-messaging-sw.js", {
                    scope: "/firebase-cloud-messaging-push-scope"
                }).then(function (registration) {
                    console.log("Firebase SW Registered:", registration.scope);
                }).catch(function (error) {
                    console.error("Firebase SW Error:", error);
                });
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
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js"></script>
    <script src="{{ asset('game/firebase-notification.js') }}"></script>

    {{-- Unity WebGL Loader --}}
    <script>
        var container = document.querySelector("#unity-container");
        var canvas = document.querySelector("#unity-canvas");
        var loadingBar = document.querySelector("#unity-loading-bar");
        var progressBarFull = document.querySelector("#unity-progress-bar-full");
        var warningBanner = document.querySelector("#unity-warning");

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

        var buildUrl = "{{ asset('game/Build') }}";
        var loaderUrl = buildUrl + "/BulletDrop.loader.js";

        var config = {
            dataUrl: buildUrl + "/BulletDrop.data",
            frameworkUrl: buildUrl + "/BulletDrop.framework.js",
            codeUrl: buildUrl + "/BulletDrop.wasm",
            streamingAssetsUrl: "{{ asset('game/StreamingAssets') }}",
            companyName: "Ariatyx Gaming",
            productName: "BulletDrop",
            productVersion: "1.0",
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
                console.log("Unity loaded successfully");
            }).catch(function (message) {
                console.error(message);
                alert(message);
            });
        };

        script.onerror = function () {
            alert("Failed to load Unity loader. Check Build file names.");
        };

        document.body.appendChild(script);
    </script>
</x-app-layout>
