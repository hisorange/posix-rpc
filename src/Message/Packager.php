<?php
namespace hisorange\PosixRPC\Message;

use MessagePack\MessagePack;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;

use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\Message\IPackager;
use hisorange\PosixRPC\Message;

class Packager implements IPackager
{
    use LoggerAwareTrait;

    /**
     * Link to the owner node.
     *
     * @var Contract\INode
     */
    protected $node;

    /**
     * Packager configuration.
     *
     * @var array
     */
    protected $config = [
        'maxPackageSize' => 8192,
    ];

    /**
     * Tracker for the active streams until
     * the last package is arrived.
     *
     * @var array
     */
    protected $streams = [];

    /**
     * @inheritDoc
     */
    public function __construct(INode $node, array $config, LoggerInterface $logger)
    {
        $this->node = $node;
        $this->config = array_merge($this->config, $config);
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function pack(IMessage $message): array
    {
        $package = $this->serialize($message);
        $packageSize = strlen($package);

        if ($packageSize <= $this->config['maxPackageSize']) {
            return [$package];
        }

        return $this->packStream($message);
    }

    /**
     * Package the message in multiple package and assign
     * them to a stream.
     *
     * @param IMessage $message
     * @return array
     */
    protected function packStream(IMessage $message): array
    {
        $packages = [];
        $streamId = md5(mt_rand());

        // Prepare the payload for calculations and chunking.
        $blobData = MessagePack::pack($message->getPayload());
        $blobSize = strlen($blobData);
        $blobSign = crc32($blobData);

        // Measure the meta size, so we can calculate the maximum data size.
        $metaSize = strlen(MessagePack::pack(array_merge($message->meta, [
            'stream' => [
                'id' => $streamId,
                'order' => PHP_INT_MAX,
                'chunks' => PHP_INT_MAX,
                'size' => PHP_INT_MAX,
                'sign' => PHP_INT_MAX,
            ],
        ])));

        // Package size formula: meta + data + 15 byte separator in json
        $chunkSize = $this->config['maxPackageSize'] - ($metaSize + 15);
        $chunkCount = ceil($blobSize / $chunkSize);

        for ($i=0; $i < $chunkCount; $i++) {
            $data = substr($blobData, $chunkSize * $i, $chunkSize);
            $meta = array_merge($message->meta, [
                'stream' => [
                    'id' => $streamId,
                    'order' => $i,
                    'chunks' => $chunkCount,
                    'size' => $blobSize,
                    'sign' => $blobSign,
                ],
            ]);

            $packages[] = MessagePack::pack(compact('meta', 'data'));
        }

        return $packages;
    }

    /**
     * @inheritDoc
     */
    public function serialize(IMessage $msg): string
    {
        return MessagePack::pack([
            'meta' => $msg->meta,
            'data' => $msg->getPayload(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function unpack(string $package)
    {
        $message = $this->unserialize($package);

        // Single chunk package, can dispatch here.
        if ($message->meta['stream'] == false) {
            return $message;
        }

        return $this->unpackStream($message);
    }

    /**
     * Unpack a multi package message stream.
     *
     * @param IMessage $message
     * @return IMessage|IStream
     */
    protected function unpackStream(IMessage $message)
    {
        // Identify the stream.
        $streamId = $message->meta['stream']['id'];

        // Check if the stream is already started, if not
        // then add register it as the first package has already arrived.
        if (!isset($this->streams[$streamId])) {
            $this->streams[$streamId] = new Stream($message->meta['stream']['chunks']);
        }

        $stream = $this->streams[$streamId];
        $ready = $stream->push($message);

        if ($ready) {
            // Remove the reference for the stream,
            // before it is dispatched as the final message.
            unset($this->streams[$streamId]);

            return $stream->join();
        } else {
            return $stream;
        }
    }

    /**
     * @inheritDoc
     */
    public function unserialize(string $package): IMessage
    {
        $arr = MessagePack::unpack($package);
        $msg = new Message($arr['data'], $arr['meta']);

        return $msg;
    }
}
