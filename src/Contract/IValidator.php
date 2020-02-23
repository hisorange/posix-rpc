<?php
namespace hisorange\PosixRPC\Contract;

interface IValidator
{
    /**
     * Validate the given input and return a boolean result.
     *
     * @param mixed $input
     * @return boolean
     */
    public function validate($input): bool;
}
