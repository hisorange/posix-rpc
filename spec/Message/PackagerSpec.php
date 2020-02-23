<?php

use Psr\Log\NullLogger;
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Message\Packager;
use hisorange\PosixRPC\Message\Stream;

describe('Message\Packager', function () {
    beforeEach(function () {
        $node = new Node('test');
        $config = ['maxPackageSize' => 1024];
        $logger = new NullLogger;

        $this->packager = new Packager($node, $config, $logger);
    });

    describe('::pack()', function () {
        it('expect to create a single package', function () {
            $content = 'test-data';
            $message = new Message($content);

            $packages = $this->packager->pack($message);

            expect($packages)->toBeA('array');
            expect($packages)->toHaveLength(1);
            expect($packages)->toContainKey(0);

            $package = $packages[0];

            expect($package)->toBeA('string');
        });


        it('expect to create a stream of package', function () {
            $content = str_repeat('data', 1024);
            $message = new Message($content);

            $packages = $this->packager->pack($message);

            expect($packages)->toBeA('array');
            expect($packages)->toHaveLength(6);
        });
    });

    describe('::unpack()', function () {
        it('expect to handle the null bytes as data', function () {
            $content = chr(10) . '1' . chr(0);
            $message = new Message($content);

            $packages = $this->packager->pack($message);
            $unpacked = $this->packager->unpack($packages[0]);

            expect($unpacked->getPayload())->toBe($content);
        });

        it('expect to unpack a stream into a single message', function () {
            $content = str_repeat('dat' . chr(31), 1024);
            $message = new Message($content);

            $packages = $this->packager->pack($message);
            $results = [];

            foreach ($packages as $package) {
                $results[] = $this->packager->unpack($package);
            }

            expect($results[0])->toBeAnInstanceOf(Stream::class);
            expect($results[1])->toBeAnInstanceOf(Stream::class);
            expect($results[2])->toBeAnInstanceOf(Stream::class);
            expect($results[3])->toBeAnInstanceOf(Stream::class);
            expect($results[4])->toBeAnInstanceOf(Stream::class);

            expect($results[5])->toBeAnInstanceOf(Message::class);
            expect($results[5]->getPayload())->toBe($content);
        });

        it('expect to keep the data structure', function () {
            $set = [
                true,
                false,
                null,
                0xff,
                0001,
                1002,
                'string',
                'Specíál',
                'Binary->' . chr(0) . chr(10) . chr(20),
                ['a', 'r', 'r', 'a', 'y'],
                ['nes' => ['t', 'e' => 'd'], 1],
                ['nes' => ['t', 'e' => 'd'], 1 => 2],
            ];

            foreach ($set as $entry) {
                $packages = $this->packager->pack(new Message($entry));

                foreach ($packages as $package) {
                    $unpacked = $this->packager->unpack($package);
                }

                expect($unpacked)->toBeAnInstanceOf(Message::class);
                expect($unpacked->getPayload())->toBe($entry);
            }
        });
    });
});
