// resources/js/store/index.js
import { createStore } from 'vuex';
import axios from 'axios';
import websocketService from '../services/websocket';

export default createStore({
    state: {
        user: null,
        rooms: [],
        currentRoom: null,
        messages: {},
        onlineUsers: {},
    },
    
    getters: {
        isAuthenticated: (state) => !!state.user,
        currentUser: (state) => state.user,
        rooms: (state) => state.rooms,
        currentRoom: (state) => state.currentRoom,
        messages: (state) => (roomId) => state.messages[roomId] || [],
        onlineUsers: (state) => (roomId) => state.onlineUsers[roomId] || [],
    },
    
    mutations: {
        SET_USER(state, user) {
            state.user = user;
        },
        
        SET_ROOMS(state, rooms) {
            state.rooms = rooms;
        },
        
        SET_CURRENT_ROOM(state, room) {
            state.currentRoom = room;
        },
        
        SET_MESSAGES(state, { roomId, messages }) {
            state.messages = {
                ...state.messages,
                [roomId]: messages,
            };
        },
        
        ADD_MESSAGE(state, { roomId, message }) {
            if (!state.messages[roomId]) {
                state.messages[roomId] = [];
            }
            
            state.messages[roomId].push(message);
        },
        
        SET_ONLINE_USERS(state, { roomId, users }) {
            state.onlineUsers = {
                ...state.onlineUsers,
                [roomId]: users,
            };
        },
        
        ADD_ONLINE_USER(state, { roomId, user }) {
            if (!state.onlineUsers[roomId]) {
                state.onlineUsers[roomId] = [];
            }
            
            if (!state.onlineUsers[roomId].find(u => u.id === user.id)) {
                state.onlineUsers[roomId].push(user);
            }
        },
        
        REMOVE_ONLINE_USER(state, { roomId, userId }) {
            if (state.onlineUsers[roomId]) {
                state.onlineUsers[roomId] = state.onlineUsers[roomId].filter(
                    user => user.id !== userId
                );
            }
        },
    },
    
    actions: {
        // store/index.js
        async login({ commit }, credentials) {
            try {
                const response = await axios.post('/api/login', credentials);
        
                const token = response.data.token;
        
                localStorage.setItem('auth_token', token);
        
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        
                commit('SET_USER', response.data.user);
                return response.data.user;
            } catch (error) {
                console.error('Lỗi đăng nhập:', error.response?.data || error);
                throw error;
            }
        },
        

        async register({ commit }, userData) {

            try {
                await axios.get('/sanctum/csrf-cookie');
                
                await axios.post('/api/register', userData);
            } catch (error) {
                console.error('Lỗi đăng Ký:', error);
                throw error;
            }
        },

        // store/index.js
        async fetchCurrentUser({ commit }) {
            try {
                const response = await axios.get('/api/user');
                commit('SET_USER', response.data);
                return response.data;
            } catch (error) {
                if (error.response && error.response.status === 401) {
                    console.log('Người dùng chưa đăng nhập');
                    commit('SET_USER', null);
                    return null;
                }
                console.error('Lỗi lấy thông tin user:', error);
                throw error;
            }
        },
        
        logout({ commit }) {
            const token = localStorage.getItem('auth_token');
        
            if (!token) return;
        
            return axios.post('/api/logout', null, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: 'application/json'
                }
            }).then(() => {
                commit('SET_USER', null);
                localStorage.removeItem('auth_token');
                delete axios.defaults.headers.common['Authorization'];
            });
        },

        async createRoom({ commit, dispatch }, roomData) {
            const response = await axios.post('/api/rooms', roomData);
            commit('setRooms', [...state.rooms, response.data]);
            return response.data;
        },
        
        
        async fetchRooms({ commit }) {
            try {
                const response = await axios.get('/api/rooms');
                commit('SET_ROOMS', response.data);
                return response.data;
            } catch (error) {
                console.error('Lỗi lấy danh sách phòng:', error);
                throw error;
            }
        },
        
        async connectWebSocket({ state, commit }) {
            if (!window.Echo) {
                console.error('Echo is not initialized');
                return;
            }
            console.log('Connecting to presence channel:', `presence-chat.${state.currentRoom?.id}`);
            await axios.get('/sanctum/csrf-cookie');
            const roomId = this.currentRoomId || 1; // Lấy từ Vuex, component, hoặc API
            if (!roomId) {
                console.error('Room ID is undefined');
                return;
            }
            console.log('Joining channel: presence-chat.' + roomId);
            window.Echo.join(`presence-chat.${roomId}`)
                .here((users) => { console.log('Users:', users); })
                .joining((user) => { console.log('Joining:', user); })
                .leaving((user) => { console.log('Leaving:', user); })
                .error((error) => { console.error('Echo join error:', error); })
                .here((users) => {
                    commit('setOnlineUsers', {
                        roomId: state.currentRoom.id,
                        users,
                    });
                    console.log('Users in room:', users);
                })
                .joining((user) => {
                    commit('setOnlineUsers', {
                        roomId: state.currentRoom.id,
                        users: [...state.onlineUsers[state.currentRoom.id], user],
                    });
                    console.log('User joined:', user);
                })
                .leaving((user) => {
                    commit('setOnlineUsers', {
                        roomId: state.currentRoom.id,
                        users: state.onlineUsers[state.currentRoom.id].filter(
                            (u) => u.id !== user.id
                        ),
                    });
                    console.log('User left:', user);
                })
                .listen('ChatMessageEvent', (e) => {
                    commit('addMessage', {
                        roomId: state.currentRoom.id,
                        message: e.message,
                    });
                    console.log('Message received:', e.message);
                })
                .error((error) => {
                    console.error('Echo error:', error);
                });
        },
        
        joinRoom({ commit }, roomId) {
            websocketService.joinRoom(roomId);
        },
        
        leaveRoom({ commit }, roomId) {
            websocketService.leaveRoom(roomId);
        },
        
        sendMessage({ commit }, { roomId, content }) {
            websocketService.sendMessage(roomId, content);
        },
    },
});