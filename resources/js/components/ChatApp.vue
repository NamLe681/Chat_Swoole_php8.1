<!-- resources/js/components/ChatApp.vue -->
<template>
  <div class="chat-app">
    <div v-if="isAuthenticated" class="chat-container">
      <!-- Sidebar hiển thị danh sách phòng chat -->
      <div class="sidebar">
        <div class="user-info">
          <h3>{{ currentUser.name }}</h3>
          <button class="logout-btn" @click="handleLogout">Đăng xuất</button>
        </div>

        <div class="rooms-list">
          <h2>Phòng Chat</h2>
          <ul>
            <li v-for="room in rooms" :key="room.id" :class="{ active: currentRoom && currentRoom.id === room.id }"
              @click="selectRoom(room.id)">
              {{ room.name }}
              <span class="users-count">({{ room.users_count || 0 }})</span>
            </li>
          </ul>

          <button class="create-room-btn" @click="showCreateRoomModal = true">
            Tạo phòng mới
          </button>
        </div>
      </div>

      <!-- Khu vực chat chính -->
      <div class="main-content">
        <template v-if="currentRoom">
          <div class="room-header">
            <h2>{{ currentRoom.name }}</h2>
            <p v-if="currentRoom.description">{{ currentRoom.description }}</p>
          </div>

          <div class="messages-container" ref="messagesContainer">
            <div v-if="isLoadingMore" class="loading-more">
              <span>Đang tải tin nhắn cũ...</span>
            </div>
            <div v-if="roomMessages.length === 0" class="no-messages">
              Chưa có tin nhắn nào trong phòng này
            </div>
            <div v-for="message in roomMessages" :key="message.id"
              :class="['message', { 'own-message': message.user.id === currentUser.id }]">
              <div class="message-header">
                <span class="message-author">{{ message.user.name }}</span>
                <span class="message-time">{{ formatTime(message.created_at) }}</span>
              </div>
              <div class="message-content">

                <audio v-if="message.type === 'voice'" :src="getVoiceUrl(message.content)" controls></audio>
                <span v-if="message.type === 'text'">{{ message.content }}</span>
                <img v-if="message.type === 'drawing'" :src="getDrawUrl(message.content)" alt="drawing"
                  class="drawing-preview" />
              </div>
            </div>
          </div>

          <div class="message-input">
            <input type="text" v-model="newMessage" @keyup.enter="sendMessage" placeholder="Nhập tin nhắn..." />
            <button class="voice-recorder-toggle-btn" @click="showVoiceRecord = !showVoiceRecord">
              Ghi âm
            </button>
            <div v-if="showVoiceRecord" class="voice-recorder-container">
              <VoiceRecorder :room-id="currentRoom.id" @voiceSent="handleVoiceMessage" />
            </div>
            <button class="emoji-toggle-btn" @click="showEmojiPicker = !showEmojiPicker">
              😊
            </button>

            <div v-if="showEmojiPicker" class="emoji-picker-container">
              <textarea-emoji-picker @emoji-selected="handleEmojiSelect" />
            </div>

            <!-- Test tìm và gửi nhạc -->
            <button class="music-toggle-btn" @click="showfindMusic = !showfindMusic">
              🎵
            </button>
            <div v-if="showfindMusic" class="music-container">
              <MusicSpotify @Track-selected="handleMusicSelect" />
            </div>

            <!-- Nút mở canvas -->
            <button class="draw-toggle-btn" @click="showDraw = true">
              🖌️ Vẽ
            </button>

            <!-- Thay vì render trong message-input, render ở ngoài để overlay -->
            <CanvasMessage v-if="showDraw" @close="showDraw = false" @draw-selected="handleDraw" />

            <button @click="sendMessage">Gửi</button>
          </div>
        </template>

        <div v-else class="no-room-selected">
          <h2>Chọn một phòng để bắt đầu chat</h2>
        </div>
      </div>

      <!-- Danh sách người dùng online trong phòng -->
      <div class="online-users" v-if="currentRoom">
        <h3>Người dùng online ({{ roomOnlineUsers.length }})</h3>
        <ul>
          <li v-for="user in roomOnlineUsers" :key="user.id">
            {{ user.name }}
            <span v-if="user.id === currentUser.id" class="user-self">(bạn)</span>
          </li>
        </ul>
        <div>
          <h3>Add user</h3>

          <button class="Add-user-toggle" @click="showAddUser = !showAddUser">
            Add user
          </button>

          <div v-if="showAddUser" class="add-user-container">

            <div class="user-list">
              <div v-for="u in users" :key="u.id" class="user-item">

                <span>{{ u.name }} (ID: {{ u.id }})</span>

                <button class="add-btn" @click="addUser(u.id)">
                  Add
                </button>

              </div>
            </div>

          </div>


        </div>
      </div>
    </div>
    <!-- Form đăng nhập -->
    <div v-else class="login-container">
      <div class="login-form">
        <h2>Đăng nhập để Chat</h2>
        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" v-model="loginForm.email" required />
          </div>

          <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" v-model="loginForm.password" required />
          </div>

          <div v-if="loginError" class="error-message">
            {{ loginError }}
          </div>

          <button type="submit" class="login-btn">Đăng nhập</button>
        </form>
      </div>
    </div>

    <!-- Modal tạo phòng mới -->
    <div class="modal" v-if="showCreateRoomModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Tạo phòng chat mới</h2>
          <button class="close-btn" @click="showCreateRoomModal = false">&times;</button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="createNewRoom">
            <div class="form-group">
              <label for="room-name">Tên phòng</label>
              <input type="text" id="room-name" v-model="newRoom.name" required />
            </div>

            <div class="form-group">
              <label for="room-description">Mô tả</label>
              <textarea id="room-description" v-model="newRoom.description"></textarea>
            </div>

            <button type="submit" class="create-btn">Tạo phòng</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, nextTick, onUnmounted, onMounted } from 'vue';
