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

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Procedure\Context\ContextInterface;

abstract class AbstractInstruction implements InstructionInterface
{
    /** @var InstructionInterface|null */
    protected $nextInstruction;

    public function execute(ContextInterface $context)
    {
        if($context->isPaused()) {
            $context->repeatForNextLoop();
            return false;
        }
        return $this->doExec($context);
    }

    /**
     * Executes the instructions now.
     *
     * @param ContextInterface $context
     */
    abstract protected function doExec(ContextInterface $context);

    /**
     * @return InstructionInterface|null
     */
    public function getNextInstruction(): ?InstructionInterface
    {
        return $this->nextInstruction;
    }

    /**
     * @param InstructionInterface|null $nextInstruction
     * @return static
     */
    public function setNextInstruction(?InstructionInterface $nextInstruction)
    {
        $this->nextInstruction = $nextInstruction;
        return $this;
    }
}