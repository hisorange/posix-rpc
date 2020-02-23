<?php
namespace hisorange\PosixRPC;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IMessage;
use hisorange\PosixRPC\Contract\IReplyRouter;
use hisorange\PosixRPC\Contract\ISubscription;

class ReplyRouter implements IReplyRouter
{
    use LoggerAwareTrait;

    /**
     * Link to the owner node.
     *
     * @var INode
     */
    protected $node;

    /**
     * Unique channel for the node, to receive
     * direct reply messages.
     *
     * @var string
     */
    protected $channel;

    /**
     * Transaction stack for awaiting requests.
     *
     * @var array
     */
    protected $transactions = [];

    /**
     * Subscription created for direct reply handling.
     *
     * @var ISubscription|null
     */
    protected $subscription = null;

    /**
     * @inheritDoc
     */
    public function __construct(INode $node, LoggerInterface $logger)
    {
        // Associate the owner node.
        $this->node = $node;
        $this->logger = $logger;

        // This channel is used to save on reply channels,
        // every reply arrives here, but routed to their respective callbacks.
        $this->channel = preg_replace('%[^a-z\.\d]%', '.', $this->node->getId()) . '.direct.reply';
    }

    /**
     * Check if the subscription is active or not.
     *
     * @return boolean
     */
    protected function isSubscribed(): bool
    {
        return $this->subscription !== null;
    }

    /**
     * Create the subscription to handle the direct replies.
     *
     * @return void
     */
    protected function subscribe(): void
    {
        if (!$this->isSubscribed()) {
            $this->subscription = $this->node->subscribe($this->channel, function (IMessage $reply) {
                $transactionId = $reply->getMeta('reply-for');

                // Lookup the transaction handler by it's ID.
                if (isset($this->transactions[$transactionId])) {
                    $handler = $this->transactions[$transactionId];

                    unset($this->transactions[$transactionId]);

                    // Remove the subscription if no response is expected.
                    if (!$this->hasActiveTransaction()) {
                        $this->unsubscribe();
                    }

                    $handler($reply);
                } else {
                    $this->logger->warning("Unhandled reply!");
                }
            });
        }
    }

    /**
     * Remove the subscription when no answer is expected.
     *
     * @return void
     */
    protected function unsubscribe(): void
    {
        if ($this->isSubscribed()) {
            $this->subscription->unsubscribe();
            $this->subscription = null;
        }
    }

    /**
     * Check if there is any transaction waiting for reply.
     *
     * @return bool
     */
    protected function hasActiveTransaction(): bool
    {
        return !empty($this->transactions);
    }

    /**
     * @inheritDoc
     */
    public function register(IMessage $message, callable $handler)
    {
        $message->setMeta('reply-to', $this->channel);
        $this->transactions[$message->getId()] = $handler;

        // Lazy connect the subscription.
        if (!$this->isSubscribed()) {
            $this->subscribe();
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->unsubscribe();
        $this->transactions = [];
    }
}
