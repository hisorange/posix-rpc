<?php
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Logger;
use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Transport\PosixQueue as Transport;

use Monolog\Handler\StreamHandler;

chdir(__DIR__);

require '../vendor/autoload.php';

define('BENCH_STARTED_AT', time());
define('BENCH_INTERVAL', 5);
define('BENCH_ENDS_AT', BENCH_STARTED_AT + BENCH_INTERVAL);
define('BENCH_LOOP_SLEEP', 200);

$ipc_key = rand();

$nodes = [
    'node.x1',
    'node.x2',
    'node.x3',
    'node.x4',
    'node.x5',
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
            pcntl_waitpid($pid, $status);

            unset($pids[$pkey]);
            $alive--;
        }
    }

    exit('Main process died!' . PHP_EOL);
} else {
    $logger = new Logger($child_name);
    $logger->pushHandler(new StreamHandler("php://stdout", Monolog\Logger::INFO));

    $node = new Node($child_name, new Transport($ipc_key, null, true), $logger);

    // Every node other then the first will respond to hash requests.
    if ($child_idx !== 0) {
        $node->respond->hash(function (Message $msg) {
            return md5($msg->getPayload());
        });
    }

    $tick = 1;
    $messages = 0;

    while ($messages < 100) {
        $node->tick();

        if ($child_idx === 0) {
            $result = $node->request->hash($messages++);

            $logger->info("Hash [$messages] results [$result]");
        }


        if (time() >= BENCH_ENDS_AT) {
            $logger->info("Reached the end time.");
            exit(0);
        }


        $tick++;
    }

    exit(0);
}
