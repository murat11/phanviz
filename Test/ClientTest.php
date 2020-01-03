<?php declare(strict_types=1);

use PhAnviz\Client;
use PhAnviz\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testRequestOk()
    {
        $transport = $this->createMock(TransportInterface::class);
        $pieces = [
            hex2bin('a500000001bc000012000025000029000002000000004dce00000071f6'),
            hex2bin('a500000001df00000e00000000122593831902020000009300')
        ];
        $transport->method('req')->willReturn(implode(PHP_EOL, $pieces));
        $client = new Client('1', $transport);
        $response = $client->request(0x12, sprintf('%02x%02x', 0, 12));
        $this->assertIsIterable($response);

        foreach ($response as $responseItem) {
            $this->assertIsArray($responseItem);
        }
    }

    public function testClientMulti()
    {
        $client = $this->createPartialMock(Client::class, ['request']);
        $client->expects($this->exactly(2))->method('request')->withConsecutive([0x01, '0x0101'], [0x02, '0x0202'])->willReturn('aaa');

        $multiResponse = $client->requestMulti([[0x01, '0x0101'], [0x02, '0x0202']]);
        foreach ($multiResponse as $responseItem) {
            $this->assertEquals('aaa', $responseItem);
        }
    }
}
