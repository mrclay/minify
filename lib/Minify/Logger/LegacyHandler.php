<?php

namespace Minify\Logger;

use Monolog\Handler\AbstractProcessingHandler;

class LegacyHandler extends AbstractProcessingHandler
{
    /**
     * @var object
     */
    private $obj;

    /**
     * @param object $obj
     */
    public function __construct($obj)
    {
        if (!\is_callable(array($obj, 'log'))) {
            throw new \InvalidArgumentException('$obj must have a public log() method');
        }

        $this->obj = $obj;

        parent::__construct();
    }

    /**
     * @param array $record
     *
     * @return void
     */
    protected function write(array $record): void
    {
        $this->obj->log((string) $record['formatted']);
    }
}
