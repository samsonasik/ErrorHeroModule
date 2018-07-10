<?php

declare(strict_types=1);

namespace ErrorHeroModule\Handler\Writer;

use Exception;
use Zend\Log\Exception as LogException;
use Zend\Log\Writer\Mail as BaseMail;
use Zend\Mail\Message as MailMessage;
use Zend\Mail\Transport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;

class Mail extends BaseMail
{
    /**
     * @var array
     */
    private $requestData;

    /**
     * @throws LogException\InvalidArgumentException
     */
    public function __construct(
        MailMessage                  $mail,
        Transport\TransportInterface $transport,
        array                        $requestData
    ) {
        parent::__construct($mail, $transport);

        $this->requestData = $requestData;
    }

    /**
     * {inheritDoc}
     *
     * Override with apply attachment whenever there is $_FILES data
     */
    public function shutdown() : void
    {
        // Always provide events to mail as plaintext.
        $body = \implode(\PHP_EOL, $this->eventsToMail);

        if (empty($this->requestData['files_data'])) {
            $this->mail->setBody($body);
        } else {
            $mimePart = new MimePart($body);
            $mimePart->type     = Mime::TYPE_TEXT;
            $mimePart->charset  = 'utf-8';
            $mimePart->encoding = Mime::ENCODING_8BIT;

            $body = new MimeMessage();
            $body->addPart($mimePart);

            foreach ($this->requestData['files_data'] as $key => $row) {
                if (\key($row) === 'name') {
                    // single upload
                    $body = $this->singleBodyAddPart($body, $row);
                    continue;
                }

                // collection upload
                $body = $this->collectionBodyAddPart($body, $row);
            }

            $this->mail->setBody($body);

            $headers = $this->mail->getHeaders();
            /** @var \Zend\Mail\Header\ContentType $contentTypeHeader */
            $contentTypeHeader = $headers->get('Content-Type');
            $contentTypeHeader->setType('multipart/alternative');
        }

        // Finally, send the mail.  If an exception occurs, convert it into a
        // warning-level message so we can avoid an exception thrown without a
        // stack frame.
        try {
            $this->transport->send($this->mail);
        } catch (Exception $e) {
            \trigger_error(
                "unable to send log entries via email; " .
                "message = {$e->getMessage()}; " .
                "code = {$e->getCode()}; " .
                "exception class = " . \get_class($e),
                \E_USER_WARNING
            );
        }
    }

    private function singleBodyAddPart(MimeMessage $body, array $data) : MimeMessage
    {
        $mimePart              = new MimePart(\fopen($data['tmp_name'], 'r'));
        $mimePart->type        = $data['type'];
        $mimePart->filename    = $data['name'];
        $mimePart->disposition = Mime::DISPOSITION_ATTACHMENT;
        $mimePart->encoding    = Mime::ENCODING_BASE64;

        return $body->addPart($mimePart);
    }

    private function collectionBodyAddPart(MimeMessage $body, array $data) : MimeMessage
    {
        foreach ($data as $multiple => $upload) {
            if (\key($upload) === 'name') {
                $body = $this->singleBodyAddPart($body, $upload);
                continue;
            }

            $body = $this->collectionBodyAddPart($body, $upload);
        }

        return $body;
    }
}
