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
        async fetchCurrentUser({ commit }) {
            try {
                const response = await axios.get('/api/user');
                commit('SET_USER', response.data);
                return response.data;
            } catch (error) {
                console.error('Lỗi lấy thông tin user:', error);
            }
        },
        
        logout({ commit }) {
            return axios.post('/logout').then(() => {
                commit('SET_USER', null);
                websocketService.disconnect();
            });
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