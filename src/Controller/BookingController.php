<?php


namespace App\Controller;


use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BookingController
 * @package App\Controller
 * @Route("/api", name="booking_api_")
 */
class BookingController extends ApiController
{
    const SECRET_KEY = 'a1b2c3d4e5f6a1b2c3d4e5f6';

    private $user;
    private $validUser = false;

    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $request = Request::createFromGlobals();
        $request = $this->transformJsonBody($request);
        $password = $request->get('password');
        $email = $request->get('email');

        /** @var User $user */
        $user = $userRepository->findOneBy(['email'=>$email]);
        if(!$user){
            return $this->respondUnauthorized('You must authentificate to do this');
        }
        $isValid = $passwordEncoder->isPasswordValid($user, $password);
        if(!$isValid){
            return $this->respondUnauthorized('You must authentificate to do this');
        }
        $this->user = $user;
        $this->validUser = true;
        return null;
    }

    /**
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/booking", name="book_add", methods={"POST"})
     */
    public function addBooking(BookingRepository $bookingRepository,
                               UserRepository $userRepository,
                               ValidatorInterface $validator
    ){
        try {
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($bookingRepository->getFirstVacantSeat());
            if (!$booking) {
                return $this->respondValidationError('All seats are already booked');
            }
            $booking->setUser($this->user)->setStatus(Booking::STATUS_BOOKED);
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
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param int $id
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/booking/{id}", name="book_add_certain", methods={"POST"})
     */
    public function addBookingForCertainSeat(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator, int $id){
        try{
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($id);
            if(!$booking){
                return $this->respondValidationError('Booking not valid');
            }
            if($booking->getStatus()!=Booking::STATUS_VACANT){
                return $this->respondValidationError('Seat is already booked');
            }
            $booking->setUser($this->user)->setStatus(Booking::STATUS_BOOKED);
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
     * @param BookingRepository $bookingRepository
     * @param int $id
     * @Route("/cancel_booking/{id}", name="cancel_booking", methods={"POST"})
     */
    public function cancelBooking(BookingRepository $bookingRepository, int $id){
        try {
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($id);
            if($booking->getUser()->getId()!=$this->user->getId()){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking->setStatus(Booking::STATUS_VACANT);
            $booking = $bookingRepository->save($booking);
            return $this->respondWithSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/buy_ticket", name="buy_ticket", methods={"POST"})
     */
    public function buyTicket(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator){
        try {
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($bookingRepository->getFirstVacantSeat());
            if (!$booking) {
                return $this->respondValidationError('All seats are already booked');
            }
            if($booking->getUser()->getId()!=$this->user->getId()){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking->setStatus(Booking::STATUS_BOUGHT);
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
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param int $id
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/buy_ticket/{id}", name="buy_ticket_certain", methods={"POST"})
     */
    public function buyTicketCertainSeat(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator, int $id){
        try {
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($id);
            if (!$booking) {
                return $this->respondValidationError('Booking not valid');
            }
            if($booking->getUser()->getId()!=$this->user->getId()){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            if ($booking->getStatus() == Booking::STATUS_BOUGHT) {
                return $this->respondValidationError('Ticket is already bought');
            }
            $booking->setStatus(Booking::STATUS_BOUGHT);
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
     * @param BookingRepository $bookingRepository
     * @param int $id
     * @Route("/cancel_ticket/{id}", name="cancel_ticket", methods={"POST"})
     */
    public function cancelTicket(BookingRepository $bookingRepository, int $id){
        try {
            if(!$this->validUser){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking = $bookingRepository->find($id);
            if($booking->getUser()->getId()!=$this->user->getId()){
                return $this->respondUnauthorized('You must authentificate to do this');
            }
            $booking->setStatus(Booking::STATUS_VACANT);
            $booking = $bookingRepository->save($booking);
            return $this->respondWithSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->respondValidationError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @Route("/event", name="event", methods={"POST"})
     */
    public function getEvent(){
        $request = Request::createFromGlobals();
        $request = $this->transformJsonBody($request)->get('data');
        if($request['secret_key']!=self::SECRET_KEY){
            return $this->respondUnauthorized('Wrong application key');
        }
        print_r($request);exit;
    }
}