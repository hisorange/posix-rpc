<?php
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Message;

describe('Message', function () {
    describe('ID', function () {
        it('expect to create a random id', function () {
            $msg = new Message(null);

            expect($msg->getId())->toBeTruthy();
            expect($msg->getId())->toBeA('string');
            expect($msg->getId())->toHaveLength(32);
            expect($msg->getId())->toMatch('%^[a-f0-9]+$%');
        });

        it('expect to return the given payload', function () {
            $msg = new Message('a');
            expect($msg->getPayload())->toBe('a');
        });
    });


    describe('node', function () {
        it('expect to store the node', function () {
            $node = new Node('test');

            $msg = new Message(null);

            expect($msg->setNode($node))->toBe(null);
            expect($msg->getNode())->toBe($node);
        });
    });

    describe('getPayload', function () {
        it('expect to store the payload', function () {
            $msg = new Message('data');

            expect($msg->getPayload())->toBe('data');
        });
    });

    describe('getMeta', function () {
        it('expect to store the meta', function () {
            $msg = new Message(null);

            expect($msg->setMeta('test', 12))->toBe(null);
            expect($msg->getMeta('test'))->toBe(12);
        });
    });
});
