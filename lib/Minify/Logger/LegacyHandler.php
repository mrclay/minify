<?php

namespace Minify\Logger;

use Monolog\Handler\AbstractProcessingHandler;

class LegacyHandler extends AbstractProcessingHandler
{
    private $obj;

    public function __construct($obj)
    {
        if (!is_callable(array($obj, 'log'))) {
            throw new \InvalidArgumentException('$obj must have a public log() method');
        }
        $this->obj = $obj;
        parent::__construct();
    }

    protected function write(array $record)
    {
        $this->obj->log((string)$record['formatted']);
    }
}
