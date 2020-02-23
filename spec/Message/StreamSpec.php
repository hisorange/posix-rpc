<?php
use MessagePack\MessagePack;

use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Message\Stream;

describe('Message->Stream', function () {
    describe('::__construct()', function () {
        it('expect to provide id and chunk count', function () {
            expect(function () {
                new Stream;
            })->toThrow();

            expect(function () {
                new Stream('chunks');
            })->toThrow();

            expect(function () {
                new Stream(1);
            })->not->toThrow();
        });
    });

    describe('::push()', function () {
        beforeEach(function () {
            $this->stream = new Stream(2);
        });

        it('expect to accept only messages', function () {
            expect(function () {
                $this->stream->push('string');
            })->toThrow();

            expect(function () {
                $this->stream->push(new Message(1));
            })->not->toThrow();
        });

        it('expect to return true when the chunks reached the expected size', function () {
            expect($this->stream->push(new Message(1)))->toBe(false);
            expect($this->stream->push(new Message(2)))->toBe(true);
            expect($this->stream->push(new Message(3)))->toBe(false);
        });
    });

    describe('::join()', function () {
        it('expect to joint messages', function () {
            $stream = new Stream(11);
            $original = '0123456789';
            $blob = MessagePack::pack($original); // 1 byte type + 10 byte content

            for ($i=0; $i < 11; $i++) {
                $stream->push(new Message(substr($blob, $i, 1), [
                    'stream' => [
                        'order' => $i,
                    ]
                ]));
            }

            $message = $stream->join();

            expect($message)->toBeAnInstanceOf('hisorange\\PosixRPC\\Message');
            expect($original)->toBe($message->getPayload());
        });
    });
});
