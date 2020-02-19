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

/**
 * Class TargetInstruction
 *
 * Identifyable instruction to jump on it
 *
 * @package Ikarus\SPS\Procedure\Instruction\Workflow
 * @see JumpInstruction
 */
class TargetInstruction extends AbstractInstruction
{
    /** @var string */
    private $targetID;

    private static $targets = [];

    /**
     * TargetInstruction constructor.
     * @param string $targetID
     * @param InstructionInterface|null $nextInstruction
     */
    public function __construct(string $targetID, InstructionInterface $nextInstruction = NULL)
    {
        $this->targetID = $targetID;
        $this->nextInstruction = $nextInstruction;
        self::$targets[ $targetID ] = $this;
    }

    /**
     * @return string
     */
    public function getTargetID(): string
    {
        return $this->targetID;
    }

    /**
     * @param $targetID
     * @return InstructionInterface|null
     * @internal
     */
    public static function getTarget($targetID): ?InstructionInterface {
        return self::$targets[$targetID] ?? NULL;
    }

    protected function doExec(ContextInterface $context)
    {
        // NOOP
    }
}