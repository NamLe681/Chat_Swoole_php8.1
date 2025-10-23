<?php
namespace App\WebSocket;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SwooleTW\Http\Websocket\HandlerContract;
use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;

class ChatWebSocketHandler implements HandlerContract
{
    protected $users = [];

    /**
     * Connect handler - Xử lý khi client kết nối
     */
    public function connect(Request $request, int $fd)
    {
        // Kiểm tra xác thực
        $userID = $request->query('id');
        $user = User::find($userID);
        
        if (!$user) {
            return false;
        }
        
        // Lưu thông tin user
        $this->users[$fd] = [
            'id' => $user->id,
            'name' => $user->name,
            'rooms' => [],
        ];
        
        return true;
    }

    /**
     * Message handler - Xử lý khi nhận được message
     */
    public function message(RoomContract $room, string $message, int $fd)
    {
        $data = json_decode($message, true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'join_room':
                return $this->joinRoom($room, $data, $fd);
                
            case 'leave_room':
                return $this->leaveRoom($room, $data, $fd);
                
            case 'send_message':
                return $this->sendMessage($room, $data, $fd);
                
            default:
                return 'Unknown action';
        }
    }

    /**
     * Close handler - Xử lý khi client ngắt kết nối
     */
    public function close(RoomContract $room, int $fd, $reactorId)
    {
        // Xóa user khỏi tất cả rooms
        if (isset($this->users[$fd])) {
            foreach ($this->users[$fd]['rooms'] as $roomId) {
                $room->delete($fd, 'room_' . $roomId);
                
                // Thông báo cho các users khác
                $broadcastData = json_encode([
                    'type' => 'user_left',
                    'user' => [
                        'id' => $this->users[$fd]['id'],
                        'name' => $this->users[$fd]['name'],
                    ],
                    'room_id' => $roomId,
                    'timestamp' => now()->toDateTimeString(),
                ]);
                
                $room->broadcast('room_' . $roomId, $broadcastData, $fd);
            }
            
            // Xóa user khỏi danh sách
            unset($this->users[$fd]);
        }
    }
    
    /**
     * Xử lý yêu cầu tham gia room
     */
    protected function joinRoom(RoomContract $room, array $data, int $fd)
    {
        $roomId = $data['room_id'] ?? null;
        
        if (!$roomId) {
            return json_encode(['error' => 'Room ID is required']);
        }
        
        // Kiểm tra room tồn tại
        $chatRoom = ChatRoom::find($roomId);
        if (!$chatRoom) {
            return json_encode(['error' => 'Room not found']);
        }
        
        // Thêm user vào room
        $room->add($fd, 'room_' . $roomId);
        
        // Cập nhật danh sách room của user
        if (!in_array($roomId, $this->users[$fd]['rooms'])) {
            $this->users[$fd]['rooms'][] = $roomId;
        }
        
        // Thêm user vào room trong database
        $user = User::find($this->users[$fd]['id']);
        if (!$user->rooms->contains($roomId)) {
            $user->rooms()->attach($roomId);
        }
        
        // Lấy lịch sử tin nhắn
        $messages = Message::where('room_id', $roomId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                    ],
                    'created_at' => $message->created_at->toDateTimeString(),
                ];
            });
        
        // Thông báo cho các user khác về user mới
        $broadcastData = json_encode([
            'type' => 'user_joined',
            'user' => [
                'id' => $this->users[$fd]['id'],
                'name' => $this->users[$fd]['name'],
            ],
            'room_id' => $roomId,
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        $room->broadcast('room_' . $roomId, $broadcastData, $fd);
        
        // Trả về thông tin room và lịch sử tin nhắn
        return json_encode([
            'type' => 'join_room_success',
            'room' => [
                'id' => $chatRoom->id,
                'name' => $chatRoom->name,
                'description' => $chatRoom->description,
            ],
            'messages' => $messages,
        ]);
    }
    
    /**
     * Xử lý yêu cầu rời room
     */
    protected function leaveRoom(RoomContract $room, array $data, int $fd)
    {
        $roomId = $data['room_id'] ?? null;
        
        if (!$roomId) {
            return json_encode(['error' => 'Room ID is required']);
        }
        
        // Xóa user khỏi room
        $room->delete($fd, 'room_' . $roomId);
        
        // Cập nhật danh sách room của user
        if (($key = array_search($roomId, $this->users[$fd]['rooms'])) !== false) {
            unset($this->users[$fd]['rooms'][$key]);
        }
        
        // Xóa user khỏi room trong database
        $user = User::find($this->users[$fd]['id']);
        $user->rooms()->detach($roomId);
        
        // Thông báo cho các user khác
        $broadcastData = json_encode([
            'type' => 'user_left',
            'user' => [
                'id' => $this->users[$fd]['id'],
                'name' => $this->users[$fd]['name'],
            ],
            'room_id' => $roomId,
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        $room->broadcast('room_' . $roomId, $broadcastData, $fd);
        
        return json_encode([
            'type' => 'leave_room_success',
            'room_id' => $roomId,
        ]);
    }
    
    /**
     * Xử lý yêu cầu gửi tin nhắn
     */
    protected function sendMessage(RoomContract $room, array $data, int $fd)
    {
        $roomId = $data['room_id'] ?? null;
        $content = $data['content'] ?? '';
        
        if (!$roomId) {
            return json_encode(['error' => 'Room ID is required']);
        }
        
        if (empty($content)) {
            return json_encode(['error' => 'Message content is required']);
        }
        
        // Kiểm tra user có trong room không
        if (!in_array($roomId, $this->users[$fd]['rooms'])) {
            return json_encode(['error' => 'You are not in this room']);
        }
        
        // Lưu tin nhắn vào database
        $message = new Message([
            'user_id' => $this->users[$fd]['id'],
            'room_id' => $roomId,
            'content' => $content,
        ]);
        $message->save();
        
        // Chuẩn bị dữ liệu tin nhắn để broadcast
        $messageData = [
            'type' => 'new_message',
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'user' => [
                    'id' => $this->users[$fd]['id'],
                    'name' => $this->users[$fd]['name'],
                ],
                'created_at' => $message->created_at->toDateTimeString(),
            ],
            'room_id' => $roomId,
        ];
        
        // Broadcast tin nhắn đến tất cả users trong room
        $room->broadcast('room_' . $roomId, json_encode($messageData));
        
        // Publish tin nhắn đến Redis để đồng bộ giữa các server
        Redis::publish('chat', json_encode([
            'event' => 'message.created',
            'data' => $messageData,
        ]));
        
        return json_encode([
            'type' => 'send_message_success',
            'message_id' => $message->id,
        ]);
    }
}