importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js");

firebase.initializeApp({
  apiKey: "AIzaSyD4QZ4jHI_TvxzHdEtTVoz_9-dOxoaCquw",
  authDomain: "push-notif-49af1.firebaseapp.com",
  projectId: "push-notif-49af1",
  storageBucket: "push-notif-49af1.firebasestorage.app",
  messagingSenderId: "409604966307",
  appId: "1:409604966307:web:803d07cdc6435bfe20bc16",
  measurementId: "G-BGRYMT1KW2"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: "/icon-192.png"
  });
});