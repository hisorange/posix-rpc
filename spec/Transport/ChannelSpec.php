<?php

use hisorange\PosixRPC\Message\Packager;
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Transport;
use hisorange\PosixRPC\Transport\Channel;
use Psr\Log\NullLogger;

describe('Transport->Channel', function () {
    beforeEach(function () {
        $this->logger = new NullLogger;
        $this->node = new Node('test');
        $this->packager = new Packager($this->node, [], $this->logger);
        $this->transport = new Transport($this->node, [], $this->logger);
        $this->channel = new Channel($this->transport, $this->packager, $this->logger, 'test');

        allow($this->channel)->toReceive('write')->andReturn('');
        allow($this->channel)->toReceive('read')->andReturn('');
    });

    describe('::getName()', function () {
        it('expect to return the given name', function () {
            expect($this->channel->getName())->toBe('test');
        });
    });
});
