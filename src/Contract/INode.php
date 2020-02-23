<?php
namespace hisorange\PosixRPC\Contract;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

interface INode extends LoggerAwareInterface
{
    /**
     * Initialize and configure the network node.
     *
     * @param string $id
     * @param array $config
     * @param LoggerInterface $logger
     */
    public function __construct(string $id, array $config = [], LoggerInterface $logger = null);

    /**
     * Accessor for the node id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Emit an event to every listener on the network.
     *
     * @param string $channel
     * @param mixed $payload
     * @return void
     */
    public function publish(string $channel, $payload): void;

    /**
     * Subscribe to an event on the network.
     *
     * @param string $channel
     * @param callable $handler
     * @return ISubscription
     */
    public function subscribe(string $channel, callable $handler): ISubscription;

    /**
     * Send a request to the responder.
     *
     * @param string $channel
     * @param mixed $payload
     * @param callable $handler
     * @return void|mixed
     */
    public function request(string $channel, $payload = null, callable $handler = null);

    /**
     * Answer to a request.
     *
     * @param string $channel
     * @param callable $handler
     * @return void
     */
    public function respond(string $channel, callable $handler): void;

    /**
     * Pool the transport for new messages.
     *
     * @return void
     */
    public function tick(): void;

    /**
     * Disconnect from the transport and deregister the listeners.
     *
     * @return void
     */
    public function disconnect(): void;
}
