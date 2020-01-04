<?php

namespace PhAnviz\Client;

interface LoggerInterface
{
    /**
     * @param string $entry
     */
    public function log(string $entry): void;
}
