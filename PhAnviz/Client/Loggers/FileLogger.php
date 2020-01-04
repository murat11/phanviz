<?php

namespace PhAnviz\Client\Loggers;

use PhAnviz\Client\LoggerInterface;

class FileLogger implements LoggerInterface
{
    /**
     * @var resource
     */
    private $fp;

    public function __construct(string $filename)
    {
        $this->fp = fopen($filename, 'a+');
    }

    /**
     * @param string $entry
     */
    public function log(string $entry): void
    {
        fwrite($this->fp, $entry);
    }
}
