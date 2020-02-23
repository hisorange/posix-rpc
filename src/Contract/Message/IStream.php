<?php
namespace hisorange\PosixRPC\Contract\Message;

use hisorange\PosixRPC\Contract\IMessage;

interface IStream
{
    /**
     * Initialize a new stream.
     *
     * @param integer $chunks
     */
    public function __construct(int $chunks);

    /**
     * Append a message to the end of the stream,
     * and return the stream's state.
     *
     * @param Message $chunk
     * @return boolean
     */
    public function push(IMessage $chunk): bool;

    /**
     * Join the message chunks and create
     * the final message from it.
     *
     * @return IMessage
     */
    public function join(): IMessage;
}
