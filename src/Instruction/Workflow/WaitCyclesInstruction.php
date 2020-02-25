<?php

namespace Ikarus\SPS\Procedure\Instruction\Workflow;


use Ikarus\SPS\Procedure\Context\ContextInterface;
use Ikarus\SPS\Procedure\Instruction\AbstractInstruction;

class WaitCyclesInstruction extends AbstractInstruction
{
    private $counter = 0;
    /** @var int */
    private $expectedCount;

    /**
     * WaitCyclesInstruction constructor.
     * @param int $expectedCount
     */
    public function __construct(int $expectedCount)
    {
        $this->expectedCount = $expectedCount;
    }

    /**
     * @return int
     */
    public function getExpectedCount(): int
    {
        return $this->expectedCount;
    }
    

    protected function doExec(ContextInterface $context)
    {
        if($this->counter < $this->getExpectedCount()) {
            $this->counter++;
            $context->repeatForNextLoop();
        } else
            $this->counter = 0;
    }
}