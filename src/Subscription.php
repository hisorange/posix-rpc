<?php
namespace hisorange\PosixRPC;

use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ITransport;
use hisorange\PosixRPC\Contract\Transport\IChannel;

class Subscription implements Contract\ISubscription
{
    /**
     * A uniquish identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Channel's name, where the subscription is routed.
     *
     * @var IChannel
     */
    protected $channel;

    /**
     * Message handler for the channel.
     *
     * @var callable
     */
    protected $handler;

    /**
     * Initialize and configure the subscription.
     *
     * @param ITransport $transport
     * @param string $channel
     * @param callable $handler
     */
    public function __construct(IChannel $channel, callable $handler)
    {
        // Generate a uniquish identifier.
        $this->id = md5(mt_rand() . microtime());

        // Link the resources.
        $this->channel = $channel;
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getChannel(): IChannel
    {
        return $this->channel;
    }

    /**
     * @inheritDoc
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(): void
    {
        $this->channel->unsubscribe($this);
    }

    /**
     * @inheritDoc
     */
    public function handle(IMessage $message)
    {
        $handler = $this->handler;
        $message->setSubscription($this);

        $handler($message);
    }
}
