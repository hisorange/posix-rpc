<?php
namespace hisorange\PosixRPC\Validator;

use hisorange\PosixRPC\Contract\IValidator;

class Permissions implements IValidator
{
    /**
     * @inheritDoc
     */
    public function validate($permissions): bool
    {
        if (is_numeric($permissions)) {
            return preg_match('%^[024]?[4670]{3}$%', $permissions);
        }

        return false;
    }
}
