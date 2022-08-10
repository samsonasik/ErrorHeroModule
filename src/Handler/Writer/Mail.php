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
use Laminas\Mime\Part;

use function fopen;
use function implode;
use function is_array;
use function key;
use function sprintf;
use function trigger_error;

use const E_USER_WARNING;
use const PHP_EOL;

final class Mail extends BaseMail
{
    /** @var string */
    private const NAME = 'name';

    /**
     * @throws LogException\InvalidArgumentException
     */
    public function __construct(
        MailMessage $mailMessage,
        TransportInterface $transport,
        private readonly array $filesData
    ) {
        parent::__construct($mailMessage, $transport);
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
            $mimePart           = new Part($body);
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
        } catch (Exception $exception) {
            /** @var string $message */
            $message = $exception->getMessage();
            /** @var int $code */
            $code = $exception->getCode();

            trigger_error(
                "unable to send log entries via email; "
                . sprintf('message = %s; ', $message)
                . sprintf('code = %d; ', $code)
                . "exception class = " . $exception::class,
                E_USER_WARNING
            );
        }
    }

    private function bodyAddPart(MimeMessage $mimeMessage, array $data): MimeMessage
    {
        foreach ($data as $singleData) {
            if (key($singleData) === self::NAME && ! is_array($singleData[self::NAME])) {
                $mimeMessage = $this->singleBodyAddPart($mimeMessage, $singleData);
                continue;
            }

            $mimeMessage = $this->bodyAddPart($mimeMessage, $singleData);
        }

        return $mimeMessage;
    }

    private function singleBodyAddPart(MimeMessage $mimeMessage, array $data): MimeMessage
    {
        $mimePart              = new Part(fopen($data['tmp_name'], 'r'));
        $mimePart->type        = $data['type'];
        $mimePart->filename    = $data[self::NAME];
        $mimePart->disposition = Mime::DISPOSITION_ATTACHMENT;
        $mimePart->encoding    = Mime::ENCODING_BASE64;

        return $mimeMessage->addPart($mimePart);
    }
}
