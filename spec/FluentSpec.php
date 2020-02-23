<?php
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Fluent;

describe('Fluent', function () {
    beforeEach(function () {
        $node = new Node('test');
        $handler = function () {
        };

        allow($node)->toReceive('request')->andRun(function (...$params) {
            return [
                'branch' => 'request',
                'params' => $params,
            ];
        });

        allow($node)->toReceive('respond')->andRun(function (...$params) {
            return [
                'branch' => 'respond',
                'params' => $params,
            ];
        });

        $this->handler = $handler;
        $this->node = $node;
        $this->request = new Fluent($node, 'request');
        $this->respond = new Fluent($node, 'respond');
    });

    it('expect to call a single segment on the node', function () {
        expect($this->node)->toReceive('request')->with('sum');
        expect($this->node)->toReceive('respond')->with('sum');

        $this->request->sum();
        $this->respond->sum($this->handler);
    });

    it('expect to collect segments', function () {
        expect($this->node)->toReceive('request')->with('calculator.sum');
        expect($this->node)->toReceive('respond')->with('calculator.sum');

        $this->request->calculator->sum();
        $this->respond->calculator->sum($this->handler);
    });

    it('expect to not being called', function () {
        expect($this->node)->not->toReceive('request');
        expect($this->node)->not->toReceive('respond');

        $this->request->calculator->sum;
        $this->respond->calculator->sum;
    });
});
