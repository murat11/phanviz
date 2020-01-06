<?php

namespace PhAnviz;

use DateTime;
use DateTimeZone;
use PhAnviz\Client\LoggerInterface;

class PhAnviz
{
    /**
     * Restart; retrieve new records (The first data packet must send this data when retrieving the new records)
     */
    const DOWNLOAD_NEW = 0x02;

    /**
     * Operation successful
     */
    private const ACK_SUCCESS = 0x00;

    /**
     * For Anviz devices zero second means 2000/Jan/02 00:00:00 GMT+0
     */
    private const ANVIZ_EPOCH = 946771200;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $timestampOffset = self::ANVIZ_EPOCH;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Client $client
     * @param DateTimeZone $deviceTimeZone
     */
    public function __construct(Client $client, DateTimeZone $deviceTimeZone = null)
    {
        $this->client = $client;
        if (!empty($deviceTimeZone)) {
            $this->timestampOffset -= $deviceTimeZone->getOffset(
                new DateTime('now', new DateTimeZone('UTC'))
            );
        }
    }

    /**
     * @param int $command
     * @param string|null $data
     * @param array $responseHandlers
     *
     * @return array
     */
    public function runCommand(int $command, ?string $data, array $responseHandlers): array
    {
        $response = $this->client->request($command, $data);
        $result = [];
        foreach ($response as $responseItem) {
            $responseHandler = $responseHandlers[($responseItem['ack'] ?? null)] ?? null;
            if (!$responseHandler) {
                continue;
            }
            $responseHandler($responseItem, $result);
        }

        return $result;
    }

    /**
     * @param bool $clear
     *
     * @return array
     */
    public function downloadNewTimeAttendanceRecords(bool $clear = false): array
    {
        $timeAttendanceRecordsHandler = function ($res, &$result) {
            if (self::ACK_SUCCESS !== $res['ret']) {
                return;
            }
            $taRecords = [];
            $len = 1;
            $lenByteOffset = 0;
            if (0xC0 === $res['ack']) {
                $len = hexdec($res['data'][0]);
                $lenByteOffset = 1;
            }
            for ($i = 0; $i < $len; $i++) {
                $itemOffset = $i * 14 + $lenByteOffset;
                $record = [
                    'user_code' => hexdec(implode(array_slice($res['data'], $itemOffset, 5))),
                    'timestamp' => hexdec(
                            implode(array_slice($res['data'], $itemOffset + 5, 4))
                        ) + $this->timestampOffset,
                    'backup_code' => hexdec($res['data'][$itemOffset + 9]),
                    'record_type' => hexdec($res['data'][$itemOffset + 10] & 0xF),
                    'work_type' => hexdec(implode(array_slice($res['data'], $itemOffset + 11, 2))),
                ];
                $taRecords[md5(json_encode($record))] = $record;
            }

            if ($this->logger) {
                $this->logger->log(sprintf("T&A Records (count %d): %s\n", count($taRecords), json_encode($taRecords)));
            }
            if (!isset($result['ta_records'])) {
                $result['ta_records'] = [];
            }
            $result['ta_records'] += $taRecords;
        };

        $handlers = [
            0xBC => function($res, &$result) {
                if (self::ACK_SUCCESS !== $res['ret']) {
                    return;
                }
                $recordInformation = [
                    'user_amount' => hexdec(implode(array_slice($res['data'], 0, 3))),
                    'fp_amount' => hexdec(implode(array_slice($res['data'], 3, 3))),
                    'password_amount' => hexdec(implode(array_slice($res['data'], 6, 3))),
                    'card_amount' => hexdec(implode(array_slice($res['data'], 9, 3))),
                    'all_record_amount' => hexdec(implode(array_slice($res['data'], 12, 3))),
                    'new_record_amount' => hexdec(implode(array_slice($res['data'], 15, 3))),
                ];
                if ($this->logger) {
                    $this->logger->log(sprintf("Record information: %s\n", json_encode($recordInformation)));
                }
                $result['record_information'] = $recordInformation;
            },
            0xDF => $timeAttendanceRecordsHandler,
            0xC0 => $timeAttendanceRecordsHandler,
            0xCE => function($res, &$result) {
                if (self::ACK_SUCCESS !== $res['ret']) {
                    return;
                }
                $result['cleared_records'] = hexdec(implode($res['data']));
            }
        ];
        $result = $this->runCommand(0x3C, null, $handlers);
        $records = $result['ta_records'] ?? [];

        $newRecordsAmount = $result['record_information']['new_record_amount'] ?? 0x00;
        if ($newRecordsAmount > 0) {
            $num = min(25, $newRecordsAmount);
            $commands = [
                [0x40, sprintf("%02x%02x", self::DOWNLOAD_NEW, $num)],
            ];
            $remainRecordsAmount = $newRecordsAmount - $num;
            while ($remainRecordsAmount > 0) {
                $num = min(25, $remainRecordsAmount);
                $commands[] = [0x40, sprintf("%02x%02x", 0, $num)];
                $remainRecordsAmount -= $num;
            }

            foreach ($commands as list($command, $data)) {
                $records += $this->runCommand($command, $data, $handlers)['ta_records'] ?? [];
            }
        }


        if ($clear && $newRecordsAmount > 0) {
            $records += $this->runCommand(0x4E, sprintf("%02x%06x", 0x01, 0x00), $handlers)['ta_records'] ?? [];
        }

        return $records;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
