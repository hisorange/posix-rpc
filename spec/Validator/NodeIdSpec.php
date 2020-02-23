<?php
use hisorange\PosixRPC\Validator\NodeId;

describe('Validator->NodeId', function () {
    beforeEach(function () {
        $this->validator = new NodeId;
    });

    it('expect at least one argument', function () {
        expect(function () {
            $this->validator->validate();
        })->toThrow();
    });

    it('expect a string argument', function () {
        expect($this->validator->validate([]))->toBe(false);
    });

    it('expect the node id to only consist lowercase alphanum chars and dot', function () {
        expect($this->validator->validate('NAME'))->toBe(false);
    });

    it('expect the node id to start with a letter', function () {
        expect($this->validator->validate('4ame'))->toBe(false);
    });

    it('expect the node id not to start with dot', function () {
        expect($this->validator->validate('-name'))->toBe(false);
    });

    it('expect the node id not to end with dot', function () {
        expect($this->validator->validate('name-'))->toBe(false);
    });

    it('expect the node id to disallow multiple dot in a group', function () {
        expect($this->validator->validate('nam--e'))->toBe(false);
    });

    it('expect the node id to accept valid name', function () {
        expect($this->validator->validate('name'))->toBe(true);
    });

    it('expect the node id to accept valid separator', function () {
        expect($this->validator->validate('name-separator'))->toBe(true);
    });
});
