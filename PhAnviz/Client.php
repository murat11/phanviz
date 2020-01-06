<?php declare(strict_types=1);

namespace PhAnviz;

use PhAnviz\Client\LoggerInterface;
use PhAnviz\Client\ResponseParser;
use PhAnviz\Client\Transports\SocketTransport;
use PhAnviz\Client\TransportInterface;

class Client
{
    /**
     * @var string
     */
    private $deviceId;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var ResponseParser
     */
    private $responseParser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $deviceId
     * @param string $address
     * @param int $port
     *
     * @return Client
     */
    public static function createInstance(string $deviceId, string $address, int $port): self
    {
        return new static($deviceId, new SocketTransport($address, $port), new ResponseParser());
    }

    /**
     * Client constructor.
     *
     * @param string $deviceId
     * @param TransportInterface $transport
     * @param ResponseParser $responseParser
     */
    public function __construct(string $deviceId, TransportInterface $transport, ResponseParser $responseParser)
    {
        $this->deviceId = $deviceId;
        $this->transport = $transport;
        $this->responseParser = $responseParser;
    }

    /**
     * @param int    $command
     * @param string $data
     *
     * @return array
     */
    public function request(int $command, string $data = null)
    {
        $req = $this->createRequestString($command, $data);
        if ($this->logger) {
            $this->logger->log(
                sprintf(
                    "Command: %02x %s (%s),",
                    $command,
                    $data,
                    implode(unpack("H*", $req))
                )
            );
        }
        $response = $this->transport->req($req);
        $result = $this->responseParser->parse($response);
        if ($this->logger) {
            $this->logger->log(
                sprintf(" response: %s (parsed: %s)\n",
                    implode(unpack("H*", $response)),
                    json_encode($result)
                )
            );
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param int    $command
     * @param string $data
     *
     * @return bool|string
     */
    private function createRequestString(int $command, string $data = null)
    {
        $req = sprintf(
            "a5%08s%02x%04x%s",
            $this->deviceId,
            $command,
            !empty($data) ? strlen($data) / 2 : 0,
            $data
        );
        $req .= CRC16::crc16(hex2bin($req));
        $req = hex2bin($req);

        return $req;
    }
}
