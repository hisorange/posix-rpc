<?php
namespace hisorange\PosixRPC\Message;

use MessagePack\MessagePack;

use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Contract\IMessage;

class Stream
{
    /**
     * Number of chunks expected in the stream.
     *
     * @var int
     */
    protected $chunks;

    /**
     * Message buffer.
     *
     * @var array
     */
    protected $buffer;

    /**
     * @inheritDoc
     */
    public function __construct(int $chunks)
    {
        $this->chunks = $chunks;
        $this->buffer = [];
    }

    /**
     * @inheritDoc
     */
    public function push(IMessage $chunk): bool
    {
        return array_push($this->buffer, $chunk) === $this->chunks;
    }

    /**
     * @inheritDoc
     */
    public function join(): IMessage
    {
        $order = [];
        $chunks = [];

        foreach ($this->buffer as $chunk) {
            $chunks[] = $chunk->getPayload();
            $order[] = $chunk->meta['stream']['order'];
        }

        array_multisort($order, $chunks);

        $data = join('', $chunks);
        $size = strlen($data);
        $sign = crc32($data);

        // TODO: validate the sign and size.

        // Pull the message meta from the first message.
        $meta = $this->buffer[0]->meta;
        // Remove the stream reference.
        $meta['stream'] = false;

        return new Message(MessagePack::unpack($data), $meta);
    }
}
