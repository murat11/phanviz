<?php

namespace PhAnviz\Client;

class ResponseParser
{
    /**
     * @param string $input
     *
     * @return array
     */
    public function parse(string $input): array
    {
        $hexString = implode(unpack("H*", $input));
        $hexArr = str_split($hexString, 2);

        $result = [];
        while (!empty($hexArr)) {
            $element = [
                'stx' => implode(array_splice($hexArr, 0, 1)),
                'ch'  => hexdec(implode(array_splice($hexArr, 0, 4))),
                'ack' => hexdec(implode(array_splice($hexArr, 0, 1))),
                'ret' => hexdec(implode(array_splice($hexArr, 0, 1))),
                'len' => hexdec(implode(array_splice($hexArr, 0, 2))),
            ];
            $element['data'] = array_splice($hexArr, 0, $element['len']);
            $element['crc'] = implode(array_splice($hexArr, 0, 2));
            $result[] = $element;
        }

        return $result;
    }
}
