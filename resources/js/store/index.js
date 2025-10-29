import { createStore } from 'vuex';
import axios from 'axios';
axios.defaults.withCredentials = true;
export default createStore({
  state: {
    user: null,
    rooms: [],
    currentRoom: null,
    onlineUsers: {},
    messages: {}
  },

  getters: {
    isAuthenticated: state => !!state.user,
    currentUser: state => state.user,
    rooms: state => state.rooms,
    currentRoom: state => state.currentRoom,
    messages: state => (roomId) => state.messages[roomId] || [],
    onlineUsers: state => (roomId) => state.onlineUsers[roomId] || []
  },

  mutations: {
    setUser(state, user) {
      state.user = user;
    },
    setRooms(state, rooms) {
      state.rooms = rooms;
    },
    setCurrentRoomId(state, roomId) {
      const room = state.rooms.find(r => r.id === roomId);
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
    }
  },

  actions: {
    async login({ commit, state }, credentials) {
      try {
          await axios.get('/sanctum/csrf-cookie');
          
          const res = await axios.post('/api/login', credentials);
          console.log('API login response:', res.data);
          
          commit('setUser', res.data.user);
          
          console.log('State hiện tại:', JSON.stringify(state));
          
          await new Promise(resolve => setTimeout(resolve, 300));
          
          console.log('User sau timeout:', state.user);
          
          return res.data;
      } catch (error) {
          console.error('Login error:', error);
          throw error;
      }
  },

    async logout({ commit }) {
      await axios.post('/api/logout');
      commit('clearAuth');
    },

    async fetchRooms({ commit, state }) {
      const res = await axios.get('/api/rooms');
      const rooms = res.data.data || res.data;
      commit('setRooms', rooms);
      if (!state.currentRoom && rooms.length > 0) {
        commit('setCurrentRoomId', rooms[0].id);
      }
    },

    async createRoom({ dispatch }, roomData) {
      const res = await axios.post('/api/rooms', roomData);
      await dispatch('fetchRooms');
      return res.data;
    },

    async connectWebSocket({ state, commit }) {
      if (!window.Echo) return console.error('Echo not initialized');
      if (!state.currentRoom) return console.error('No room selected');

      const roomId = state.currentRoom.id;
      console.log('Joining presence-chat.' + roomId);
      console.log('user',state.user.id);

      if (window.Echo.connector.channels[`presence-chat.${roomId}`]) {
          window.Echo.leave(`presence-chat.${roomId}`);
      }

      window.Echo.join(`presence-chat.${roomId}`)
          .here(users => {
              console.log('Users here:', users);
              commit('setOnlineUsers', { roomId, users });
          })
          .joining(user => {
              console.log('User joining:', user);
              const current = state.onlineUsers[roomId] || [];
              commit('setOnlineUsers', { roomId, users: [...current, user] });
          })
          .leaving(user => {
              console.log('User leaving:', user);
              const current = state.onlineUsers[roomId] || [];
              commit('setOnlineUsers', { roomId, users: current.filter(u => u.id !== user.id) });
          })
          .listen('.ChatMessageEvent', e => {
              console.log('Message received:', e);
              commit('addMessage', { roomId, message: e.message });
          })
          .error(err => console.error('Echo error:', err));
    },

    async sendMessage({ state }, { content }) {
      if (!state.currentRoom) return;
      await axios.post(`/api/rooms/${state.currentRoom.id}/messages`, { content });
    }
  }
});