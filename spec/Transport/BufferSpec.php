<?php
use hisorange\PosixRPC\Transport\Buffer;

describe('Transport->Buffer', function () {
    beforeEach(function () {
        $this->buffer  = new Buffer;
    });

    it('expect to construct', function () {
        expect(function () {
            new Buffer;
        })->not->toThrow();
    });


    describe('::write()', function () {
        it('expect to write one message to the buffer', function () {
            expect($this->buffer->size())->toBe(0);

            $this->buffer->write(['a']);

            expect($this->buffer->size())->toBe(1);
        });


        it('expect to write multiple message to the buffer', function () {
            expect($this->buffer->size())->toBe(0);

            $this->buffer->write(['a', 'b', 'c']);

            expect($this->buffer->size())->toBe(3);
        });

        it('expect to create package entries', function () {
            $this->buffer->write(['a']);
            $this->buffer->write(['b']);

            expect($this->buffer->size())->toBe(2);

            $iterator = $this->buffer->iterator();

            expect($iterator)->toBeA('array');
            expect($iterator)->toContainKey([0, 1]);

            expect($iterator[0])->toBe('a');
            expect($iterator[1])->toBe('b');
        });
    });


    describe('::size()', function () {
        it('expect to reflect the amount of package in the queue', function () {
            expect($this->buffer->size())->toBeA('integer');

            expect($this->buffer->size())->toBe(0);

            $this->buffer->write(['a']);

            expect($this->buffer->size())->toBe(1);

            $this->buffer->write(['b']);

            expect($this->buffer->size())->toBe(2);

            $this->buffer->delete(0);

            expect($this->buffer->size())->toBe(1);

            $this->buffer->delete(1);

            expect($this->buffer->size())->toBe(0);
        });
    });

    describe('::iterator()', function () {
        it('expect to give back the package', function () {
            expect($this->buffer->iterator())->toBe([]);
            expect($this->buffer->iterator())->toHaveLength(0);

            $this->buffer->write(['a']);

            expect($this->buffer->iterator())->toContainKey(0);
            expect($this->buffer->iterator()[0])->toBe('a');
        });
    });

    describe('::delete()', function () {
        it('expect to remove the given index', function () {
            $this->buffer->write(['a']);
            $this->buffer->write(['b']);

            expect($this->buffer->size())->toBe(2);

            $this->buffer->delete(0);

            expect($this->buffer->iterator())->not->toContainKey(0);
            expect($this->buffer->iterator())->toContainKey(1);

            expect($this->buffer->iterator()[1])->toBe('b');
        });
    });
});
