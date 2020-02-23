<?php
use hisorange\PosixRPC\Validator\Permissions;

describe('Validator->Permissions', function () {
    beforeEach(function () {
        $this->validator = new Permissions;
    });

    it('expect at least one argument', function () {
        expect(function () {
            $this->validator->validate();
        })->toThrow();
    });

    it('expect a numeric argument', function () {
        expect($this->validator->validate([]))->toBe(false);
    });

    it('expect accept every valid permission', function () {
        foreach ([null, 0, 2] as $s) {
            foreach ([0,4,6,7] as $u) {
                foreach ([0,4,6,7] as $g) {
                    foreach ([0,4,6,7] as $w) {
                        expect($this->validator->validate(($s === null ? '' : $s).$u.$g.$w))->toBe(true);
                    }
                }
            }
        }
    });

    it('expect the filter invalid permissions', function () {
        expect($this->validator->validate(888))->toBe(false);
        expect($this->validator->validate(999))->toBe(false);
        expect($this->validator->validate(6000))->toBe(false);
        expect($this->validator->validate(3600))->toBe(false);
    });
});
