<?php
namespace hisorange\PosixRPC\Contract;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

interface IReplyRouter extends LoggerAwareInterface
{
    /**
     * Initialize and configure the reply router.
     *
     * @param INode $node
     * @param LoggerInterface $logger
     */
    public function __construct(INode $node, LoggerInterface $logger);

    /**
     * Register a transaction for reply handling.
     *
     * @param IMessage $message
     * @param callable $handler
     * @return void
     */
    public function register(IMessage $message, callable $handler);

    /**
     * Unsubscribe and reset the transactions.
     *
     * @return void
     */
    public function disconnect(): void;
}
