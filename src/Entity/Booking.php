<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=BookingRepository::class)
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="seat_flight", columns={"seat","flight_id"})})
 */
class Booking
{
    const STATUS_VACANT = 0;
    const STATUS_BOOKED = 1;
    const STATUS_BOUGHT = 2;
    const FIRST_SEAT = 1;
    const NUMBER_OF_SEATS = 150;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", options={"default"=0})
     * @Assert\LessThanOrEqual(self::NUMBER_OF_SEATS)
     * @Assert\GreaterThanOrEqual(self::FIRST_SEAT)
     */
    private $seat;

    /**
     * @ORM\Column(type="integer", options={"default"=0})
     * @Assert\LessThanOrEqual(self::STATUS_BOUGHT)
     * @Assert\GreaterThanOrEqual(self::STATUS_VACANT)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="bookings")
     * @ORM\JoinColumn
     */
    private $user;

    /**
     * @ORM\Column(type="integer", options={"default"=0})
     */
    private $flightId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeat(): ?int
    {
        return $this->seat;
    }

    public function setSeat(int $seat): self
    {
        $this->seat = $seat;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFlightId(): ?int
    {
        return $this->flightId;
    }

    public function setFlightId(int $flightId): self
    {
        $this->flightId = $flightId;

        return $this;
    }
}
