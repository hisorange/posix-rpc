<?php

use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Node;

describe('Node', function () {
    beforeEach(function () {
        $this->node = new Node('test');
    });

    it('expect to respond to an rpc', function () {
        $node1 = new Node('node1');
        $node2 = new Node('node2');

        $node2->respond('sum', function (IMessage $request) {
            return array_sum($request->getPayload());
        });

        $node1->request('sum', [10, 2], function (IMessage $response) {
            expect($response->getPayload())->toBe(12);
        });

        for ($i=0; $i < 10; $i++) {
            $node1->tick();
            $node2->tick();
        }
    });
});
