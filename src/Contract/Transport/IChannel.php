<?php
namespace hisorange\PosixRPC\Contract\Transport;

use Psr\Log\LoggerInterface;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ITransport;
use hisorange\PosixRPC\Contract\ISubscription;
use hisorange\PosixRPC\Contract\Message\IPackager;

interface IChannel
{
    /**
     * Initialize the channel.
     *
     * @param ITransport $transport
     * @param string $name
     */
    public function __construct(ITransport $transport, IPackager $packager, LoggerInterface $logger, string $name, array $config = []);

    /**
     * Get the transport which opened the channel.
     *
     * @return ITransport
     */
    public function getTransport(): ITransport;

    /**
     * Get channel name in human readable format.
     *
     * @return  string
     */
    public function getName(): string;
    /**
     * Publish a message to the provided channel.
     *
     * @param string $channel
     * @param IMessage $message
     * @return void
     */
    public function publish(IMessage $message): void;

    /**
     * Subscribe to the provided channel and route messages to the handler.
     *
     * @param string $channel
     * @param callable $handler
     * @return ISubscription
     */
    public function subscribe(callable $handler): ISubscription;

    /**
     * Cancel the given subscription.
     *
     * @param ISubscription $subscription
     * @return void
     */
    public function unsubscribe(ISubscription $subscription): void;

    /**
     * Read / write the transport medium for new messages.
     *
     * @return void
     */
    public function tick(): void;
}
