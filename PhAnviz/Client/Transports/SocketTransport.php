<?php declare(strict_types=1);

namespace PhAnviz\Client\Transports;

use PhAnviz\Client\TransportInterface;
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
        $response = '';
        stream_set_timeout($this->socket, 3);
        $info = stream_get_meta_data($this->socket);
        while (!feof($this->socket) && !($info['timed_out'] ?? true)) {
            $response .= fgets($this->socket);
            $info = stream_get_meta_data($this->socket);
        }
        fclose($this->socket);

        return $response;
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
