<?php
require __DIR__ . '/../vendor/autoload.php';

use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Logger;
use hisorange\PosixRPC\Message;

use hisorange\PosixRPC\Transport\PosixQueue as T;

$started_at = microtime(true);
$pongs = 0;

function finished($last)
{
    global $started_at;
    global $is_master;

    $elapsed = round(microtime(true) - $started_at, 2);

    echo "[".($is_master ? 'MASTER' : 'CHILD')."] Process finished in [$elapsed] second last value [$last]\n\n";
    exit(0);
}

$is_master = true;
$childs = 0;

while ($is_master && $childs < 5) {
    $childs++;
    $pid = pcntl_fork();

    if (!!$pid) {
        $is_master = false;
    }
}

if (!$pid) {
    $pid = pcntl_fork();
}

$target_number = 1000;

$is_master = !$pid;
$is_child = !!$pid;

$node_name = $is_master ? 'master' : 'child';

$logger = new Logger(sprintf("%01s", $childs) .  "-log-" . ($is_master ? 'maste' : 'child'));
$logger->pushHandler(new \Monolog\Handler\StreamHandler("php://stdout", \Monolog\Logger::DEBUG));


$node = new Node($is_master ? 'node-master' : 'node-child', new T(405));


if ($is_master) {
    $node->subscribe('connect', function (Message $event) use ($logger) {
        $logger->info('Got connected event: ' . $event->getPayload());
    });
    $logger->debug('Listening the [connect] event');


    $handler = function (Message $res) use ($target_number, $logger) {
        global $pongs;

        $responded = $res->getPayload();

        $logger->info('Pong: ' . $responded);

        if ($responded >= $target_number) {
            finished($responded);
        }

        $pongs = $responded + 1;

        $res->getNode()->request('ping.pong', $responded + 1, $res->getSubscription()->getHandler());
    };


    $node->request('ping.pong', 1, $handler);
} else {
    $node->publish('connect', 'MyPID: ' . $pid);
    $logger->debug('Emited the [connect] event');


    $node->respond('ping.pong', function (Message $req) use ($logger,  $target_number) {
        global $pongs;

        $responded = $req->getPayload();
        $logger->info('Ping: ' . $responded);

        if ($responded > $target_number) {
            finished($responded);
        }

        $pongs = $responded + 1;

        return $responded + 1;
    });
}


do {
    $node->tick();

    if ($is_master && $pongs == $target_number) {
        finished($pongs);
    }

    if (microtime(true) - $started_at > 2) {
        finished($pongs);
    }

    usleep(100);
} while (true);
