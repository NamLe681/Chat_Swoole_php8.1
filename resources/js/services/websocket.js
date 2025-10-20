class WebSocketService {
    constructor() {
        this.connection = null;
        this.connected = false;
        this.callbacks = {
            message: () => {},
            open: () => {},
            close: () => {},
            error: () => {},
        };
    }
    
    connect(userId) {
        if (this.connection && this.connected) {
            return Promise.resolve();
        }
        
        // URL relative đến domain hiện tại
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.host}/chat?user_id=${userId}`;
        
        return new Promise((resolve, reject) => {
            this.connection = new WebSocket(wsUrl);
            
            this.connection.onopen = (event) => {
                console.log('WebSocket đã kết nối');
                this.connected = true;
                this.callbacks.open(event);
                resolve();
            };
            
            this.connection.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.callbacks.message(data);
            };
            
            this.connection.onclose = (event) => {
                console.log('WebSocket đã ngắt kết nối');
                this.connected = false;
                this.callbacks.close(event);
            };
            
            this.connection.onerror = (error) => {
                console.error('Lỗi WebSocket:', error);
                this.callbacks.error(error);
                reject(error);
            };
        });
    }
    
    disconnect() {
        if (this.connection) {
            this.connection.close();
        }
    }
    
    send(data) {
        if (!this.connected) {
            throw new Error('WebSocket chưa kết nối');
        }
        
        this.connection.send(JSON.stringify(data));
    }
    
    onMessage(callback) {
        this.callbacks.message = callback;
    }
    
    joinRoom(roomId) {
        this.send({
            action: 'join_room',
            room_id: roomId
        });
    }
    
    leaveRoom(roomId) {
        this.send({
            action: 'leave_room',
            room_id: roomId
        });
    }
    
    sendMessage(roomId, content) {
        this.send({
            action: 'send_message',
            room_id: roomId,
            content: content
        });
    }
}

export default new WebSocketService();