<?php

namespace ErrorHeroModule\Mailer;

use Zend\Mail\Message;

class Sender
{
    private $message;
    private $transport;

    public function __construct(
        Message $message,
        Transport $transpost
    ) {
        
    }

    public function send()
    {
        $this->transport->send($message);
    }
}
