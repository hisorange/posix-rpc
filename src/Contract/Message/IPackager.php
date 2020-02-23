<?php
namespace hisorange\PosixRPC\Contract\Message;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IMessage;

/**
 * Handles the message to package conversion.
 */
interface IPackager extends LoggerAwareInterface
{
    /**
     * Initialize and configure the message packager.
     *
     * @param INode $node
     * @param array $config
     * @param LoggerInterface $logger
     */
    public function __construct(INode $node, array $config, LoggerInterface $logger);

    /**
     * Pack the message into one or more package,
     * if the message would not fit into a single message,
     * then it creates a "stream" of messages.
     *
     * @param IMessage $message
     * @return array $packages
     */
    public function pack(IMessage $message): array;

    /**
     * Unpack a package into a message object,
     * if the message is part of a multi package
     * stream, then return the stream instead,
     * until the last package of the stream arrives.
     *
     * @param string $package
     * @return IMessage|IStream
     */
    public function unpack(string $package);

    /**
     * Serialize the message into a string format.
     *
     * @param IMessage $message
     * @return string
     */
    public function serialize(IMessage $message): string;

    /**
     * Unserialize the package back into the message format.
     *
     * @param string $package
     * @return IMessage
     */
    public function unserialize(string $package): IMessage;
}
