<?php
namespace hisorange\PosixRPC\Contract;

interface IFluent
{
    /**
     * Initialize the collector.
     *
     * @param INode $node
     * @param string $branch
     */
    public function __construct(INode $node, string $branch);

    /**
     * Magic getter collects the called segment.
     *
     * @param string $var
     * @return self
     */
    public function __get($var);

    /**
     * Magic caller, executes the branch segments.
     *
     * @param string $var
     * @param array $params
     * @return mixed
     */
    public function __call(string $var, array $params);
}
