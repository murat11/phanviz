<?php


use PhAnviz\Client\ResponseParser;
use PHPUnit\Framework\TestCase;

class ResponseParserTest extends TestCase
{
    public function testParseResponse()
    {
        $responseParser = new ResponseParser();
        $result = $responseParser->parse(
            hex2bin('a500000001bc00001200002800002c000002000000000022000003adbfa500000001df00000e000000001a25a0ddc701030000001a87')
        );
        $this->assertCount(2, $result);
        $this->assertEquals(
            [
                [
                    'stx' => 'a5',
                    'ch' => 0x00000001,
                    'ack' => 0xBC,
                    'ret' => 0x00,
                    'len' => 0x0012,
                    'crc' => 'adbf',
                    'data' => str_split('00002800002c000002000000000022000003', 2),
                ],
                [
                    'stx' => 'a5',
                    'ch' => 0x00000001,
                    'ack' => 0xDF,
                    'ret' => 0x00,
                    'len' => 0x000E,
                    'crc' => '1a87',
                    'data' => str_split('000000001a25a0ddc70103000000', 2),
                ]
            ],
            $result
        );
    }
}
