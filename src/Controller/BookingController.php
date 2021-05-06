<?php


namespace App\Controller;


use App\Entity\Booking;
use App\Entity\User;
use App\Event\FlightCanceledEvent;
use App\Event\TicketsSoldEvent;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Subscriber\FlightEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BookingController
 * @package App\Controller
 */
class BookingController extends ApiController
{
    const SECRET_KEY = 'a1b2c3d4e5f6a1b2c3d4e5f6';

    /**
     * Бронирует билет на первое свободное место данного рейса
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/api/booking/{flightId}", name="booking_api_book_add", methods={"POST"})
     */
    public function addBooking(BookingRepository $bookingRepository,
                               UserRepository $userRepository,
                               ValidatorInterface $validator,
                               UserPasswordEncoderInterface $passwordEncoder,
                               int $flightId
    ){
        try {
            $firstVacantSeatId = $bookingRepository->getFirstVacantSeat($flightId);
            $booking = $bookingRepository->find($firstVacantSeatId);
            if (!$booking) {
                return $this->respondValidationError('All seats are already booked');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOOKED);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->respondValidationError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->respondWithSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Бронирует билет на определенное место данного рейса
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $id
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/api/booking/{flightId}/{seatId}", name="booking_api_book_add_certain", methods={"POST"})
     */
    public function addBookingForCertainSeat(BookingRepository $bookingRepository,
                                             UserRepository $userRepository,
                                             ValidatorInterface $validator,
                                             UserPasswordEncoderInterface $passwordEncoder,
                                             int $flightId,
                                             int $seatId
    ){
        try{
            $booking = $bookingRepository->findOneBy(['flightId'=>$flightId,'seat'=>$seatId]);
            if(!$booking){
                return $this->respondValidationError('Booking not valid');
            }
            if($booking->getStatus()!=Booking::STATUS_VACANT){
                return $this->respondValidationError('Seat is already booked');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOOKED);
            $errors = $validator->validate($booking);
            if(count($errors)>0) {
                return $this->respondValidationError((string)$errors);
            }else{
                $booking = $bookingRepository->save($booking);
                return $this->respondWithSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Отменяет бронирование с данным $id
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $id
     * @Route("/api/cancel_booking/{id}", name="booking_api_cancel_booking", methods={"POST"})
     */
    public function cancelBooking(BookingRepository $bookingRepository,UserRepository $userRepository,UserPasswordEncoderInterface $passwordEncoder, int $id){
        try {
            $booking = $bookingRepository->find($id);
            if(!$booking){
                return $this->respondValidationError('Booking not valid');
            }
            if($booking->getStatus()==Booking::STATUS_BOOKED) {
                if($booking->getUser()==null){
                    return $this->respondValidationError('Seat is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            }else{
                return $this->respondValidationError('Seat is not booked');
            }
            if($booking->getStatus()!=Booking::STATUS_BOOKED){
                return $this->respondValidationError('Seat is not booked');
            }
            $booking->setStatus(Booking::STATUS_VACANT)->setUser(null);
            $booking = $bookingRepository->save($booking);
            return $this->respondWithSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Покупает билет на первое свободное место данного рейса
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/api/buy_ticket/{flightId}", name="booking_api_buy_ticket", methods={"POST"})
     */
    public function buyTicket(BookingRepository $bookingRepository,
                              UserRepository $userRepository,
                              ValidatorInterface $validator,
                              UserPasswordEncoderInterface $passwordEncoder,
                              int $flightId
    ){
        try {
            $firstVacantSeatId = $bookingRepository->getFirstVacantSeat($flightId);
            $booking = $bookingRepository->find($firstVacantSeatId);
            if (!$booking) {
                return $this->respondValidationError('All seats are already booked');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOUGHT);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->respondValidationError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->respondWithSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Покупает билет на определенное место данного рейса
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $id
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/api/buy_ticket/{flightId}/{seatId}", name="booking_api_buy_ticket_certain", methods={"POST"})
     */
    public function buyTicketCertainSeat(BookingRepository $bookingRepository,
                                         UserRepository $userRepository,
                                         ValidatorInterface $validator,
                                         UserPasswordEncoderInterface $passwordEncoder,
                                         int $flightId,
                                         int $seatId
    ){
        try {
            $booking = $bookingRepository->findOneBy(['flightId'=>$flightId,'seat'=>$seatId]);
            if (!$booking) {
                return $this->respondValidationError('Booking not valid');
            }
            if($booking->getStatus()==Booking::STATUS_BOOKED) {
                if($booking->getUser()==null){
                    return $this->respondValidationError('Seat is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            }
            if ($booking->getStatus() == Booking::STATUS_BOUGHT) {
                return $this->respondValidationError('Ticket is already bought');
            }
            $user = $this->getUser();
            $booking->setUser($user)->setStatus(Booking::STATUS_BOUGHT);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->respondValidationError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->respondWithSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Отменяет покупку билета с данным id
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $id
     * @Route("/api/cancel_ticket/{id}", name="booking_api_cancel_ticket", methods={"POST"})
     */
    public function cancelTicket(BookingRepository $bookingRepository,UserRepository $userRepository,UserPasswordEncoderInterface $passwordEncoder, int $id){
        try {
            $booking = $bookingRepository->find($id);
            if(!$booking){
                return $this->respondValidationError('Ticket not valid');
            }
            if($booking->getStatus()==Booking::STATUS_BOUGHT) {
                if($booking->getUser()==null){
                    return $this->respondValidationError('Ticket is not valid');
                }
                if ($booking->getUser()->getId() != $this->getUser()->getId()) {
                    return $this->respondUnauthorized('You must authentificate to do this');
                }
            }else{
                return $this->respondValidationError('Ticket is not bought');
            }
            if($booking->getStatus()!=Booking::STATUS_BOUGHT){
                return $this->respondValidationError('Ticket is not bought');
            }
            $booking->setStatus(Booking::STATUS_VACANT)->setUser(null);
            $booking = $bookingRepository->save($booking);
            return $this->respondWithSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }


    /**
     * @param BookingRepository $bookingRepository
     * @param MailService $mailService
     * @return JsonResponse
     * @Route("/api/event", name="booking_api_event", methods={"POST"})
     */
    public function getEvent(BookingRepository $bookingRepository, MailService $mailService){
        $request = Request::createFromGlobals();
        $request = $this->transformJsonBody($request)->get('data');
        if($request['secret_key']!=self::SECRET_KEY){
            return $this->respondUnauthorized('Wrong authorization key');
        }
        $flightId = $request['flight_id'];
        $eventType = $request['event'];
        $dispatcher = new EventDispatcher();
        $subscriber = new FlightEventSubscriber($bookingRepository, $mailService);
        $dispatcher->addSubscriber($subscriber);
        if($eventType=='flight_ticket_sales_completed'){
            $event = new TicketsSoldEvent($flightId);
            $eventName = TicketsSoldEvent::NAME;
            $dispatcher->dispatch($event,$eventName);
        }
        if($eventType=='flight_canceled'){
            $event = new FlightCanceledEvent($flightId);
            $eventName = FlightCanceledEvent::NAME;
            $dispatcher->dispatch($event,$eventName);
        }
        return $this->respondWithSuccess('Message received');
    }
}