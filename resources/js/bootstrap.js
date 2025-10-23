import axios from 'axios';
import Pusher from 'pusher-js';
import Echo from 'laravel-echo';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb', 
    key: import.meta.env.VITE_REVERB_APP_KEY, 
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https', 
    enabledTransports: ['ws'], 
    disableStats: true,
});

// Bật log để debug
window.Pusher.logToConsole = true;

// Log cấu hình để kiểm tra
console.log('Echo config:', {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME,
});

axios.defaults.withCredentials = true;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Thêm CSRF token vào tất cả requests
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.error('CSRF token not found');
}