<?php

namespace PhAnviz\Transport;

interface TransportInterface
{
    /**
     * @param string $request
     *
     * @return string
     */
    public function req(string $request): string;
}
