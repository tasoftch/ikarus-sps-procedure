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

class ArrayInstructionsMapper implements InstructionInterface
{
    /** @var InstructionInterface[] */
    private $instructions;

    public function __construct(...$instructions)
    {
        $this->addInstructions(...$instructions);
    }

    public function execute(ContextInterface $context)
    {
        // NOOP
    }

    public function getNextInstruction(): ?InstructionInterface
    {
        if($this->instructions) {
            $instruction = $this->instructions[0];
            $last = $instruction;

            for($e=1;$e<count($this->instructions);$e++) {
                $next = $this->instructions[$e];
                if($last instanceof AbstractInstruction)
                    $last->setNextInstruction($next);
                $last = $next;
            }
            return $instruction;
        }
        return NULL;
    }

    /**
     * @param InstructionInterface $instruction
     * @return static
     */
    public function addInstruction(InstructionInterface $instruction) {
        $this->instructions[] = $instruction;
        return $this;
    }

    /**
     * @param InstructionInterface $instruction
     * @return static
     */
    public function removeInstruction(InstructionInterface $instruction) {
        if(($idx = array_search($instruction, $this->instructions)) !== false)
            unset($this->instructions[$idx]);
        return $this;
    }

    /**
     * @param mixed ...$instructions
     * @return static
     */
    public function addInstructions(...$instructions) {
        foreach($instructions as $instruction)
            $this->addInstruction($instruction);
        return $this;
    }

    /**
     * @return InstructionInterface[]
     */
    public function getInstructions(): array
    {
        return $this->instructions;
    }
}