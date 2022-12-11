<?php

namespace ErrorHeroModule\Spec\Handler\Writer;

use ErrorHeroModule\Handler\Writer\Mail;
use Exception;
use Kahlan\Plugin\Double;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use ReflectionProperty;
use Throwable;

describe('Mail', function () {

   beforeAll(function (): void {
        $this->mailMessage   = Double::instance(['extends' => Message::class]);
        $this->transport = Double::instance(['implements' => TransportInterface::class]);

        $this->writer = new Mail(
            $this->mailMessage,
            $this->transport,
            [
                "fileupload" => [
                    'name' => 'foo.html',
                    'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                    'error'    => 0,
                    'size'     => 1,
                    'type'     => 'text/html'
                ],
            ]
        );
   });

   describe('->shutdown', function () {

        it('bring multiple collection upload, then transport->send()', function (): void {

            $writer = new Mail(
                $this->mailMessage,
                $this->transport,
                [
                    "file-collection" => [
                        [
                            'name'     => 'foo.html',
                            'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                            'error'    => 0,
                            'size'     => 1,
                            'type'     => 'text/html'
                        ],
                    ],
                    "file-collection2" => [
                        "name" => [
                            [
                                'name'     => 'foo.html',
                                'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                                'error'    => 0,
                                'size'     => 1,
                                'type'     => 'text/html'
                            ]
                        ],
                    ],
                ]
            );

            $r = new ReflectionProperty($this->writer, 'eventsToMail');
            $r->setAccessible(true);
            $r->setValue($writer, ["timestamp" => "2017-02-25T02:08:46+07:00"]);

            allow($this->transport)->toReceive('send');

            $writer->shutdown();

            expect($this->transport)->toReceive('send');

        });

        it('bring deeper multiple collection upload, then transport->send()', function (): void {

            $writer = new Mail(
                $this->mailMessage,
                $this->transport,
                [
                    "file-collection" => [
                        'file-collection-deeper' => [
                            'file-collection-deeper-deep' => [
                                [
                                    'name'     => 'foo.html',
                                    'tmp_name' => __DIR__ . '/../../Fixture/data/foo.html',
                                    'error'    => 0,
                                    'size'     => 1,
                                    'type'     => 'text/html'
                                ],
                            ],
                        ],
                    ],
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

            allow($this->transport)->toReceive('send')->andRun(function (): never { throw new Exception('root'); });

            try {
                $this->writer->shutdown();
                expect($this->transport)->toReceive('send');
            } catch (Throwable $throwable) {
                expect($throwable)->toBeAnInstanceOf(Exception::class);
            }

        });

   });

});
