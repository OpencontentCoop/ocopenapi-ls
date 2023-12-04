<?php

use Opencontent\OpenApi\Exceptions\TooManyRequestsException;
use Opencontent\OpenApi\Loader;

class OpenApiRateLimit
{
    const COUNT_FIELD = 'openapi_request_count';

    const RESET_TIMESTAMP_FIELD = 'openapi_reset_timestamp';

    private static $instance;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var bool
     */
    private $enableDocumentation = true;

    /**
     * @var int
     */
    private $loggedInRateLimitPerInterval = 10000;

    /**
     * @var int
     */
    private $anonymousRateLimitPerInterval = 1000;

    /**
     * @var int
     */
    private $rateLimitPerInterval;

    /**
     * @var int
     */
    private $interval = 3600;

    /**
     * @var int
     */
    private $requestCount;

    /**
     * @var int
     */
    private $lastReset;

    /**
     * @var int
     */
    private $relativeNextReset;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();

            try {
                self::$instance->setEnabled(
                    Loader::instance()->getSettingsProvider()->provideSettings()->rateLimitEnabled
                );
                self::$instance->setEnableDocumentation(
                    Loader::instance()->getSettingsProvider()->provideSettings()->rateLimitDocumentationEnabled
                );
            }catch (Throwable $e){}
        }

        return self::$instance;
    }

    /**
     * @param int $rateLimitPerInterval
     */
    public function setRateLimitPerInterval($rateLimitPerInterval)
    {
        $this->rateLimitPerInterval = (int)$rateLimitPerInterval;
    }

    /**
     * @return int
     */
    public function getRateLimitPerInterval()
    {
        if ($this->rateLimitPerInterval === null){
            $this->rateLimitPerInterval = \eZUser::isCurrentUserRegistered() ? $this->loggedInRateLimitPerInterval : $this->anonymousRateLimitPerInterval;
        }

        return $this->rateLimitPerInterval;
    }

    /**
     * @param int $interval
     */
    public function setInterval($interval)
    {
        $this->interval = (int)$interval;
    }

    /**
     * @throws TooManyRequestsException
     */
    public function checkAndUpdateRequestCount()
    {
        if ($this->isEnabled()) {
            $this->requestCount = (int)$this->getValue(self::COUNT_FIELD);
            if ($this->requestCount === 0) {
                $this->reset();
            }
            $this->lastReset = $this->getValue(self::RESET_TIMESTAMP_FIELD);
            $nextReset = $this->lastReset + $this->interval;
            $this->relativeNextReset = $nextReset - time();
            if ($this->relativeNextReset <= 0) {
                $this->reset();
            }
            if ($this->requestCount >= $this->getRateLimitPerInterval()) {
                throw new TooManyRequestsException($this->relativeNextReset);
            }
            $this->requestCount++;
            $this->setValue(self::COUNT_FIELD, $this->requestCount);
        }
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isEnableDocumentation()
    {
        return $this->enableDocumentation;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
    }

    /**
     * @param bool $enableDocumentation
     */
    public function setEnableDocumentation($enableDocumentation)
    {
        $this->enableDocumentation = $enableDocumentation;
    }

    private function getValue($field)
    {
        return eZPreferences::value($field);
    }

    private function reset()
    {
        $this->requestCount = 0;
        $this->lastReset = time();
        $this->setValue(self::RESET_TIMESTAMP_FIELD, $this->lastReset);
        $this->relativeNextReset = $this->interval;
    }

    private function setValue($field, $value)
    {
        eZPreferences::setValue($field, $value);
    }

    public function setHeaders()
    {
        if ($this->isEnabled()) {
            $remaining = $this->getRateLimitPerInterval() - $this->requestCount;
            if ($remaining < 0){
                $remaining = 0;
            }
            header("X-RateLimit-Limit: " . $this->getRateLimitPerInterval());
            header("X-RateLimit-Remaining: $remaining");
            header("X-RateLimit-Reset: $this->relativeNextReset");
        }
    }

    /**
     * @return int
     */
    public function getRelativeNextReset()
    {
        return $this->relativeNextReset;
    }
}