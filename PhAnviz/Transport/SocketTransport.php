<?php declare(strict_types=1);

namespace PhAnviz\Transport;

use RuntimeException;

class SocketTransport implements TransportInterface
{
    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * Client constructor.
     *
     * @param string $address
     * @param int    $port
     */
    public function __construct(string $address, int $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    /**
     * @param string $request
     *
     * @return string
     */
    public function req(string $request): string
    {
        $this->connect();
        fwrite($this->socket, $request);

        $responses = [];
        while ($str = fgets($this->socket, 1024)) {
            $responses[] = $str;
        }
        fclose($this->socket);

        return implode(PHP_EOL, $responses);
    }


    private function connect()
    {
        $this->socket = fsockopen(
            $this->address,
            $this->port,
            $errno,
            $errorMessage
        );

        if (!$this->socket) {
            throw new RuntimeException(
                sprintf('Can not connect to %s:%d', $this->address, $this->port),
                $errno,
                $errorMessage
            );
        }
    }
}
