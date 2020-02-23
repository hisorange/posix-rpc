<?php
namespace hisorange\PosixRPC\Validator;

use hisorange\PosixRPC\Contract\IValidator;

class ChannelName implements IValidator
{
    /**
     * @inheritDoc
     */
    public function validate($name): bool
    {
        if (is_string($name)) {
            return preg_match('%^[a-z]((\.)?[a-z\d]+)*$%', $name);
        }

        return false;
    }
}
