<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

class TicketsSoldEvent extends Event
{
    public const NAME = 'tickets.sold';

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