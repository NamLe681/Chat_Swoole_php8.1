// resources/js/app.js
import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import store from './store';
import ChatApp from './components/ChatApp.vue';

// Tạo CSRF token cho tất cả request axios
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

// Tạo router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: ChatApp
        }
    ]
});

// Tạo app Vue
const app = createApp({});

// Đăng ký component
app.component('chat-app', ChatApp);

// Sử dụng Vuex và router
app.use(store);
app.use(router);

// Mount app
app.mount('#app');

// Lấy thông tin người dùng hiện tại nếu đã đăng nhập
store.dispatch('fetchCurrentUser');