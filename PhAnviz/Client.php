<?php declare(strict_types=1);

namespace PhAnviz;

use Generator;
use PhAnviz\Transport\SocketTransport;
use PhAnviz\Transport\TransportInterface;

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
     * @param string $deviceId
     * @param string $address
     * @param int $port
     *
     * @return Client
     */
    public static function createInstance(string $deviceId, string $address, int $port): self
    {
        return new static($deviceId, new SocketTransport($address, $port));
    }

    /**
     * Client constructor.
     *
     * @param string $deviceId
     * @param TransportInterface $transport
     */
    public function __construct(string $deviceId, TransportInterface $transport)
    {
        $this->deviceId = $deviceId;
        $this->transport = $transport;
    }

    /**
     * @param int    $command
     * @param string $data
     *
     * @return \Generator
     */
    public function request(int $command, string $data = null)
    {
        $req = $this->createRequestString($command, $data);
        $response = $this->transport->req($req);
        foreach (explode(PHP_EOL, $response) as $responseItem) {
            yield self::parseResponse($responseItem);
        }
    }

    /**
     * @param array $commands
     *
     * @return Generator
     */
    public function requestMulti(array $commands)
    {
        foreach ($commands as list($command, $data)) {
            yield $this->request($command, $data);
        }
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

    /**
     * Convert response from:
     *
     * STX      CH(device code)     ACK(response)               RET(return)     LEN(data length)    DATA            CRC16
     * 0xA5     4 Bytes             1 Byte(command + 0x80)      1 Byte          2 Bytes             0-400 Bytes     2 Bytes
     *
     * to array
     *
     * @param string $response
     *
     * @return array
     */
    private static function parseResponse($response)
    {
        $response = implode(unpack("H*", $response));

        $resArr = str_split($response, 2);

        $result = [
            'stx' => implode(array_slice($resArr, 0, 1)),
            'ch'  => implode(array_slice($resArr, 1, 4)),
            'ack' => implode(array_slice($resArr, 5, 1)),
            'ret' => implode(array_slice($resArr, 6, 1)),
            'len' => implode(array_slice($resArr, 7, 2)),
            'crc' => implode(array_slice($resArr, -2, 2)),
        ];
        $result['data'] = array_slice($resArr, 9, $result['len']);

        return $result;
    }
}
