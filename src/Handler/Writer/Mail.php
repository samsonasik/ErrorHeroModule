<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler\Writer;

use Exception;
use Laminas\Log\Exception as LogException;
use Laminas\Log\Writer\Mail as BaseMail;
use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Message as MailMessage;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;

use function fopen;
use function implode;
use function is_array;
use function key;
use function trigger_error;

use const E_USER_WARNING;
use const PHP_EOL;

class Mail extends BaseMail
{
    /**
     * @throws LogException\InvalidArgumentException
     */
    public function __construct(
        MailMessage $mail,
        TransportInterface $transport,
        private array $filesData
    ) {
        parent::__construct($mail, $transport);
    }

    /**
     * {inheritDoc}
     *
     * Override with apply attachment whenever there is $_FILES data
     */
    public function shutdown(): void
    {
        // Always provide events to mail as plaintext.
        $body = implode(PHP_EOL, $this->eventsToMail);

        if (empty($this->filesData)) {
            $this->mail->setBody($body);
        } else {
            $mimePart           = new MimePart($body);
            $mimePart->type     = Mime::TYPE_TEXT;
            $mimePart->charset  = 'utf-8';
            $mimePart->encoding = Mime::ENCODING_8BIT;

            $body = new MimeMessage();
            $body->addPart($mimePart);

            $body = $this->bodyAddPart($body, $this->filesData);
            $this->mail->setBody($body);

            $headers = $this->mail->getHeaders();
            /** @var ContentType $contentTypeHeader */
            $contentTypeHeader = $headers->get('Content-Type');
            $contentTypeHeader->setType('multipart/alternative');
        }

        // Finally, send the mail.  If an exception occurs, convert it into a
        // warning-level message so we can avoid an exception thrown without a
        // stack frame.
        try {
            $this->transport->send($this->mail);
        } catch (Exception $e) {
            trigger_error(
                "unable to send log entries via email; "
                . "message = {$e->getMessage()}; "
                . "code = {$e->getCode()}; "
                . "exception class = " . $e::class,
                E_USER_WARNING
            );
        }
    }

    private function singleBodyAddPart(MimeMessage $body, array $data): MimeMessage
    {
        $mimePart              = new MimePart(fopen($data['tmp_name'], 'r'));
        $mimePart->type        = $data['type'];
        $mimePart->filename    = $data['name'];
        $mimePart->disposition = Mime::DISPOSITION_ATTACHMENT;
        $mimePart->encoding    = Mime::ENCODING_BASE64;

        return $body->addPart($mimePart);
    }

    private function bodyAddPart(MimeMessage $body, array $data): MimeMessage
    {
        foreach ($data as $upload) {
            if (key($upload) === 'name' && ! is_array($upload['name'])) {
                $body = $this->singleBodyAddPart($body, $upload);
                continue;
            }

            $body = $this->bodyAddPart($body, $upload);
        }

        return $body;
    }
}
