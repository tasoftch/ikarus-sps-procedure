<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\SPS\Procedure\Instruction\Workflow;


use Ikarus\SPS\Procedure\Context\ContextInterface;
use Ikarus\SPS\Procedure\Instruction\AbstractInstruction;
use Ikarus\SPS\Procedure\Instruction\InstructionInterface;

class IfInstruction extends AbstractInstruction implements ConditionInstructionInterface
{
    /** @var callable */
    private $conditionCallback;
    /** @var InstructionInterface|null */
    private $trueInstruction, $falseInstruction;

    /**
     * IfInstruction constructor.
     * @param callable $conditionCallback
     * @param InstructionInterface $trueInstruction
     * @param InstructionInterface|null $falseInstruction
     */
    public function __construct(callable $conditionCallback, InstructionInterface $trueInstruction, InstructionInterface $falseInstruction = NULL)
    {
        $this->conditionCallback = $conditionCallback;
        $this->falseInstruction = $falseInstruction;
        $this->trueInstruction = $trueInstruction;
        if($falseInstruction)
            $this->nextInstruction = $falseInstruction;
    }

    /**
     * @return InstructionInterface|null
     */
    public function getFalseInstruction(): ?InstructionInterface
    {
        return $this->falseInstruction;
    }

    /**
     * @param InstructionInterface|null $falseInstruction
     * @return static
     */
    public function setFalseInstruction(?InstructionInterface $falseInstruction)
    {
        $this->falseInstruction = $falseInstruction;
        return $this;
    }

    /**
     * @return InstructionInterface|null
     */
    public function getTrueInstruction(): InstructionInterface
    {
        return $this->trueInstruction;
    }

    /**
     * @param InstructionInterface $trueInstruction
     * @return static
     */
    public function setTrueInstruction(InstructionInterface $trueInstruction)
    {
        $this->trueInstruction = $trueInstruction;
        return $this;
    }

    public function getCondition()
    {
        return $this->conditionCallback;
    }


    protected function doExec(ContextInterface $context)
    {
        $c = $this->getCondition();
        if(is_callable($c)) {
            if(call_user_func($c, $context))
                $this->nextInstruction = $this->getTrueInstruction();
            else
                $this->nextInstruction = $this->getFalseInstruction();
        } elseif(is_bool($c) && $c) {
            $this->nextInstruction = $this->getTrueInstruction();
        } else
            $this->nextInstruction = $this->getFalseInstruction();
    }
}