import { useStore } from 'vuex';
import TextareaEmojiPicker from './TextareaEmojiPicker.vue';
import VoiceRecorder from './VoiceRecorder.vue';
import MusicSpotify from './MusicSpotify.vue';
import CanvasMessage from './CanvasMessage.vue';

export default {
  name: 'ChatApp',
  components: {
    TextareaEmojiPicker,
    VoiceRecorder,
    MusicSpotify,
    CanvasMessage,
  },

  methods: {
    addEmoji(emoji) {
      this.newMessage += emoji;
    },

    formatTime(datetime) {
      return new Date(datetime).toLocaleTimeString();
    },
    getVoiceUrl(content) {
      if (content.startsWith('http')) {
        return content;
      }
      return `/storage/${content}`;
    },

    getDrawUrl(content) {
      if (content.startsWith('http')) {
        return content;
      }
      return `/storage/${content}`;
    }
  },
  setup() {
    const isLoadingMore = ref(false);
    const hasMoreMessages = ref(true);
    const currentCursor = ref(null);
    const store = useStore();
    const newMessage = ref('');
    const query = ref('');
    const messagesContainer = ref(null);
    const showCreateRoomModal = ref(false);
    const loginForm = ref({ email: '', password: '' });
    const newRoom = ref({ name: '', description: '' });
    const loginError = ref('');

    // Computed 
    const isAuthenticated = computed(() => store.getters.isAuthenticated);
    const currentUser = computed(() => store.getters.currentUser);
    const rooms = computed(() => store.getters.rooms);
    const currentRoom = computed(() => store.getters.currentRoom);
    const users = computed(() => store.state.usersList || []);
    const roomMessages = computed(() =>
      store.getters.messages(currentRoom.value?.id || 0)
    );
    const roomOnlineUsers = computed(() =>
      store.getters.onlineUsers(currentRoom.value?.id || 0)
    );
    const showEmojiPicker = ref(false);
    const showVoiceRecord = ref(false);
    const showfindMusic = ref(false);
    const showDraw = ref(false);
    const showAddUser = ref(false);
    const selectedUserId = ref('');

    onMounted(() => {
      store.dispatch('getAllUser');
    });

    watch(roomMessages, () => {
      nextTick(() => {
        scrollToBottom();
      });
    });

    watch(currentRoom, () => {
      hasMoreMessages.value = true;
      currentCursor.value = null;
      isLoadingMore.value = false;
    });

    const handleScroll = () => {
      const container = messagesContainer.value;
      if (!container) return;

      const scrollPosition = container.scrollTop;
      const threshold = 100;
      if (scrollPosition < threshold && !isLoadingMore.value && hasMoreMessages.value) {
        loadMoreMessages();
      }
    };

    // Gắn và gỡ event
    watch(messagesContainer, (el) => {
      if (el) {
        el.addEventListener('scroll', handleScroll);
      }
    }, { immediate: true });

    onUnmounted(() => {
      if (messagesContainer.value) {
        messagesContainer.value.removeEventListener('scroll', handleScroll);
      }
    });

    const sendMessage = () => {
      if (!newMessage.value.trim() || !currentRoom.value) {
        return;
      }

      store.dispatch('sendMessage', {
        content: newMessage.value
      });
      const body = newMessage.value;
      store.dispatch('senNoti',body);
      newMessage.value = '';
    };

    const selectRoom = async (roomId) => {
      const previousRoomId = currentRoom.value?.id;

      if (previousRoomId) {
        window.Echo.leave(`presence-chat.${previousRoomId}`);
      }

      store.commit('setCurrentRoomId', roomId);

      await store.dispatch('connectWebSocket', roomId);
    };


    const handleLogin = async () => {
      try {
        loginError.value = '';
        await store.dispatch('login', loginForm.value);
        await store.dispatch('fetchRooms');
        await store.dispatch('connectWebSocket');
        await store.dispatch('getMessage');

        loginForm.value = { email: '', password: '' };
      } catch (error) {
        loginError.value = 'Đăng nhập thất bại. Vui lòng kiểm tra thông tin đăng nhập.';
        console.error('Lỗi đăng nhập:', error);
      }
    };

    const handleLogout = () => {
      if (currentRoom.value) {
        store.dispatch('logout', currentRoom.value.id);
      }
      store.dispatch('logout');
    };

    const createNewRoom = async () => {
      try {
        const room = await store.dispatch('createRoom', newRoom.value);
        showCreateRoomModal.value = false;
        newRoom.value = { name: '', description: '' };
        selectRoom(room.id);
      } catch (error) {
        console.error('Lỗi tạo phòng:', error);
      }
    };

    const addUser = (uid) => {
      store.dispatch("addUserToRoom", {
        userId: uid,
        roomId: currentRoom.value.id
      });
    };

    const getUser = () => {
      store.dispatch('getAllUser');
    };

    const formatTime = (timestamp) => {
      return new Date(timestamp).toLocaleString('vi-VN');
    };

    const scrollToBottom = () => {
      if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
      }
    };

    const handleEmojiSelect = (emoji) => {
      newMessage.value += emoji;
      showEmojiPicker.value = false;
    };

    const handleVoiceMessage = (voiceData) => {
      newMessage.value += audioUrl;
      showVoiceRecord.value = false;
    };

    const handleMusicSelect = (track) => {
      newMessage.value += track.name;
      showfindMusic.value = false;
    };

    const handleDraw = async (dataUrl) => {
      showDraw.value = false;

      await store.dispatch('sendMessage', {
        content: dataUrl,
        type: 'image'
      });
    };


    const loadMoreMessages = async () => {
      if (isLoadingMore.value || !hasMoreMessages.value || !currentRoom.value) return;

      isLoadingMore.value = true;

      try {
        const response = await store.dispatch('fetchMoreMessages', {
          roomId: currentRoom.value.id,
          cursor: currentCursor.value
        });

        const { data, next_cursor, prev_cursor } = response;

        if (!next_cursor) {
          hasMoreMessages.value = false;
        } else {
          currentCursor.value = next_cursor;
        }

        const container = messagesContainer.value;
        const previousHeight = container.scrollHeight;

        store.commit('prependMessages', { roomId: currentRoom.value.id, messages: data });

        nextTick(() => {
          const newHeight = container.scrollHeight;
          container.scrollTop = newHeight - previousHeight;
        });

      } catch (error) {
        console.error('Lỗi tải thêm tin nhắn:', error);
      } finally {
        isLoadingMore.value = false;
      }


    };
    // const searchSpotify = async () => {
    //   if (!query.value.trim()) return;

    //   try {
    //     await store.dispatch('searchspotify', { q: query.value });
    //     console.log('Đã tìm nhạc:', query.value);
    //   } catch (error) {
    //     console.error('Lỗi tìm nhạc Spotify:', error);
    //   }
    // };

    const searchSpotify = async () => {
      if (!query.value.trim()) return;

      try {
        await store.dispatch('searchspotify', { q: query.value });
        console.log('Đã tìm nhạc:', query.value);
      } catch (error) {
        console.error('Lỗi tìm nhạc Spotify:', error);
      }
    };



    // Load rooms and connect WebSocket on mount
    (async () => {
      if (isAuthenticated.value) {
        await store.dispatch('fetchRooms');
        await store.dispatch('connectWebSocket');
        await store.dispatch('getMessage');
      }
    })();

    return {
      newMessage,
      messagesContainer,
      showCreateRoomModal,
      loginForm,
      newRoom,
      loginError,
      isAuthenticated,
      currentUser,
      rooms,
      currentRoom,
      roomMessages,
      roomOnlineUsers,
      selectRoom,
      sendMessage,
      handleLogin,
      handleLogout,
      addUser,
      createNewRoom,
      formatTime,
      showEmojiPicker,
      showVoiceRecord,
      showfindMusic,
      showDraw,
      handleEmojiSelect,
      handleVoiceMessage,
      handleMusicSelect,
      handleDraw,
      isLoadingMore,
      showAddUser,
      getUser,
      selectedUserId,
      users,
      searchSpotify,
      query,
    };
  }
};
</script>

