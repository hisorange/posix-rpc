<?php
namespace hisorange\PosixRPC;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Evenement\EventEmitterTrait;
use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\ITransport;
use hisorange\PosixRPC\Contract\ISubscription;

class Node implements INode
{
    use LoggerAwareTrait;
    use EventEmitterTrait;

    /**
     * Node's unique identifier on the network.
     *
     * @var string
     */
    protected $id;

    /**
     * Transport layer used to communicate on the network.
     *
     * @var ITransport
     */
    protected $transport;

    /**
     * Instance for the reply router, to handle RPC response.
     *
     * @var IReplyRouter
     */
    protected $replyRouter;

    /**
     * Fluent call collector for request forgery.
     *
     * @var Fluent
     */
    public $request;

    /**
     * Fluent call collector for response forgery.
     *
     * @var Fluent
     */
    public $respond;

    /**
     * @inheritDoc
     */
    public function __construct(string $id, array $config = [], LoggerInterface $logger = null)
    {
        $idValidator = new Validator\NodeId;

        if (!$idValidator->validate($id)) {
            throw new Exception\InvalidArgumentException("Identifier [$id] is not valid");
        }

        $this->id = $id;

        // Create an std out logger if none provided.
        $this->setLogger($logger ?? new NullLogger());

        // Configure the transport's resources.
        $this->transport = $this->createTransport($config['transport'] ?? []);

        // Register the reply router for RPC response routing.
        $this->replyRouter = new ReplyRouter($this, $this->logger);

        // Register the fluent call collectors.
        $this->request = new Fluent($this, 'request');
        $this->respond = new Fluent($this, 'respond');

        $this->logger->info("Node [$this->id] is initialized");
    }

    /**
     * Create the transport layer with the user provided configuration.
     *
     * @param array $config
     * @return ITransport
     */
    protected function createTransport(array $config): ITransport
    {
        return new Transport($this, $config, $this->logger);
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
    public function publish(string $channel, $payload = null): void
    {
        $message = $payload instanceof Message ? $payload : new Message($payload);
        $message->setMeta('from', $this->id);

        $this->transport->publish($channel, $message);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(string $channel, callable $handler): ISubscription
    {
        return $this->transport->subscribe($channel, $handler);
    }

    /**
     * @inheritDoc
     */
    public function request(string $channel, $payload = null, callable $handler = null)
    {
        $isSynchronous = $handler === null;

        // Synchronous call when no handler is provided.
        if ($isSynchronous) {
            $isArrived = false;
            $responseProxy = null;

            // Local handler to dispatch the proxied result.
            $handler = function (IMessage $response) use (&$isArrived, &$responseProxy) {
                $isArrived = true;
                $responseProxy = $response->getPayload();
            };
        }

        $request = new Message($payload);

        $this->replyRouter->register($request, $handler);
        $this->publish($channel, $request);

        if ($isSynchronous) {
            while (!$isArrived) {
                $this->tick();

                usleep(100);
            }

            return $responseProxy;
        }
    }

    /**
     * @inheritDoc
     */
    public function respond(string $channel, callable $handler): void
    {
        $this->transport->subscribe($channel, function (IMessage $request) use ($handler) {
            // Execute the handler to create the result.
            $response = $handler($request);
            $response = $response instanceof Message ? $response : new Message($response);

            // Associate the reply with the request message.
            $response->setMeta('reply-for', $request->getId());

            $this->publish($request->getMeta('reply-to'), $response);
        });
    }

    /**
     * @inheritDoc
     */
    public function tick(): void
    {
        $this->transport->tick();
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->transport->disconnect();
    }
}
