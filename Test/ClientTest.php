<?php declare(strict_types=1);

use PhAnviz\Client;
use PhAnviz\Client\ResponseParser;
use PhAnviz\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testRequestOk()
    {
        $binResponse = hex2bin(
            'a500000001bc000012000025000029000002000000004dce00000071f6a500000001df00000e00000000122593831902020000009300'
        );
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('req')->willReturn($binResponse);
        $responseParser = $this->createMock(ResponseParser::class);
        $responseParser->expects($this->once())->method('parse')->with($binResponse)->willReturn(['aaa', 'bbb']);

        $client = new Client('1', $transport, $responseParser);
        $response = $client->request(0x12, sprintf('%02x%02x', 0, 12));
        $this->assertEquals(['aaa', 'bbb'], $response);
    }
}
