<!-- resources/js/components/Login.vue -->
<template>
    <div class="login-form">
      <h2>Đăng nhập để sử dụng chat app</h2>
      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" v-model="form.email" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" v-model="form.password" required />
        </div>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
      </form>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        form: {
          email: '',
          password: ''
        },
        loading: false,
        error: null
      };
    },
    methods: {
      async handleLogin() {
        this.loading = true;
        this.error = null;
        
        try {
          await this.$store.dispatch('login', this.form);
          this.$router.push('/chatapp');
        } catch (error) {
          console.log('error', error);
          this.error = 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.';
          console.error(error);
        } finally {
          this.loading = false;
        }
      }
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
</style>