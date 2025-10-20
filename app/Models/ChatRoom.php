<?php

// app/Models/ChatRoom.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description'];
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'room_user', 'room_id', 'user_id')
                    ->withTimestamps();
    }
    
    public function messages()
    {
        return $this->hasMany(Message::class, 'room_id');
    }
}
