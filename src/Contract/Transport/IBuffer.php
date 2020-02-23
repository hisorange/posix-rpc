<?php
namespace hisorange\PosixRPC\Contract\Transport;

/**
 * In memory buffer for messages, the transport only writes
 * when asked, and the messages are being queued in memory until
 * the transport is asked to write.
 */
interface IBuffer
{
    /**
     * Write packages to the channel's buffer.
     *
     * @param array $packages
     * @return void
     */
    public function write(array $packages): void;

    /**
     * Amount of packages in the buffer.
     *
     * @return integer
     */
    public function size(): int;

    /**
     * Reads the buffer back into an iterable format.
     *
     * @return array
     */
    public function iterator(): array;

    /**
     * Delete the package under the given index.
     *
     * @param integer $idx
     * @return void
     */
    public function delete(int $idx): void;
}
