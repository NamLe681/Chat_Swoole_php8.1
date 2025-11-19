import { createStore } from "vuex";
import axios from "axios";
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey: "AIzaSyB3-Czd4q3rcTuB2TmjTHFmCvXJJpTFA-Y",
    authDomain: "test-ea1b1.firebaseapp.com",
    projectId: "test-ea1b1",
    storageBucket: "test-ea1b1.firebasestorage.app",
    messagingSenderId: "1091800666098",
    appId: "1:1091800666098:web:d9dd24250cc13ab58fe56b",
    measurementId: "G-1TL4VH11D0"
  };
  
  // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);
axios.defaults.withCredentials = true;


// Nhận notification khi tab đang active
onMessage(messaging, (payload) => {
    console.log("Foreground notification:", payload);
    alert(`${payload.notification.title}: ${payload.notification.body}`);
  });


export default createStore({
    state: {
        user: null,
        rooms: [],
        currentRoom: null,
        onlineUsers: {},
        messages: {},
        usersList: [],
        spotifyResults: [],
    },

    getters: {
        isAuthenticated: (state) => !!state.user,
        currentUser: (state) => state.user,
        rooms: (state) => state.rooms,
        currentRoom: (state) => state.currentRoom,
        messages: (state) => (roomId) => state.messages[roomId] || [],
        onlineUsers: (state) => (roomId) => state.onlineUsers[roomId] || [],
        usersList: (state) => state.usersList,
        spotifyResults: (state) => state.spotifyResults,
    },

    mutations: {
        setUser(state, user) {
            state.user = user;
        },
        setRooms(state, rooms) {
            state.rooms = rooms;
        },
        setCurrentRoomId(state, roomId) {
            const room = state.rooms.find((r) => r.id === roomId);
            state.currentRoom = room || null;
            if (!room) console.warn(`Room ${roomId} not found`);
        },
        setOnlineUsers(state, { roomId, users }) {
            state.onlineUsers = { ...state.onlineUsers, [roomId]: users };
        },
        addMessage(state, { roomId, message }) {
            if (!state.messages[roomId]) state.messages[roomId] = [];
            state.messages[roomId].push(message);
        },
        clearAuth(state) {
            state.user = null;
            state.currentRoom = null;
            if (window.Echo && state.currentRoom) {
                window.Echo.leave(`presence-chat.${state.currentRoom.id}`);
            }
        },
        prependMessages(state, { roomId, messages }) {
            if (!Array.isArray(messages)) {
                console.warn(
                    "prependMessages: messages không phải là mảng",
                    messages
                );
                return;
            }

            const roomMessages = state.messages[roomId] || [];
            const reversed = [...messages].reverse();

            state.messages[roomId] = [...reversed, ...roomMessages];
        },
        setUsersList(state, users) {
            state.usersList = users;
        },

        setSpotifyResults(state, tracks) {
            state.spotifyResults = tracks;
        },

        
    },

    actions: {
        async login({ commit, state, dispatch }, credentials) {
            try {
                await axios.get("/sanctum/csrf-cookie");

                const res = await axios.post("/api/login", credentials);
                console.log("API login response:", res.data);

                commit("setUser", res.data.user);

                await dispatch("registerFcm");
                // console.log("State hiện tại:", JSON.stringify(state));

                await new Promise((resolve) => setTimeout(resolve, 300));

                console.log("User sau timeout:", state.user);

                return res.data;
            } catch (error) {
                console.error("Login error:", error);
                throw error;
            }
        },

        async register({ commit }, userData) {
            try {
                await axios.get("/sanctum/csrf-cookie");

                await axios.post("/api/register", userData);

                const response = await axios.get("/api/user");
                commit("SET_USER", response.data);
                return response.data;
            } catch (error) {
                console.error("Lỗi đăng Ký:", error);
                throw error;
            }
        },

        async fetchMoreMessages({ commit }, { roomId, cursor }) {
            const url = cursor
                ? `http://127.0.0.1:8000/api/rooms/${roomId}/messages?cursor=${cursor}`
                : `http://127.0.0.1:8000/api/rooms/${roomId}/messages`;

            const response = await axios.get(url);
            const { data, next_cursor, prev_cursor } = response.data || [];

            commit("prependMessages", { roomId, messages: data });
            return { data, next_cursor, prev_cursor };
        },

        async logout({ commit }) {
            await axios.post("/api/logout");
            commit("clearAuth");
        },

        async fetchRooms({ commit, state }) {
            const res = await axios.get("/api/rooms");
            const rooms = res.data.data || res.data;
            commit("setRooms", rooms);
            if (!state.currentRoom && rooms.length > 0) {
                commit("setCurrentRoomId", rooms[0].id);
            }
        },

        async getMessage({ commit, state }, { roomId }) {
            const res = await axios.get(`/api/rooms/${roomId}/messages`);
            const messages = res.data.data || res.data;
            state.messages = { ...state.messages, [roomId]: messages };
        },

        async createRoom({ dispatch }, roomData) {
            const res = await axios.post("/api/rooms", roomData);
            await dispatch("fetchRooms");
            return res.data;
        },

        async connectWebSocket({ state, commit }) {
            if (!window.Echo) return console.error("Echo not initialized");
            if (!state.currentRoom) return console.error("No room selected");

            const roomId = state.currentRoom.id;

            const res1 = await axios.get(`/api/rooms/${roomId}/messages`);

            const messages = res1.data.data.reverse();

            state.messages = { ...state.messages, [roomId]: messages };

            console.log("Messages sau khi lấy:", state.messages);

            if (window.Echo.connector.channels[`presence-chat.${roomId}`]) {
                window.Echo.leave(`presence-chat.${roomId}`);
            }

            window.Echo.join(`presence-chat.${roomId}`)
                .here((users) => {
                    console.log("Users here:", users);
                    commit("setOnlineUsers", { roomId, users });
                })
                .joining((user) => {
                    console.log("User joining:", user);
                    const current = state.onlineUsers[roomId] || [];
                    commit("setOnlineUsers", {
                        roomId,
                        users: [...current, user],
                    });
                })
                .leaving((user) => {
                    console.log("User leaving:", user);
                    const current = state.onlineUsers[roomId] || [];
                    commit("setOnlineUsers", {
                        roomId,
                        users: current.filter((u) => u.id !== user.id),
                    });
                })

                .listen(".ChatMessageEvent", (data) => {
                    console.log("Message received:", data);
                    commit("addMessage", { roomId, message: data.message });
                })
                .error((err) => console.error("Presence channel error:", err));
        },

        async sendMessage({ state }, { content }) {
            if (!state.currentRoom) return;
            await axios.post(`/api/rooms/${state.currentRoom.id}/messages`, {
                content,
            });

        },

        async getAllUser({ commit }) {
            try {
                const res = await axios.get("/api/get/users");
                commit("setUsersList", res.data);
            } catch (error) {
                console.error("Error fetching users:", error);
            }
        },

        async addUserToRoom({ commit, state }, { userId }) {
            try {
                const res = await axios.post(
                    `/api/rooms/${state.currentRoom.id}/add-user/${userId}`,
                    {}
                );
                console.log("User sau khi add:", res);
            } catch (error) {
                console.error("Error adding user to room:", error);
            }
        },

        async removeUserFromRoom({ commit, state }, { userId }) {},

        async searchspotify({ commit, state }, { q, type, content }) {
            try {
                const type = "track";
                const res = await axios.get(
                    `/api/spotify/search?q=${q}&type=${type}`
                );
                console.log("Kết quả tìm kiếm Spotify:", res.data);
                commit("setSpotifyResults", res.data.tracks);
                return res.data;
            } catch (error) {
                console.error("Lỗi tìm kiếm Spotify:", error);
            }
        },

        async canvasMessage({ state }, { dataUrl }) {
            try {
                const res = await axios.post(
                    `/api/rooms/${state.currentRoom.id}/draw`,
                    {
                        drawing: dataUrl,
                    }
                );
                emit("draw-sent", res.data);
                emit("close");
            } catch (err) {
                console.error("Lỗi gửi hình vẽ:", err);
            }
        },

        async registerFcm() {
            try {
              const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            
              const token = await getToken(messaging, {
                vapidKey: "BNtxDQr9QeaKZEMVWQknC4mEiuMAff--gf78gYGwg45S4GHXjV4zewsxjE3z0wNdHF_YF7uwsJKmubfiLNAamC4",
                serviceWorkerRegistration: swRegistration
              });
              
              
              if (token) {
                await axios.post("/api/save-fcm-token", { token });
                console.log("FCM token registered:", token);
              }
            } catch (err) {
              console.error("FCM registration error:", err);
            }
          },

        async senNoti(){
            try {
                const res = await axios.post("/api/send-notification", {
                    token:"e1M7kJZcobfTugNn8x9WL5:APA91bHlJoQsdrSBvBQYzmdMwGLpip0p1WX5hLSAIbCM5E3LWZTPZmTMf4lYgLPiu2fbl_6jqs0d2iWPMTdAQTA3cBd4mF1_goXuxy_YUi-7L0okmevQn5E",
                    title: "Test Notification",
                    body: "This is a test notification from Vuex action.",
                });
                console.log("Notification sent:", res.data);
            } catch (err) {
                console.error("Error sending notification:", err);
            }
        }
    },
});
