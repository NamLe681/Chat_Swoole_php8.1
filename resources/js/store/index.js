import { createStore } from "vuex";
import axios from "axios";
axios.defaults.withCredentials = true;
export default createStore({
    state: {
        user: null,
        rooms: [],
        currentRoom: null,
        onlineUsers: {},
        messages: {},
        usersList: [],
    },

    getters: {
        isAuthenticated: (state) => !!state.user,
        currentUser: (state) => state.user,
        rooms: (state) => state.rooms,
        currentRoom: (state) => state.currentRoom,
        messages: (state) => (roomId) => state.messages[roomId] || [],
        onlineUsers: (state) => (roomId) => state.onlineUsers[roomId] || [],
        usersList: (state) => state.usersList,
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
    },

    actions: {
        async login({ commit, state }, credentials) {
            try {
                await axios.get("/sanctum/csrf-cookie");

                const res = await axios.post("/api/login", credentials);
                console.log("API login response:", res.data);

                commit("setUser", res.data.user);

                console.log("State hiện tại:", JSON.stringify(state));

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
              await axios.get('/sanctum/csrf-cookie');
              
              await axios.post('/api/register', userData);
             
             const response = await axios.get('/api/user');
              commit('SET_USER', response.data);
              return response.data;
          } catch (error) {
              console.error('Lỗi đăng Ký:', error);
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
            // console.log('Joining presence-chat.' + roomId);
            // console.log('user', state.user.id);
            // console.log('message', state.messages[roomId]);

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
                commit("setUsersList", res.data); // ✅ LƯU VÀO STATE
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
    },
});
