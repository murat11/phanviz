<?php

use PhAnviz\Client;
use PhAnviz\PhAnviz;
use PHPUnit\Framework\TestCase;

class PhAnvizTest extends TestCase
{

    public function testDownloadNewTimeAttendanceRecords()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(3))->method('request')
            ->withConsecutive(
                [0x3C, null],
                [0x40, sprintf("%02x%02x", 0x02, 1)],
                [0x4E, sprintf("%02x%06x", 0x01, 0x00)]
            )->willReturnOnConsecutiveCalls(
                [
                    self::parseStr('a500000001bc00001200002700002b000002000000004e8f00000153ef'),
                    self::parseStr('a500000001df00000e0000000026259f5b2a0102000000d509'),
                    self::parseStr('a500000001df00000e0000000026259f5b2a0102000000d509'),
                    self::parseStr('a500000001df00000e0000000026259f5b2a0102000000d509'),
                    self::parseStr('a500000001df00000e0000000023259f5b5f0102000000e844')
                ],
                [
                    self::parseStr('a500000001c000000f010000000026259f5b2a0102000000af90'),
                    self::parseStr('a500000001df00000e0000000025259f5ba101020000003b55'),
                    self::parseStr('a500000001df00000e0000000026259f5b2a0102000000d509'),
                    self::parseStr('a500000001df00000e0000000019259f5bc70202000000ee70'),
                    self::parseStr('a500000001df00000e000000001a259f5bce0102000000383c'),
                    self::parseStr('a500000001df00000e0000000020259f5bd601020000005010'),
                ],
                [
                    self::parseStr('a500000001ce0000030000060c9c'),
                    self::parseStr('a500000001df00000e0000000026259f5b2a0102000000d509'),
                    self::parseStr('a500000001df00000e0000000012259f5c2302020000000938'),
                ]
            );

        $anviz = new PhAnviz($client, new DateTimeZone('America/New_York'));
        $data = $anviz->downloadNewTimeAttendanceRecords(true);
        $this->assertCount(7, $data);
    }

    private static function parseStr(string $str): array
    {
        $resArr = str_split($str, 2);
        $result = [
            'stx' => implode(array_slice($resArr, 0, 1)),
            'ch' => hexdec(implode(array_slice($resArr, 1, 4))),
            'ack' => hexdec(implode(array_slice($resArr, 5, 1))),
            'ret' => hexdec(implode(array_slice($resArr, 6, 1))),
            'len' => hexdec(implode(array_slice($resArr, 7, 2))),
            'crc' => implode(array_slice($resArr, -2, 2)),
        ];
        $result['data'] = array_slice($resArr, 9, $result['len']);

        return $result;
    }
}
