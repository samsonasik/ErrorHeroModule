<?php

namespace ErrorHeroModule\Spec\Handler\Writer;

use ErrorHeroModule\Handler\Writer\Mail;
use Kahlan\Plugin\Double;
use ReflectionProperty;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;

describe('Mail', function () {

   beforeAll(function () {
        $this->mailMessage   = Double::instance(['extends' => Message::class]);
        $this->transport = Double::instance(['implements' => TransportInterface::class]);

        $this->writer = new Mail(
            $this->mailMessage,
            $this->transport,
            [
                'query'          => [],
                'request_method' => 'POST',
                'body_data'      => ['text' => 'test'],
                'raw_data'       => [],
                'files_data'     => [
                    "file" => [
                        'name' => 'foo.html',
                        'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                        'error'    => 0,
                        'size'     => 1,
                        'type'     => 'text/html'
                     ],
                 ],
            ]
        );
   });

   describe('->shutdown', function () {

        it('return early when eventsToMail is empty', function () {
            
            $r = new ReflectionProperty($this->writer, 'eventsToMail');
            $r->setAccessible(true);
            $r->setValue($this->writer, []);

            $this->writer->shutdown();
            expect($this->transport)->not->toReceive('send');
            
        });

   });

});
