import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import store from './store';
import App from './App.vue';
import ChatApp from './components/ChatApp.vue';
import Login from './components/Login.vue';
import RegisterComponent from './components/RegisterComponent.vue';
import axios from 'axios';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            component: Login,
        },
        {
            path: '/register',
            component: RegisterComponent,
        },
        {
            path: '/chatapp',
            component: ChatApp,
        }
    ]
});


const app = createApp(App);
app.use(store);
app.use(router);
app.mount('#app');

console.log('Vue app mounted');