<style scoped>
.chat-app {
  height: 100vh;
  font-family: Arial, sans-serif;
}

.chat-container {
  display: flex;
  height: 100%;
}

.sidebar {
  width: 250px;
  background: #2c3e50;
  color: white;
  display: flex;
  flex-direction: column;
}

.user-info {
  padding: 15px;
  border-bottom: 1px solid #34495e;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logout-btn {
  background: #e74c3c;
  border: none;
  color: white;
  padding: 5px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.rooms-list {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
}

.rooms-list h2 {
  margin-top: 0;
}

.rooms-list ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.rooms-list li {
  padding: 10px;
  margin-bottom: 5px;
  cursor: pointer;
  border-radius: 4px;
}

.rooms-list li:hover {
  background: #34495e;
}

.rooms-list li.active {
  background: #3498db;
}

.create-room-btn {
  margin-top: 15px;
  padding: 10px;
  background: #27ae60;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  width: 100%;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #ddd;
}

.room-header {
  padding: 15px;
  background: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.messages-container {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f9f9f9;
}

.no-messages {
  text-align: center;
  color: #999;
  padding: 20px;
}

.message {
  margin-bottom: 15px;
  padding: 10px;
  border-radius: 8px;
  max-width: 70%;
  background: white;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message.own-message {
  margin-left: auto;
  background: #dcf8c6;
  /* word-wrap: break-word; */
}

.message-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
  font-size: 0.9em;
}

.message-author {
  font-weight: bold;
}

.message-time {
  color: #999;
}

.message-input {
  display: flex;
  padding: 15px;
  border-top: 1px solid #ddd;
}

.message-input input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-right: 10px;
}

.message-input button {
  padding: 10px 20px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.online-users {
  width: 200px;
  padding: 15px;
  background: #f5f5f5;
  overflow-y: auto;
}

.online-users h3 {
  margin-top: 0;
}

.online-users ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.online-users li {
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.user-self {
  font-size: 0.8em;
  color: #27ae60;
  margin-left: 5px;
}

.no-room-selected {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #999;
}

.login-container {
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f5f5f5;
}

.login-form {
  width: 350px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.login-form h2 {
  text-align: center;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
}

.error-message {
  color: #e74c3c;
  margin-bottom: 10px;
}

.login-btn {
  width: 100%;
  padding: 10px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  width: 400px;
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.modal-header {
  padding: 15px;
  background: #f5f5f5;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
}

.modal-body {
  padding: 15px;
}

.create-btn {
  width: 100%;
  padding: 10px;
  background: #27ae60;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
}

.emoji-toggle-btn {
  position: relative;
  background: transparent;
  border: none;
  /* font-size: 20px; */
  cursor: pointer;
  margin-right: 8px;
}

.voice-recorder-toggle-btn {
  position: relative;
  background: transparent;
  border: none;
  /* font-size: 20px; */
  cursor: pointer;
  margin-right: 8px;
}

.music-toggle-btn {
  position: relative;
  background: transparent;
  border: none;
  cursor: pointer;
  margin-right: 8px;
}

.draw-toggle-btn {
  position: relative;
  background: transparent;
  border: none;
  cursor: pointer;
  margin-right: 8px;
}

.Add-user-toggle {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #3498db;
  text-decoration: underline;
}

.voice-recorder-container {
  /* position: absolute; */
  bottom: 300px;
  right: 50px;
  z-index: 10;
}

.emoji-picker-container {
  position: absolute;
  bottom: 60px;
  right: 50px;
  z-index: 10;
}

.music-container {
  position: absolute;
  bottom: 9%;
  right: 10%;
  z-index: 10;
}

.draw-container {
  position: absolute;
  bottom: 9%;
  right: 10%;
  z-index: 10;
}

.add-user-container {
  position: absolute;
  bottom: 68%;
  right: 75px;
  z-index: 10;
}

.loading-more {
  text-align: center;
  padding: 10px;
  color: #888;
  font-size: 0.9em;
}

.messages-container {
  overflow-y: auto;
  max-height: 100%;
  padding: 10px;
  display: flex;
  flex-direction: column;
}

.drawing-preview {
  display: flex;
  max-width: 300px;
  max-height: 300px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
</style>