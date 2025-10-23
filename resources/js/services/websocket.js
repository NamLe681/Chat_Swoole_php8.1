class WebSocketService {
    constructor() {
        this.connection = null;
        this.connected = false;
        this.reconnectDelay = 3000; // 3s
        this.messageQueue = [];
        this.callbacks = {
            message: () => {},
            open: () => {},
            close: () => {},
            error: () => {},
        };
        this.userId = null;
    }

    connect(userId) {
        if (this.connected) {
            console.log('WebSocket đã kết nối trước đó');
            return Promise.resolve();
        }

        this.userId = userId;

        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//127.0.0.1:8080/app/2067528?user_id=${userId}`;

        return new Promise((resolve, reject) => {
            console.log('Đang kết nối WebSocket...', wsUrl);
            this.connection = new WebSocket(wsUrl);

            this.connection.onopen = (event) => {
                console.log('WebSocket kết nối thành công');
                this.connected = true;
                this.callbacks.open(event);

                // Gửi lại các message trong queue
                this.messageQueue.forEach(msg => this.send(msg));
                this.messageQueue = [];

                resolve();
            };

            this.connection.onmessage = (event) => {
                let data;
                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    console.warn('Không parse được data WebSocket', event.data);
                    return;
                }
                console.log('WebSocket nhận message:', data);
                this.callbacks.message(data);
            };

            this.connection.onclose = (event) => {
                console.warn('WebSocket đã đóng kết nối', event);
                this.connected = false;
                this.callbacks.close(event);

                // Thử reconnect
                setTimeout(() => {
                    console.log('Thử reconnect WebSocket...');
                    this.connect(this.userId).catch(err => console.error(err));
                }, this.reconnectDelay);
            };

            this.connection.onerror = (error) => {
                console.error('Lỗi WebSocket:', error);
                this.callbacks.error(error);
                // Không reject promise nếu reconnect, chỉ log
                if (!this.connected) reject(error);
            };
        });
    }

    disconnect() {
        if (this.connection) {
            console.log('Đang đóng WebSocket...');
            this.connection.close();
            this.connection = null;
            this.connected = false;
        }
    }

    send(data) {
        if (!this.connected) {
            console.warn('WebSocket chưa kết nối, lưu vào queue:', data);
            this.messageQueue.push(data);
            return;
        }
        this.connection.send(JSON.stringify(data));
    }

    onMessage(callback) {
        this.callbacks.message = callback;
    }

    onOpen(callback) {
        this.callbacks.open = callback;
    }

    onClose(callback) {
        this.callbacks.close = callback;
    }

    onError(callback) {
        this.callbacks.error = callback;
    }

    joinRoom(roomId) {
        this.send({ action: 'join_room', room_id: roomId });
    }

    leaveRoom(roomId) {
        this.send({ action: 'leave_room', room_id: roomId });
    }

    sendMessage(roomId, content) {
        this.send({ action: 'send_message', room_id: roomId, content });
    }
}

export default new WebSocketService();
