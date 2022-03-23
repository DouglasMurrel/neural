<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FlightCanceledEvent extends Event
{
    public const NAME = 'flight.canceled';

    protected $flightId;

    public function __construct(int $flightId)
    {
        $this->flightId = $flightId;
    }

    public function getFlightId(): int
    {
        return $this->flightId;
    }
}
