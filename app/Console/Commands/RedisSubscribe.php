<?php

// app/Console/Commands/RedisSubscribe.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use SwooleTW\Http\Websocket\Facades\Room;

class RedisSubscribe extends Command
{
    protected $signature = 'redis:subscribe';
    protected $description = 'Subscribe to Redis channels for chat synchronization';

    public function handle()
    {
        $this->info('Đang lắng nghe kênh Redis...');
        
        Redis::subscribe(['chat'], function ($message) {
            $data = json_decode($message, true);
            
            if ($data['event'] === 'message.created') {
                $messageData = $data['data'];
                $roomId = $messageData['room_id'];
                
                // Broadcast tin nhắn đến tất cả client WebSocket
                Room::broadcast('room_' . $roomId, json_encode($messageData));
            }
        });
        
        return 0;
    }
}