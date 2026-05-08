const firebaseConfig = {
    apiKey: "AIzaSyD4QZ4jHI_TvxzHdEtTVoz_9-dOxoaCquw",
    authDomain: "push-notif-49af1.firebaseapp.com",
    projectId: "push-notif-49af1",
    storageBucket: "push-notif-49af1.firebasestorage.app",
    messagingSenderId: "409604966307",
    appId: "1:409604966307:web:803d07cdc6435bfe20bc16",
    measurementId: "G-BGRYMT1KW2"
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

async function RequestFirebaseNotificationPermission() {
    try {
        if (!("Notification" in window)) {
            console.error("This browser does not support notifications.");
            return;
        }

        if (!("serviceWorker" in navigator)) {
            console.error("This browser does not support service workers.");
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== "granted") {
            console.log("Notification permission denied.");
            return;
        }

        const registration = await navigator.serviceWorker.register("/firebase-messaging-sw.js");

        const token = await messaging.getToken({
            vapidKey: "BD2QjIGrn7obtmebZsqHixP6BSvokZxdd8XojaHN8WstJgE1vMlAJNivHYSZ6PaJQsNxuhe79p38S0OBif40uyI",
            serviceWorkerRegistration: registration
        });

        if (token) {
            console.log("Firebase Token:", token);
            localStorage.setItem("firebase_token", token);
        } else {
            console.warn("No Firebase token received.");
        }

    } catch (error) {
        console.error("Firebase notification error:", error);
    }
}

messaging.onMessage((payload) => {
    console.log("Foreground Firebase Message:", payload);

    const title = payload.notification?.title || "Ariatyx Gaming";
    const body = payload.notification?.body || "You have a new notification.";

    new Notification(title, {
        body: body,
        icon: "/icon-192.png"
    });
});

window.RequestFirebaseNotificationPermission = RequestFirebaseNotificationPermission;