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

use Ikarus\SPS\Alert\AlertInterface;
use Ikarus\SPS\Alert\NoticeAlert;
use Ikarus\SPS\Alert\WarningAlert;
use Ikarus\SPS\Helper\CyclicPluginManager;
use Ikarus\SPS\Procedure\Context\ContextInterface;
use Ikarus\SPS\Procedure\Context\CyclicContext;
use Ikarus\SPS\Procedure\Instruction\ArrayInstructionsMapper;
use Ikarus\SPS\Procedure\Instruction\CallbackInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\IfInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\JumpInstruction;
use Ikarus\SPS\Procedure\Instruction\Workflow\TargetInstruction;
use Ikarus\SPS\Procedure\NamedProcedure;
use PHPUnit\Framework\TestCase;
use TASoft\Util\ValueInjector;

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
        $ctx->exec();

        $this->assertEquals("Hello 1\nHello 2\nHello 3\nHello 4\n", $this->getActualOutput());
    }

    public function testNoticeAlert() {
        $instruction = new ArrayInstructionsMapper(
            new TargetInstruction("retry"),
            new CallbackInstruction(function(ContextInterface $ctx) {
                $ctx->triggerNotice(14, "Och nöö", NULL);
            }),
            new CallbackInstruction(function() use (&$reached) {
                $reached = true;
            })
        );

        $proc = new NamedProcedure("hi", $instruction);

        $pm = new CyclicPluginManager();
        $ctx = new CyclicContext($pm);
        $vi = new ValueInjector($pm);

        $alerts = [];
        $vi->tra = function(AlertInterface $alert) use (&$alerts) {
            $alerts[] = $alert;
        };

        $ctx->executeProcedure($proc);
        $ctx->exec();

        $this->assertCount(1, $alerts);
        $alert = $alerts[0];
        $this->assertInstanceOf(NoticeAlert::class, $alert);
        $this->assertEquals(14, $alert->getCode());
        $this->assertEquals("Och nöö", $alert->getMessage());

        $this->assertTrue($reached);
    }

    public function testWarningAlert() {
        $instruction = new ArrayInstructionsMapper(
            new TargetInstruction("retry"),
            new CallbackInstruction(function(ContextInterface $ctx) {
                $ctx->triggerWarning(14, "Och nöö", NULL, NULL);
            }),
            new CallbackInstruction(function() use (&$reached) {
                $reached = true;
            })
        );

        $proc = new NamedProcedure("hi", $instruction);

        $pm = new CyclicPluginManager();
        $ctx = new CyclicContext($pm);
        $vi = new ValueInjector($pm);

        $alerts = [];
        $vi->tra = function(AlertInterface $alert) use (&$alerts) {
            $alerts[] = $alert;
        };

        $ctx->executeProcedure($proc);
        $ctx->exec();

        $this->assertCount(1, $alerts);
        $alert = $alerts[0];
        $this->assertInstanceOf(WarningAlert::class, $alert);
        $this->assertEquals(14, $alert->getCode());
        $this->assertEquals("Och nöö", $alert->getMessage());

        $this->assertTrue($reached);
    }

    public function testInterruption() {
        $interruptions = new ArrayInstructionsMapper(
            new CallbackInstruction(function() use (&$names) {
                $names[] = 'Priska';
            }),
            new CallbackInstruction(function() use (&$names) {
                $names[] = 'Bettina';
            })
        );

        $proc = new NamedProcedure("hi", new ArrayInstructionsMapper(
                new CallbackInstruction(function() use (&$names) {
                    $names[] = 'Thomas';
                }),
                new CallbackInstruction(function(ContextInterface $context) use ($interruptions) {
                    $context->interruptWithInstruction( $interruptions );
                }),
                new CallbackInstruction(function() use (&$names) {
                    $names[] = 'Thomas';
                })
            )
        );

        $pm = new CyclicPluginManager();
        $ctx = new CyclicContext($pm);

        $ctx->executeProcedure($proc);
        $ctx->exec();

        $this->assertEquals([
            "Thomas",
            "Priska",
            "Bettina",
            "Thomas"
        ], $names);
    }
}
