if (typeof firebase === "undefined") {
    console.warn("Firebase is unavailable. Notifications are disabled while offline.");
    window.RequestFirebaseNotificationPermission = async function () {
        window.FirebaseNotificationPermissionRequestedEarly = true;
        window.FirebaseNotificationPermissionNeedsTap = true;
        return null;
    };
} else {
    const firebaseConfig = {
        apiKey: "AIzaSyD4QZ4jHI_TvxzHdEtTVoz_9-dOxoaCquw",
        authDomain: "push-notif-49af1.firebaseapp.com",
        projectId: "push-notif-49af1",
        storageBucket: "push-notif-49af1.firebasestorage.app",
        messagingSenderId: "409604966307",
        appId: "1:409604966307:web:803d07cdc6435bfe20bc16",
        measurementId: "G-BGRYMT1KW2"
    };

    firebase.apps && firebase.apps.length
        ? firebase.app()
        : firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();
    const firebaseVapidKey = "BD2QjIGrn7obtmebZsqHixP6BSvokZxdd8XojaHN8WstJgE1vMlAJNivHYSZ6PaJQsNxuhe79p38S0OBif40uyI";

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    async function getFirebaseMessagingRegistration() {
        if (!("serviceWorker" in navigator)) {
            throw new Error("This browser does not support service workers.");
        }

        if (typeof window.GetFirebaseMessagingServiceWorkerRegistration === "function") {
            const existingRegistration = await window.GetFirebaseMessagingServiceWorkerRegistration();

            if (existingRegistration) {
                return existingRegistration;
            }
        }

        return navigator.serviceWorker.register("/firebase-messaging-sw.js", {
            scope: "/firebase-cloud-messaging-push-scope/"
        });
    }

    async function saveFirebaseToken(token) {
        localStorage.setItem("firebase_token", token);
        localStorage.setItem("firebase_token_updated_at", new Date().toISOString());

        if (!window.FirebaseTokenStoreUrl) {
            return;
        }

        const response = await fetch(window.FirebaseTokenStoreUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                token: token
            })
        });

        if (!response.ok) {
            throw new Error("Could not save Firebase token. HTTP " + response.status);
        }
    }

    async function RequestFirebaseNotificationPermission() {
        try {
            if (!("Notification" in window)) {
                console.error("This browser does not support notifications.");
                return null;
            }

            if (!navigator.onLine) {
                console.warn("Firebase notifications need an internet connection.");
                return null;
            }

            const permission = Notification.permission === "default"
                ? await Notification.requestPermission()
                : Notification.permission;

            if (permission !== "granted") {
                console.log("Notification permission denied.");
                return null;
            }

            window.FirebaseNotificationPermissionNeedsTap = false;
            const registration = await getFirebaseMessagingRegistration();
            const token = await messaging.getToken({
                vapidKey: firebaseVapidKey,
                serviceWorkerRegistration: registration
            });

            if (!token) {
                console.warn("No Firebase token received.");
                return null;
            }

            console.log("Firebase Token:", token);
            await saveFirebaseToken(token);
            window.FirebaseNotificationPermissionRequestedEarly = false;
            return token;
        } catch (error) {
            console.error("Firebase notification error:", error);
            return null;
        }
    }

    function requestNotificationOnNextUserTap() {
        if (window.FirebaseNotificationTapListenerInstalled || !("Notification" in window)) {
            return;
        }

        if (Notification.permission !== "default") {
            return;
        }

        window.FirebaseNotificationTapListenerInstalled = true;

        const requestFromTap = function () {
            window.FirebaseNotificationTapListenerInstalled = false;
            window.FirebaseNotificationPermissionNeedsTap = false;
            removeTapListeners();
            RequestFirebaseNotificationPermission();
        };

        const removeTapListeners = function () {
            window.removeEventListener("pointerdown", requestFromTap, true);
            window.removeEventListener("touchend", requestFromTap, true);
            window.removeEventListener("click", requestFromTap, true);
        };

        window.addEventListener("pointerdown", requestFromTap, true);
        window.addEventListener("touchend", requestFromTap, true);
        window.addEventListener("click", requestFromTap, true);
    }

    async function showForegroundNotification(payload) {
        const notification = payload.notification || {};
        const data = payload.data || {};
        const title = notification.title || data.title || "Ariatyx Gaming";
        const body = notification.body || data.body || "You have a new notification.";
        const options = {
            body: body,
            icon: notification.icon || "/game/icon-192.png",
            badge: "/game/icon-192.png",
            data: {
                url: data.url || "/launcher",
                ...data
            }
        };

        const registration = await getFirebaseMessagingRegistration();

        if (registration && registration.showNotification) {
            return registration.showNotification(title, options);
        }

        return new Notification(title, options);
    }

    messaging.onMessage(function (payload) {
        console.log("Foreground Firebase Message:", payload);

        if (Notification.permission !== "granted") {
            return;
        }

        showForegroundNotification(payload).catch(function (error) {
            console.error("Foreground notification display failed:", error);
        });
    });

    window.RequestFirebaseNotificationPermission = RequestFirebaseNotificationPermission;

    if (
        "Notification" in window &&
        Notification.permission === "granted"
    ) {
        RequestFirebaseNotificationPermission();
    } else if (window.FirebaseNotificationPermissionRequestedEarly || window.FirebaseNotificationPermissionNeedsTap) {
        requestNotificationOnNextUserTap();
    }
}
