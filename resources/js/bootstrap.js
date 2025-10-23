import axios from 'axios';
import Pusher from 'pusher-js';
import Echo from 'laravel-echo';


window.axios = axios;
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.error('CSRF token not found');
}

window.Pusher = Pusher;
window.Pusher.logToConsole = true; // Debug Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws'],
    disableStats: true,
    authEndpoint: '/broadcasting/auth',
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                console.log('Authorizing channel:', channel.name, 'Socket ID:', socketId); // Debug
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken?.content
                    }
                })
                .then(response => {
                    console.log('Auth success:', response.data);
                    callback(null, response.data);
                })
                .catch(error => {
                    console.error('Auth error:', error.response?.data || error.message);
                    callback(error, null);
                });
            }
        };
    },
});

console.log('Echo config:', window.Echo.connector.options);