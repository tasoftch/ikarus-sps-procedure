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
use Ikarus\SPS\Procedure\Instruction\InstructionInterface;

class CheckWhileCyclesInstruction extends AbstractCheckWhileInstruction
{
    /** @var int */
    private $cyclesCount, $currentCycle = 0;

    public function __construct(callable $checkCallback, int $cyclesCount, InstructionInterface $nextInstruction, InstructionInterface $exceptionInstruction = NULL)
    {
        $this->cyclesCount = $cyclesCount;
        parent::__construct($checkCallback, $nextInstruction, $exceptionInstruction);
    }

    protected function shouldWaitFurtherChecks(ContextInterface $context): bool
    {
        return $this->currentCycle++ < $this->cyclesCount ? true : false;
    }

    protected function checkDidPass()
    {
        $this->currentCycle = 0;
    }

    protected function checkDidFail()
    {
        $this->currentCycle = 0;
    }
}