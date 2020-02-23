<?php
namespace hisorange\PosixRPC;

use hisorange\PosixRPC\Contract\INode;
use hisorange\PosixRPC\Contract\IFluent;

class Fluent implements IFluent
{
    /**
     * Collect the called sections.
     *
     * @var array
     */
    protected $__collector = [];

    /**
     * Initialize the collector.
     *
     * @param INode $node
     * @param string $branch
     */
    public function __construct(INode $node, string $branch)
    {
        $this->__node = $node;
        $this->__branch = $branch;
    }

    /**
     * Magic getter collects the called segment.
     *
     * @param string $var
     * @return self
     */
    public function __get($var)
    {
        $this->__collector[] = $var;
        return $this;
    }

    /**
     * Magic caller, executes the branch segments.
     *
     * @param string $var
     * @param array $params
     * @return mixed
     */
    public function __call(string $var, array $params)
    {
        $this->__collector[] = $var;
        $branch = $this->__branch;
        $result = $this->__node->$branch(implode('.', $this->__collector), ...$params);
        $this->__collector = [];

        return $result;
    }
}
