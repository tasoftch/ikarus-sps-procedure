<?php

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Procedure\Context\ContextInterface;

class PutCommandInstruction extends AbstractInstruction
{
    /** @var string */
    private $command;
    /** @var array|null */
    private $arguments = false;

    /**
     * PutCommandInstruction constructor.
     * @param string $command
     * @param array|null $arguments
     */
    public function __construct(string $command, array $arguments = NULL)
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    protected function doExec(ContextInterface $context)
    {
        $pm = $context->getPluginManager();
        if($pm instanceof CyclicPluginManagementInterface) {
            $pm->putCommand($this->command, $this->arguments);
        }
    }
}