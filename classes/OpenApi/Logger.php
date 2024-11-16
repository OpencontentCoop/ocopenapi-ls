<?php

namespace Opencontent\OpenApi;

use eZLog;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger implements LoggerInterface
{
    public static function instance()
    {
        return new static();
    }

    public function log($level, $message, array $context = array()): void
    {
        if (!empty($context)) {
            foreach ($context as $key => $value) {
                $message .= " ($key => " . $this->getStringValue($value) . ")";
            }
        }
        eZLog::write("[$level] $message", 'openapi.log');
    }

    private function getStringValue($value)
    {
        if (is_scalar($value) or method_exists($value, '__toString')) {
            $stringValue = $value;
        }elseif (is_array($value)){
            $stringValue = '';
            foreach ($value as $index => $item){
                if (!is_numeric($index)){
                    $stringValue .= $index . ': ';
                }
                $stringValue .= $this->getStringValue($item) . ' ';
            }
        }

        return $stringValue;
    }
}