<?php
namespace hisorange\PosixRPC;

use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ISubscription;

class Message implements IMessage
{
    /**
     * Reply channel for requests.
     *
     * @var string
     */
    public $meta;

    /**
     * Serialized message payload.
     *
     * @var string
     */
    protected $payload;

    /**
     * Channel's name where the message was received.
     *
     * @var string
     */
    protected $channel;

    /**
     * Link to the receiver node.
     *
     * @var INode
     */
    protected $node;


    /**
     * Reply channel for requests.
     *
     * @var ISubscription
     */
    protected $subscription;

    /**
     * Create a message with the given payload.
     *
     * @param mixed $payload
     */
    public function __construct($payload, array $meta = [])
    {
        $this->payload = $payload;
        $this->meta = array_merge([
            'id' => md5(mt_rand()),
            'version' => 1,
            'created-at' => microtime(true),
            'reply-to' => null,
            'reply-for' => null,
            'from' => null,
            'channel' => null,
            'stream' => false,
        ], $meta);
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->meta['id'];
    }

    /**
     * @inheritDoc
     */
    public function getNode(): INode
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    public function setNode(INode $node): void
    {
        $this->node = $node;
    }

    /**
     * @inheritDoc
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @inheritDoc
     */
    public function setSubscription(ISubscription $subscription): void
    {
        $this->subscription = $subscription;
    }

    /**
     * @inheritDoc
     */
    public function getSubscription(): ?ISubscription
    {
        return $this->subscription;
    }

    public function getMeta(string $key)
    {
        return isset($this->meta[$key]) ? $this->meta[$key]: null;
    }

    public function setMeta($header, $value):void
    {
        $this->meta[$header] = $value;
    }
}
