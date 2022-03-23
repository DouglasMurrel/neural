<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Event\FlightCanceledEvent;
use App\Event\TicketsSoldEvent;
use App\Repository\BookingRepository;
use App\Service\MailService;
use App\Subscriber\FlightEventSubscriber;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BookingController
 *
 * @Route("/api", name="booking_api_")
 */
class BookingController extends ApiController
{
    /**
     * Бронирует билет на первое свободное место данного рейса
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/booking/{flightId}", name="book_add", methods={"POST"})
     */
    public function addBooking(BookingRepository $bookingRepository,
                               ValidatorInterface $validator,
                               EntityManagerInterface $em,
                               int $flightId
    ) {
        try {
            $user = $this->getUser();

            return $em->transactional(function () use ($bookingRepository,$validator,$flightId, $user) {
                while (true) {
                    $firstVacantSeatId = $bookingRepository->getFirstVacantSeat($flightId);
                    $booking = $bookingRepository->find($firstVacantSeatId, LockMode::PESSIMISTIC_WRITE);
                    if (!$booking) {
                        break;
                    }
                    if (Booking::STATUS_VACANT == $booking->getStatus()) {
                        break;
                    }
                }
                if (!$booking) {
                    return $this->respondValidationError('All seats are already booked');
                }
                $booking->setUser($user)->setStatus(Booking::STATUS_BOOKED);
                $errors = $validator->validate($booking);
                if (count($errors) > 0) {
                    return $this->respondValidationError((string) $errors);
                } else {
                    $booking = $bookingRepository->save($booking);

                    return $this->respondWithSuccess($booking->getId());
                }
            });
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Бронирует билет на определенное место данного рейса
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/booking/{flightId}/{seatId}", name="book_add_certain", methods={"POST"})
     */
    public function addBookingForCertainSeat(BookingRepository $bookingRepository,
                                             ValidatorInterface $validator,
                                             int $flightId,
                                             int $seatId
    ) {
        try {
            $booking = $bookingRepository->findOneBy(['flightId' => $flightId, 'seat' => $seatId]);
            if (!$booking) {
                return $this->respondValidationError('Booking not valid');
            }
            if (Booking::STATUS_VACANT != $booking->getStatus()) {
                return $this->respondValidationError('Seat is already booked');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOOKED);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->respondValidationError((string) $errors);
            } else {
                $booking = $bookingRepository->save($booking);

                return $this->respondWithSuccess($booking->getId());
            }
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Отменяет бронирование с данным $id
     *
     * @Route("/cancel_booking/{id}", name="cancel_booking", methods={"POST"})
     */
    public function cancelBooking(BookingRepository $bookingRepository, int $id)
    {
        try {
            $booking = $bookingRepository->find($id);
            if (!$booking) {
                return $this->respondValidationError('Booking not valid');
            }
            if (Booking::STATUS_BOOKED == $booking->getStatus()) {
                if (null == $booking->getUser()) {
                    return $this->respondValidationError('Seat is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            } else {
                return $this->respondValidationError('Seat is not booked');
            }
            if (Booking::STATUS_BOOKED != $booking->getStatus()) {
                return $this->respondValidationError('Seat is not booked');
            }
            $booking->setStatus(Booking::STATUS_VACANT)->setUser(null);
            $booking = $bookingRepository->save($booking);

            return $this->respondWithSuccess($booking->getId());
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Покупает билет на первое свободное место данного рейса
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/buy_ticket/{flightId}", name="buy_ticket", methods={"POST"})
     */
    public function buyTicket(BookingRepository $bookingRepository,
                              ValidatorInterface $validator,
                              EntityManagerInterface $em,
                              int $flightId
    ) {
        $user = $this->getUser();
        try {
            return $em->transactional(function () use ($bookingRepository,$validator,$flightId, $user) {
                while (true) {
                    $firstVacantSeatId = $bookingRepository->getFirstVacantSeat($flightId);
                    $booking = $bookingRepository->find($firstVacantSeatId, LockMode::PESSIMISTIC_WRITE);
                    if (!$booking) {
                        break;
                    }
                    if (Booking::STATUS_VACANT == $booking->getStatus()) {
                        break;
                    }
                }
                if (!$booking) {
                    return $this->respondValidationError('All seats are already booked');
                }
                $booking->setUser($user)->setStatus(Booking::STATUS_BOUGHT);
                $errors = $validator->validate($booking);
                if (count($errors) > 0) {
                    return $this->respondValidationError((string) $errors);
                } else {
                    $booking = $bookingRepository->save($booking);

                    return $this->respondWithSuccess($booking->getId());
                }
            });
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Покупает билет на определенное место данного рейса
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/buy_ticket/{flightId}/{seatId}", name="buy_ticket_certain", methods={"POST"})
     */
    public function buyTicketCertainSeat(BookingRepository $bookingRepository,
                                         ValidatorInterface $validator,
                                         int $flightId,
                                         int $seatId
    ) {
        try {
            $booking = $bookingRepository->findOneBy(['flightId' => $flightId, 'seat' => $seatId]);
            if (!$booking) {
                return $this->respondValidationError('Booking not valid');
            }
            if (Booking::STATUS_BOOKED == $booking->getStatus()) {
                if (null == $booking->getUser()) {
                    return $this->respondValidationError('Seat is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            }
            if (Booking::STATUS_BOUGHT == $booking->getStatus()) {
                return $this->respondValidationError('Ticket is already bought');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOUGHT);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->respondValidationError((string) $errors);
            } else {
                $booking = $bookingRepository->save($booking);

                return $this->respondWithSuccess($booking->getId());
            }
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Отменяет покупку билета с данным id
     *
     * @Route("/cancel_ticket/{id}", name="cancel_ticket", methods={"POST"})
     */
    public function cancelTicket(BookingRepository $bookingRepository, int $id)
    {
        try {
            $booking = $bookingRepository->find($id);
            if (!$booking) {
                return $this->respondValidationError('Ticket not valid');
            }
            if (Booking::STATUS_BOUGHT == $booking->getStatus()) {
                if (null == $booking->getUser()) {
                    return $this->respondValidationError('Ticket is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            } else {
                return $this->respondValidationError('Ticket is not bought');
            }
            if (Booking::STATUS_BOUGHT != $booking->getStatus()) {
                return $this->respondValidationError('Ticket is not bought');
            }
            $booking->setStatus(Booking::STATUS_VACANT)->setUser(null);
            $booking = $bookingRepository->save($booking);

            return $this->respondWithSuccess($booking->getId());
        } catch (\Exception $e) {
            return $this->respondValidationError('Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * @return JsonResponse
     * @Route("/event", name="event", methods={"POST"})
     */
    public function getEvent(BookingRepository $bookingRepository, MailService $mailService)
    {
        $request = Request::createFromGlobals();
        $request = $this->transformJsonBody($request)->get('data');
        if ($request['secret_key'] != $this->getParameter('secret_api_key')) {
            return $this->respondUnauthorized('Wrong authorization key');
        }
        $flightId = $request['flight_id'];
        $eventType = $request['event'];
        $dispatcher = new EventDispatcher();
        $subscriber = new FlightEventSubscriber($bookingRepository, $mailService);
        $dispatcher->addSubscriber($subscriber);
        if ('flight_ticket_sales_completed' == $eventType) {
            $event = new TicketsSoldEvent($flightId);
            $eventName = TicketsSoldEvent::NAME;
            $dispatcher->dispatch($event, $eventName);
        }
        if ('flight_canceled' == $eventType) {
            $event = new FlightCanceledEvent($flightId);
            $eventName = FlightCanceledEvent::NAME;
            $dispatcher->dispatch($event, $eventName);
        }

        return $this->respondWithSuccess('Message received');
    }
}
