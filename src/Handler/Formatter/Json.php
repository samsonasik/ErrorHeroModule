<?php

namespace ErrorHeroModule\Handler\Formatter;

use DateTime;
use Zend\Log\Formatter\Base;
use Zend\Log\Formatter\FormatterInterface;

class Json extends Base implements FormatterInterface
{
    /**
     * @param array $event event data
     *
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        if (isset($event['timestamp']) && $event['timestamp'] instanceof DateTime) {
            $event['timestamp'] = $event['timestamp']->format($this->getDateTimeFormat());
        }

        return \str_replace(
            '\n',
            PHP_EOL,
            \json_encode($event, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
