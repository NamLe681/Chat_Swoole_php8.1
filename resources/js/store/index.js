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
        
                // Lưu token (localStorage / Vuex)
                localStorage.setItem('auth_token', token);
        
                // Set default header cho Axios
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
        
        async connectWebSocket({ commit, state }) {
            if (!state.user) {
                throw new Error('User chưa đăng nhập');
            }
            console.log('aaa',state.user)
            try {
                await websocketService.connect(state.user.id);
                websocketService.onMessage((data) => {
                    switch (data.type) {
                        case 'join_room_success':
                            commit('SET_CURRENT_ROOM', data.room);
                            commit('SET_MESSAGES', { 
                                roomId: data.room.id, 
                                messages: data.messages 
                            });
                            break;
                        
                        case 'new_message':
                            commit('ADD_MESSAGE', {
                                roomId: data.room_id,
                                message: data.message,
                            });
                            break;
                        
                        case 'user_joined':
                            commit('ADD_ONLINE_USER', {
                                roomId: data.room_id,
                                user: data.user,
                            });
                            break;
                        
                        case 'user_left':
                            commit('REMOVE_ONLINE_USER', {
                                roomId: data.room_id,
                                userId: data.user.id,
                            });
                            break;
                    }
                });
                
                return true;
            } catch (error) {
                console.error('Lỗi kết nối WebSocket:', error);
                throw error;
            }
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