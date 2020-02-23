<?php
namespace hisorange\PosixRPC\Transport;

use hisorange\PosixRPC\Contract\Transport\IBuffer;

class Buffer implements IBuffer
{
    /**
     * In memory sorted queue for the packages.
     *
     * @var array
     */
    protected $packages = [];

    /**
     * @inheritDoc
     */
    public function write(array $packages): void
    {
        // Should not use the trick or any mapping here,
        // the PHP engine can't optimize for those, but
        // this code structure will be optimized.
        foreach ($packages as $package) {
            $this->packages[] = $package;
        }
    }

    /**
     * @inheritDoc
     */
    public function size(): int
    {
        return sizeof($this->packages);
    }

    /**
     * @inheritDoc
     */
    public function iterator(): array
    {
        return $this->packages;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $idx): void
    {
        unset($this->packages[$idx]);
    }
}
