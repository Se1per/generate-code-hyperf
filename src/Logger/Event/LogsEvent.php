<?php

namespace Japool\Genconsole\Event;

class LogsEvent
{
    public $logger;
    public $status;
    public $title;

    public $requestLog;
    
    //$this->eventDispatcher->dispatch(new LogsEvent('business','error','name',['hahaha']));

    public function __construct($logger,$status,$title,$requestLog)
    {
        $this->logger = $logger;
        $this->status = $status;
        $this->title = $title;
        $this->requestLog = $requestLog;   
    }
}