<?php

namespace App\Exceptions;

class NotACvException extends \Exception
{
    /**
     * The friendly reason shown to the seeker (e.g. "This looks like a job advert, not a CV.")
     */
    public string $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
        parent::__construct($reason);
    }
}