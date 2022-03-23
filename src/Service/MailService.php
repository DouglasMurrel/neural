<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send($address, $subject, $body)
    {
        $email = (new Email())
            ->to($address)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }
}
