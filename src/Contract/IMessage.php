<?php
namespace hisorange\PosixRPC\Contract;

use hisorange\PosixRPC\Contract\ISubscription;

interface IMessage
{
    /**
     * Access to the message's unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Message meta.
     *
     * @param string $header
     * @return mixed
     */
    public function getMeta(string $header);

    /**
     * Read the unserialized payload.
     *
     * @return mixed
     */
    public function getPayload();

    /**
     * Read the subscription from the message.
     *
     * @return ISubscription|null
     */
    public function getSubscription(): ?ISubscription;

    /**
     * Attach a subscription to the message.
     *
     * @param ISubscription $subscription
     * @return void
     */
    public function setSubscription(ISubscription $subscription): void;

    /**
     * Set a meta header.
     *
     * @param string $header
     * @param mixed $value
     * @return void
     */
    public function setMeta(string $header, $value): void;
}
