<?php


namespace App\Subscriber;


use App\Event\FlightCanceledEvent;
use App\Event\TicketsSoldEvent;
use App\Repository\BookingRepository;
use App\Service\MailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FlightEventSubscriber implements EventSubscriberInterface
{

    private $bookingRepository;
    private $mailService;

    public function __construct(BookingRepository $bookingRepository,MailService $mailService){
        $this->bookingRepository = $bookingRepository;
        $this->mailService = $mailService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FlightCanceledEvent::NAME => ['sendCancelationInfo'],
            TicketsSoldEvent::NAME => ['sendSoldInfo'],
        ];
    }

    /**
     * Send emails for subscribers
     */
    public function sendCancelationInfo(FlightCanceledEvent $event){
        $emailList = $this->bookingRepository->getEmailsForFlight($event->getFlightId());
        foreach ($emailList as $emailArray) {
            $email = $emailArray['email'];
            $this->mailService->send($email,'Filght cancelation','Flight number '.$event->getFlightId().' has been canceled.');
        }
    }

    /**
     * For now do nothing
     */
    public function sendSoldInfo(TicketsSoldEvent $event){

    }

}