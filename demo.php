<?php declare(strict_types=1);

use PhAnviz\Client;
use PhAnviz\Client\Loggers\FileLogger;
use PhAnviz\PhAnviz;

require 'vendor/autoload.php';

$options = getopt('i:h:p:');
$logger = new FileLogger('php://output');

$deviceId = $options['i'] ?? '';
$host = $options['h'] ?? '';
$port = (int) ($options['p'] ?? 0);

$client = Client::createInstance($deviceId, $host, $port);
$client->setLogger($logger);

$logger->log(sprintf("%s - getting new records\n", date('r')));
$anviz = new PhAnviz($client, new DateTimeZone('America/New_York'));
$anviz->setLogger($logger);

$response = $anviz->downloadNewTimeAttendanceRecords(false);

print_r($response);

$logger->log(sprintf("%d records downloaded\n\n", count($response)));
