<?php


namespace App\Controller;


use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BookingController
 * @package App\Controller
 * @Route("/api", name="booking_api_")
 */
class BookingController extends AbstractController
{

    /**
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/booking", name="book_add", methods={"GET","POST"})
     */
    public function addBooking(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator){
        try {
            $booking = $bookingRepository->find($bookingRepository->getFirstVacantSeat());
            if (!$booking) {
                return $this->reportError('All seats are already booked');
            }
            $booking->setUser($userRepository->find(1));
            $booking->setStatus(Booking::STATUS_BOOKED);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->reportError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->reportSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
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
     * @Route("/booking/{id}", name="book_add_certain", methods={"GET","POST"})
     */
    public function addBookingForCertainSeat(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator, int $id){
        try{
            $booking = $bookingRepository->find($id);
            if(!$booking){
                return $this->reportError('Booking not valid');
            }
            if($booking->getStatus()!=Booking::STATUS_VACANT){
                return $this->reportError('Seat is already booked');
            }
            $booking->setUser($userRepository->find(1));
            $booking->setStatus(Booking::STATUS_BOOKED);
            $errors = $validator->validate($booking);
            if(count($errors)>0) {
                return $this->reportError((string)$errors);
            }else{
                $booking = $bookingRepository->save($booking);
                return $this->reportSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @param BookingRepository $bookingRepository
     * @param int $id
     * @Route("/cancel_booking/{id}", name="cancel_booking", methods={"GET","POST"})
     */
    public function cancelBooking(BookingRepository $bookingRepository, int $id){
        try {
            $booking = $bookingRepository->find($id);
            $booking->setStatus(Booking::STATUS_VACANT);
            $booking = $bookingRepository->save($booking);
            return $this->reportSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @param BookingRepository $bookingRepository
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/buy_ticket", name="buy_ticket", methods={"GET","POST"})
     */
    public function buyTicket(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator){
        try {
            $booking = $bookingRepository->find($bookingRepository->getFirstVacantSeat());
            if (!$booking) {
                return $this->reportError('All seats are already booked');
            }
            $booking->setUser($userRepository->find(1));
            $booking->setStatus(Booking::STATUS_BOUGHT);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->reportError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->reportSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
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
     * @Route("/buy_ticket/{id}", name="buy_ticket_certain", methods={"GET","POST"})
     */
    public function buyTicketCertainSeat(BookingRepository $bookingRepository, UserRepository $userRepository, ValidatorInterface $validator, int $id){
        try {
            $booking = $bookingRepository->find($id);
            if (!$booking) {
                return $this->reportError('Booking not valid');
            }
            if ($booking->getStatus() == Booking::STATUS_BOUGHT) {
                return $this->reportError('Ticket is already bought');
            }
            $booking->setUser($userRepository->find(1));
            $booking->setStatus(Booking::STATUS_BOUGHT);
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                return $this->reportError((string)$errors);
            } else {
                $booking = $bookingRepository->save($booking);
                return $this->reportSuccess($booking->getId());
            }
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @param BookingRepository $bookingRepository
     * @param int $id
     * @Route("/cancel_ticket/{id}", name="cancel_ticket", methods={"GET","POST"})
     */
    public function cancelTicket(BookingRepository $bookingRepository, int $id){
        try {
            $booking = $bookingRepository->find($id);
            $booking->setStatus(Booking::STATUS_VACANT);
            $booking = $bookingRepository->save($booking);
            return $this->reportSuccess($booking->getId());
        }catch(\Exception $e) {
            return $this->reportError('Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * @param $message
     * @return JsonResponse
     */
    private function reportSuccess($message){
        $data = [
            'status' => 200,
            'message' => $message,
        ];
        return new JsonResponse($data, 200);
    }

    /**
     * @param $message
     * @return JsonResponse
     */
    private function reportError($message){
        $data = [
            'status' => 422,
            'error' => $message,
        ];
        return new JsonResponse($data, 422);
    }
}