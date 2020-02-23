<?php
namespace hisorange\PosixRPC\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use hisorange\PosixRPC\Message;
use hisorange\PosixRPC\Subscription;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ITransport;
use hisorange\PosixRPC\Contract\ISubscription;
use hisorange\PosixRPC\Contract\Message\IPackager;
use hisorange\PosixRPC\Contract\Transport\IChannel;
use hisorange\PosixRPC\Exception\MessageRead as MessageReadException;
use hisorange\PosixRPC\Exception\MessageWrite as MessageWriteException;

class Channel implements IChannel
{
    use LoggerAwareTrait;

    /**
     * Salt used to create a uniquish queue key for IPC connections.
     *
     * @var string
     */
    const CHANNEL_SALT = 'Channel.Salt.1';

    /**
     * Channel name in human readable format.
     *
     * @var string
     */
    protected $name;

    /**
     * Connection resource to the IPC queue.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Link to the transport.
     *
     * @var ITransport
     */
    protected $transport;

    /**
     * Link to the message packager.
     *
     * @var IPackager
     */
    protected $packager;

    /**
     * Package buffering.
     *
     * @var IBuffer
     */
    protected $buffer;

    /**
     * Dictionary to track the active subscribtions.
     *
     * @var array
     */
    protected $subscriptions = [];

    /**
     * Channel configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * @inheritDoc
     */
    public function __construct(ITransport $transport, IPackager $packager, LoggerInterface $logger, string $name, array $config = [])
    {
        $this->transport = $transport;
        $this->packager = $packager;
        $this->logger = $logger;
        $this->name = $name;
        $this->config = $config;
        $this->buffer = new Buffer;
    }

    /**
     * @inheritDoc
     */
    public function getTransport(): ITransport
    {
        return $this->transport;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function publish(IMessage $message): void
    {
        // Associate the message with the channel.
        $message->setMeta('channel', $this->name);
        // Write to the buffer.
        $this->buffer->write($this->packager->pack($message));
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): ISubscription
    {
        // Create the subscription.
        $subscription = new Subscription($this, $handler);
        // Register the subscribtion under it's identifier.
        $this->subscriptions[$subscription->getId()] = $subscription;

        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function tick(): void
    {
        // TODO: optimize connection and close when not needed.
        // Only open when there is active subscription and reading from it.
        // Close when the buffer is empty and no1 is subscribed.
        $this->connect();

        $this->readPackages();
        $this->writePackages();
    }

    /**
     * Try to flush the buffer into the queue.
     *
     * @return void
     */
    protected function writePackages(): void
    {
        $this->logger->debug("Writing [{$this->name}] channel with packages");

        foreach ($this->buffer->iterator() as $idx => $package) {
            if ($this->write($package)) {
                $this->buffer->delete($idx);
            }
        }
    }

    /**
     * Read the queue for new messages.
     *
     * @return void
     */
    protected function readPackages()
    {
        $this->logger->debug("Reading [{$this->name}] channel for packages");

        foreach ($this->subscriptions as $subscription) {
            while (($package = $this->read()) != false) {
                $result = $this->packager->unpack($package);

                if ($result instanceof Message) {
                    $subscription->handle($result);
                }
            }
        }
    }

    /**
     * Write the package to the IPC queue.
     *
     * @param string $package
     * @return bool
     */
    protected function write(string $package): bool
    {
        $error_code = null;
        $msg_type = 1;

        $success = @msg_send($this->connection, $msg_type, $package, false, false, $error_code);

        if ($error_code === MSG_EAGAIN) {
            return false;
        }

        if ($error_code !== null) {
            if ($error_code === 22) {
                throw new MessageWriteException("Package is too big! Configured with [" . $this->config['maxPackageSize'] . "] the package's actual size is [" . strlen($package) . "] byte");
            }

            throw new MessageWriteException("PosixQueue [$error_code] error code on message writing!");
        }

        if (!$success) {
            $this->logger->error('Could not write package!');
            exit(1);
        }

        return true;
    }

    /**
     * Try to read the queue for the desired message type.
     *
     * @return string|null
     */
    protected function read(): ?string
    {
        $message = null;
        $error_code = null;
        $received_type = null;

        @msg_receive($this->connection, 1, $received_type, $this->config['maxPackageSize'], $message, false, MSG_IPC_NOWAIT, $error_code);

        if ($error_code === MSG_ENOMSG) {
            return null;
        }

        if ($error_code !== null && $error_code !== 0) {
            throw new MessageReadException("PosixQueue [$error_code] on message reading!");
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(ISubscription $subscription): void
    {
        $this->logger->info("Unsubscribing from [" . $this->name . "] channel");

        unset($this->subscriptions[$subscription->getId()]);
    }

    /**
     * @inheritDoc
     */
    protected function isConnected(): bool
    {
        return is_resource($this->connection);
    }

    /**
     * @inheritDoc
     */
    protected function connect(): bool
    {
        if (!$this->isConnected()) {
            $this->logger->debug("Connection to [$this->name] channel");

            return !!($this->connection = msg_get_queue($this->getIPCKeyFormat(), 0666));
        }

        return false;
    }

    /**
     * Convert the channel name into a positive integer for IPC keying.
     *
     * @return int
     */
    protected function getIPCKeyFormat(): int
    {
        return abs(crc32(static::CHANNEL_SALT . $this->name));
    }

    /**
     * @inheritDoc
     */
    protected function disconnect(): bool
    {
        if ($this->isConnected()) {
            $success = msg_remove_queue($this->connection);

            if ($success) {
                $this->connection = null;
            }

            return $success;
        }

        return false;
    }
}
