<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SystemError
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Throwable
     */
    public $throwable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
    }
}
