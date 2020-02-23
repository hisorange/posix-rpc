<?php
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Message;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

error_reporting(E_ALL & ~E_NOTICE);

chdir(__DIR__);

require '../vendor/autoload.php';

define('BENCH_STARTED_AT', time());
define('BENCH_INTERVAL', 5);
define('BENCH_ENDS_AT', BENCH_STARTED_AT + BENCH_INTERVAL);
define('BENCH_LOOP_SLEEP', 200);

set_time_limit(BENCH_INTERVAL);

$nodes = [
    'node-x1',
    'node-x2',
    'node-x3',
    'node-x4',
    'node-x5',
];

$is_master = true;
$child_idx = null;
$child_name = null;
$pids = [];

foreach ($nodes as $idx => $node) {
    $pid = pcntl_fork();

    $pids[] = $pid;

    // We are the child.
    if (!$pid) {
        $is_master = false;
        $child_idx = $idx;
        $child_name = $node;
        break;
    }
}

if ($is_master) {
    $status = null;
    $alive = 5;


    while ($alive) {
        foreach ($pids as $pkey => $pid) {
            pcntl_waitpid($pid, $status, 0);

            unset($pids[$pkey]);
            $alive--;
        }
    }

    exit('Main process died!' . PHP_EOL);
} else {
    $logger = new Logger($child_name);
    $logger->pushHandler(new StreamHandler("php://stdout", Monolog\Logger::INFO));

    $node = new Node($child_name, [], $logger);

    // Every node other then the first will respond to hash requests.
    if ($child_idx !== 0) {
        $node->respond->hash(function (Message $msg) {
            return 'OK#' . $msg->getPayload();
        });
    }

    $tick = 1;
    $messages = 1;

    while (true) {
        $node->tick();

        if ($child_idx === 0) {
            $result = $node->request->hash($messages);
            $messages = $messages + 1;

            if ($messages % 1 === 0) {
                $logger->warning('Got the response [' . $result . '] total message [' . $messages . '] count');
            }
        }


        if (time() >= BENCH_ENDS_AT) {
            $logger->info("Reached the end time.");
            exit(0);
        }

        usleep(5000);

        $tick++;
    }
}
