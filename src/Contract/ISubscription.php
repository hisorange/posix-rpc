<?php
namespace hisorange\PosixRPC\Contract;

use hisorange\PosixRPC\Contract\Transport\IChannel;

interface ISubscription
{
    /**
     * Create a new subscription linked to a transport.
     *
     * @param Transport\IChannel $channel
     * @param callable $handler
     */
    public function __construct(IChannel $channel, callable $handler);

    /**
     * Get the subscription's unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the channel.
     *
     * @return Transport\IChannel
     */
    public function getChannel(): IChannel;

    /**
     * Get the message handler.
     *
     * @return callable
     */
    public function getHandler(): callable;

    /**
     * Cancel the subscription.
     *
     * @return void
     */
    public function unsubscribe(): void;

    /**
     * Call the handler on the expected message.
     *
     * @param IMessage $message
     * @return mixed
     */
    public function handle(IMessage $message);
}
