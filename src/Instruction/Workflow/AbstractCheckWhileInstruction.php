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

abstract class AbstractCheckWhileInstruction extends AbstractInstruction
{
    /** @var InstructionInterface|null */
    private $exceptionInstruction;
    private $successInstruction;
    /** @var callable */
    private $checkCallback;

    public function __construct(callable $checkCallback, InstructionInterface $nextInstruction, InstructionInterface $exceptionInstruction = NULL)
    {
        $this->checkCallback = $checkCallback;
        $this->successInstruction = $nextInstruction;
        $this->exceptionInstruction = $exceptionInstruction;
    }

    /**
     * Returning true, will wait for the next check (next cycle), returning false will go to the exception instruction
     *
     * @param ContextInterface $context
     * @return bool
     */
    abstract protected function shouldWaitFurtherChecks(ContextInterface $context): bool;

    protected function doExec(ContextInterface $context)
    {
        if( !call_user_func($this->checkCallback, $context) ) {
            if($this->shouldWaitFurtherChecks($context))
                $context->repeatForNextLoop();
            else {
                $this->nextInstruction = $this->exceptionInstruction;
                $this->checkDidFail();
            }
        } else {
            $this->nextInstruction = $this->successInstruction;
            $this->checkDidPass();
        }
    }

    protected function checkDidPass() {}
    protected function checkDidFail() {}
}