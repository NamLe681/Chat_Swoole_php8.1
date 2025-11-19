importScripts("https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyB3-Czd4q3rcTuB2TmjTHFmCvXJJpTFA-Y",
    authDomain: "test-ea1b1.firebaseapp.com",
    projectId: "test-ea1b1",
    storageBucket: "test-ea1b1.firebasestorage.app",
    messagingSenderId: "1091800666098",
    appId: "1:1091800666098:web:d9dd24250cc13ab58fe56b",
    measurementId: "G-1TL4VH11D0"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: payload.notification.icon,
  });
});
