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
          // Chuyển đến trang chat sau khi đăng nhập
          this.$router.push('/chatapp');
        } catch (error) {
          this.error = 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.';
          console.error(error);
        } finally {
          this.loading = false;
        }
      }
    }
  };
  </script>