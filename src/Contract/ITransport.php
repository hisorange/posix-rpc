<?php
namespace hisorange\PosixRPC\Contract;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ISubscription;

interface ITransport extends LoggerAwareInterface
{
    /**
     * Initialize and configure the transport.
     *
     * @param Contract\INode $node
     * @param array $config
     * @param LoggerInterface $logger
     */
    public function __construct(INode $node, array $config, LoggerInterface $logger);

    /**
     * Accessor to the parent node.
     *
     * @return INode
     */
    public function getNode(): INode;

    /**
     * Publish a message to the provided channel.
     *
     * @param string $channel
     * @param IMessage $message
     * @return void
     */
    public function publish(string $channel, IMessage $message): void;

    /**
     * Subscribe to the provided channel and route messages to the handler.
     *
     * @param string $channel
     * @param callable $handler
     * @return ISubscription
     */
    public function subscribe(string $channel, callable $handler): ISubscription;

    /**
     * Read / write the transport medium for new messages.
     *
     * @return void
     */
    public function tick(): void;

    /**
     * Close the connection.
     *
     * @return void
     */
    public function disconnect(): void;
}
