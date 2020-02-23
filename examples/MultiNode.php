<?php
require __DIR__ . '/../vendor/autoload.php';

use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Message;

use hisorange\PosixRPC\Transport\InMemory;
use hisorange\PosixRPC\Transport\PosixQueue;

$create_in_memory = function () {
    return new InMemory();
};

$create_posix = function () {
    return new PosixQueue(42);
};

$transport_creators = [
    //$create_in_memory,
    $create_posix,
];

$nodes = [
    'node-parent',
    'node-child1',
    'node-child2',
];

$is_child = false;


foreach ($transport_creators as $transport_creator) {
    foreach ($nodes as $node_key => $node_id) {
        if ($node_key !== 0 && !$is_child) {
            echo "Running fork [$node_key] &&&&&&&&&&&&&\n\n\n";

            $pid = pcntl_fork();

            if ($pid == 0) {
                $is_child = true;
                echo "[$node_id] Forked [$pid] " . ($is_child ? 'CHILD' : 'PARENT') . " \n";
            }
        }

        $is_master = !$is_child;

        $transport = $transport_creator();

        echo "[$node_id] # Transport [" . get_class($transport) . "] -----";
        echo str_repeat('-', 50) . PHP_EOL;


        $node = new Node($node_id, $transport);

        if ($is_master) {
            // Parent waits the connected event!
            $node->subscribe('connected', function (Message $event) {
                $payload = $event->getPayload();
                $channel = $event->getChannel();

                echo "[$node_id] Child connected with message: $payload\n";
            });

            // Parent listens to the received event!
            $node->subscribe('received', function (Message $event) use ($node_id) {
                $payload = $event->getPayload();
                $channel = $event->getChannel();

                echo "[$node_id] Child connected with message: $payload\n";
            });
        } else {
            // Child emits the connected event.
            $node->publish('connected', "Child connected on [$node_id]");

            $node->respond('sum', function (Message $req) use ($node_id) {
                $req->getNode()->publish('received', "[$node_id] received message: " . $req->getId());

                return "[$node_id] Response is = " . ($req->getPayload() + 100);
            });

            // Do not create sub processes on childs!
            break;
        }
    }


    $start = time();
    $end = $start + 3;
    $count = 1;
    $responses = 0;

    while (time() < $end) {
        // echo "[$node_id] --> Loop \n";

        // Parent always requests a new sum
        if ($is_master) {
            $node->request('sum', $count++, function (Message $res) use ($responses, $node_id) {
                echo "[$node_id] Got respond: " . $res->getPayload() . PHP_EOL;

                $responses++;
            });


            if ($responses > 5) {
                break;
            }
        }

        $node->tick();

        usleep(100);
    }

    $node->tick();

    echo str_repeat("\n", 5);
}
