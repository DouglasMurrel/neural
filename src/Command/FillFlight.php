<?php

namespace App\Command;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FillFlight extends Command
{
    protected static $defaultName = 'flight:fill';

    private $repository;

    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('flight', InputArgument::REQUIRED, 'The username of the user.')
            // ...
        ;
    }

    public function __construct(BookingRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        for ($i = Booking::FIRST_SEAT; $i <= Booking::NUMBER_OF_SEATS; ++$i) {
            $flight = (int) ($input->getArgument('flight'));
            $booking = new Booking();
            $booking->setFlightId($flight)->setSeat($i)->setStatus(Booking::STATUS_VACANT);
            $this->repository->save($booking, true);
        }

        return Command::SUCCESS;
    }
}
