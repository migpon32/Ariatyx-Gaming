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
    const permission = await Notification.requestPermission();

    if (permission !== "granted") {
      console.log("Notification permission denied");
      return;
    }

    const token = await messaging.getToken({
      vapidKey: "BD2QjIGrn7obtmebZsqHixP6BSvokZxdd8XojaHN8WstJgE1vMlAJNivHYSZ6PaJQsNxuhe79p38S0OBif40uyI"
    });

    console.log("Firebase Token:", token);

  } catch (error) {
    console.error("Firebase notification error:", error);
  }
}