<?php

use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\ReplyRouter;
use Psr\Log\NullLogger;

describe('ReplyRouter', function () {
    beforeEach(function () {
        $this->logger = new NullLogger;
        $this->node = new Node('test-node');
        $this->router = new ReplyRouter($this->node, $this->logger);
    });

    describe('->register()', function () {
        it('expect to configure the reply-to meta', function () {
            $request = new Message('test');
            $handler = function ($response) {
                expect($response)->toBeAnInstanceOf(Message::class);
                expect($response->getPayload())->toBe('test-response');
            };

            allow($this->node)->toReceive('subscribe')->andRun(function ($channel, $responseHandler) use (&$request) {
                expect($channel)->toBe('test.node.direct.reply');

                $response = new Message('test-response');
                $response->setMeta('reply-for', $request->getId());

                expect($responseHandler($response))->toBe(null);

                return (new Node('test-2'))->subscribe($channel, $responseHandler);
            });

            expect($this->node)->toReceive('subscribe');
            expect($request)->toReceive('setMeta')->with('reply-to', 'test.node.direct.reply');

            $this->router->register($request, $handler);
        });
    });

    describe('disconnect', function () {
        it('expect to cancel the subscription', function () {
            $request = new Message('request-1');

            $this->router->register($request, 'trim');

            expect($this->router->disconnect())->toBe(null);
        });
    });
});
