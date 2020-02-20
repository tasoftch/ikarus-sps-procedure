<?php

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Plugin\PluginInterface;
use Ikarus\SPS\Procedure\Context\ContextInterface;

class CallPluginMethodInstruction extends AbstractPluginReferenceInstruction
{
    private $methodName;
    private $arguments;

    public function __construct(string $pluginID, string $methodName, array $arguments = [])
    {
        parent::__construct($pluginID);
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    protected function doExec(ContextInterface $context, PluginInterface $plugin = NULL)
    {
        if($plugin && method_exists($plugin, $this->getMethodName())) {
            call_user_func_array([$plugin, $this->getMethodName()], $this->getArguments());
        }
    }
}