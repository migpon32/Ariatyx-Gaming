<x-app-layout>
    <x-slot name="title">Play BulletDrop | Ariatyx Gaming</x-slot>

    <x-slot name="favicon">
        {{ asset('images/War_Bird.png') }}
    </x-slot>
    <link rel="stylesheet" href="{{ asset('game/TemplateData/style.css') }}">
    <link rel="manifest" href="{{ asset('game/manifest.webmanifest') }}">
    <link rel="manifest" href="{{ asset('game/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('game/icon-192.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="theme-color" content="#000000">

    <!-- Laravel to Unity Player Bridge -->
    <script>
        window.LaravelPlayer = {
            id: @json(auth()->id()),
            username: @json(auth()->user()->username ?? auth()->user()->name ?? 'Player'),
            name: @json(auth()->user()->username ?? auth()->user()->name ?? 'Player')
        };

        localStorage.setItem("player_id", String(window.LaravelPlayer.id || "guest"));
        localStorage.setItem("player_name", String(window.LaravelPlayer.name || "Player"));
        localStorage.setItem("player_username", String(window.LaravelPlayer.username || "Player"));

        function GetPlayerNameFromJS() {
            return localStorage.getItem("player_name") || "Player";
        }

        function GetLaravelUserId() {
            return localStorage.getItem("player_id") || "guest";
        }

        function GetLaravelUsername() {
            return localStorage.getItem("player_username") || localStorage.getItem("player_name") || "Player";
        }

        console.log("LaravelPlayer:", window.LaravelPlayer);
        console.log("Unity Player ID:", localStorage.getItem("player_id"));
        console.log("Unity Player Name:", localStorage.getItem("player_name"));
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
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/game/ServiceWorker.js')
    .then(() => console.log("Service Worker Registered"));
}
</script>
    <!-- Unity Exit Button Bridge -->
    <script>
        function ExitToLauncher() {
            window.location.href = "{{ route('launcher') }}";
        }
    </script>

    <!-- Firebase Scripts -->
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js"></script>

    <script>
        window.firebaseMessagingSwPath = "{{ asset('game/firebase-messaging-sw.js') }}";
    </script>

    <script src="{{ asset('game/firebase-notification.js') }}"></script>

    <!-- Unity WebGL Loader -->
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