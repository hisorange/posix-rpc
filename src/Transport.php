<?php
namespace hisorange\PosixRPC;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Message\Packager;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Transport\Channel;
use hisorange\PosixRPC\Contract\ITransport;
use hisorange\PosixRPC\Contract\ISubscription;
use hisorange\PosixRPC\Contract\Message\IPackager;
use hisorange\PosixRPC\Contract\Transport\IChannel;
use hisorange\PosixRPC\Exception\InvalidArgumentException;

class Transport implements ITransport
{
    use LoggerAwareTrait;

    /**
     * Link to the network node.
     *
     * @var INode
     */
    protected $node;

    /**
     * Message and stream packager.
     *
     * @var IPackager
     */
    protected $packager;

    /**
     * Channel name validator.
     *
     * @var IValidator
     */
    protected $channelValidator;

    /**
     * Active channel connections.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Transport configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * @inheritDoc
     */
    public function __construct(INode $node, array $config, LoggerInterface $logger)
    {
        // Link resources,
        $this->node = $node;
        $this->logger = $logger;

        // Override the configurations.
        $this->config = array_merge([
            'queuePermissions' => '0600',
            'maxPackageSize' => 8192,
        ], $config);

        // Validate the give configurations.
        $permissionValidator = new Validator\Permissions;

        if (!$permissionValidator->validate($this->config['queuePermissions'])) {
            throw new InvalidArgumentException(
                "Permission [" . $this->config['queuePermissions'] . "] must be a unix file system permission e.g: 0600"
            );
        }

        // Initialize the dependencies.
        $this->packager = $this->createPackager();
        $this->channelValidator = new Validator\ChannelName;

        $this->logger->info("Transport initialized");
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
    public function publish(string $channel, IMessage $message): void
    {
        $this->getChannel($channel)->publish($message);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(string $channel, callable $handler): ISubscription
    {
        return $this->getChannel($channel)->subscribe($handler);
    }

    /**
     * @inheritDoc
     */
    public function tick(): void
    {
        foreach ($this->channels as $channel) {
            $channel->tick();
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        foreach ($this->channels as $channel) {
            $channel->disconnect();
        }
    }

    /**
     * Create the message packager instance.
     *
     * @return IPackager
     */
    protected function createPackager(): IPackager
    {
        return new Packager(
            $this->node,
            [
                'maxPackageSize' => $this->config['maxPackageSize'],
            ],
            $this->logger
        );
    }

    /**
     * Upsert a channel when requested.
     *
     * @param string $channel
     * @return IChannel
     */
    protected function getChannel(string $channel): IChannel
    {
        if (!array_key_exists($channel, $this->channels)) {
            if (!$this->channelValidator->validate($channel)) {
                throw new InvalidArgumentException(
                    "Channel name [$channel] is not valid, please use alphanum and dot characters"
                );
            }

            $this->channels[$channel] = new Channel(
                $this,
                $this->packager,
                $this->logger,
                $channel,
                [
                    'maxPackageSize' => $this->config['maxPackageSize']
                ]
            );
        }

        return $this->channels[$channel];
    }
}
