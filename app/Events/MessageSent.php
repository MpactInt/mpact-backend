<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $messageSent;
    public $rec_id;
    public $first_name;
    public $last_name;
    public $profile_image;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($messageSent,$rec_id,$first_name,$last_name,$profile_image)
    {
        $this->messageSent = $messageSent;
        $this->rec_id = $rec_id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->profile_image = $profile_image;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['chat'.$this->rec_id];

//        return new PrivateChannel('chat'.$this->rec_id);
    }
}
