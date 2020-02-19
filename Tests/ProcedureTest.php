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

/**
 * ProcedureTest.php
 * ikarus-sps-procedure
 *
 * Created on 2020-02-18 16:47 by thomas
 */

use Ikarus\SPS\Helper\CyclicPluginManager;
use Ikarus\SPS\Procedure\Context\CyclicContext;
use Ikarus\SPS\Procedure\Instruction\ArrayInstructionsMapper;
use Ikarus\SPS\Procedure\Instruction\CallbackInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\IfInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\JumpInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\TargetInstruction;
use Ikarus\SPS\Procedure\NamedProcedure;
use PHPUnit\Framework\TestCase;

class ProcedureTest extends TestCase
{
    public function testJumpingInstructionsProcedure() {
        $proc = new NamedProcedure(
            'test',
            (new TargetInstruction('begin'))
                ->setNextInstruction((new IfInstruction(
                    function() use (&$count) { return $count++ < 4; },
                    (new ArrayInstructionsMapper(
                        new CallbackInstruction(function() { echo "Hello"; }),
                        new CallbackInstruction(function() use (&$count) { echo " $count\n"; }),
                        new JumpInstruction('begin')
                    ))
                )))
        );

        $pm = new CyclicPluginManager();
        $ctx = new CyclicContext($pm);

        $ctx->executeProcedure($proc);

        $this->assertEquals("Hello 1\nHello 2\nHello 3\nHello 4\n", $this->getActualOutput());
    }
}
