<?php declare(strict_types=1);

namespace PhAnviz\Client;

interface TransportInterface
{
    /**
     * @param string $request
     *
     * @return string
     */
    public function req(string $request): string;
}
