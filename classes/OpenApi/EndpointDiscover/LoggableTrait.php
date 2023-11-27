<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use eZCLI;

trait LoggableTrait
{
    protected $cli = null;

    protected function log($message, $level = null)
    {
        if ($this->cli instanceof eZCLI) {
            switch ($level) {
                case 'error';
                    $this->cli->error($message);
                    break;

                case 'warning';
                    $this->cli->warning($message);
                    break;

                case 'notice';
                    $this->cli->notice($message);
                    break;

                default;
                    $this->cli->output($message);
            }

        }
    }
}