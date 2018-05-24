<?php

namespace ErrorHeroModule\Spec\Handler\Writer;

use ErrorHeroModule\Handler\Writer\Mail;
use Exception;
use Kahlan\Plugin\Double;
use ReflectionProperty;
use Zend\Http\Header\Cookie;
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
                    [
                        'name' => 'foo.html',
                        'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                        'error'    => 0,
                        'size'     => 1,
                        'type'     => 'text/html'
                     ],
                 ],
                 'cookie_data' => (new Cookie([]))->getArrayCopy(),
            ]
        );
   });

   describe('->shutdown', function () {

        it('bring multiple collection upload, then transport->send()', function () {

            $writer = new Mail(
                $this->mailMessage,
                $this->transport,
                [
                    'query'          => [],
                    'request_method' => 'POST',
                    'body_data'      => ['text' => 'test'],
                    'raw_data'       => [],
                    'files_data'     => [
                        "file-collection" => [
                            [
                                'name'     => 'foo.html',
                                'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                                'error'    => 0,
                                'size'     => 1,
                                'type'     => 'text/html'
                            ],
                         ],
                     ],
                     'cookie_data' => (new Cookie([]))->getArrayCopy(),
                ]
            );

            $r = new ReflectionProperty($this->writer, 'eventsToMail');
            $r->setAccessible(true);
            $r->setValue($writer, ["timestamp" => "2017-02-25T02:08:46+07:00"]);

            allow($this->transport)->toReceive('send');

            $writer->shutdown();

            expect($this->transport)->toReceive('send');

        });

         it('transport->send() trigger error', function () {

            $r = new ReflectionProperty($this->writer, 'eventsToMail');
            $r->setAccessible(true);
            $r->setValue($this->writer, ["timestamp" => "2017-02-25T02:08:46+07:00"]);

            allow($this->transport)->toReceive('send')->andRun(function () { throw new Exception('test'); });

            try {
                $this->writer->shutdown();
                expect($this->transport)->toReceive('send');
            } catch (\Throwable $t) {
                expect($t)->toBeAnInstanceOf(Exception::class);
            }

        });

   });

});
