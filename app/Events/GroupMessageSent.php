<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $groupMessageSent;
    public $rec_id;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($groupMessageSent,$rec_id)
    {
        $this->groupMessageSent = $groupMessageSent;
        $this->rec_id = $rec_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['group'.$this->rec_id];

//        return new PrivateChannel('chat'.$this->rec_id);
    }
}
