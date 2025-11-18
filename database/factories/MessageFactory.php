<?php

namespace Database\Factories;

use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_id' => ChatRoom::factory(),
            'content' => $this->faker->sentence(),
            'type' => 'text',
        ];
    }
}
