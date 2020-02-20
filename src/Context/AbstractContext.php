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

namespace Ikarus\SPS\Procedure\Context;


use Ikarus\SPS\Alert\NoticeAlert;
use Ikarus\SPS\Alert\RecoveryAlert;
use Ikarus\SPS\Alert\WarningAlert;
use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Exception\SPSException;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
use Ikarus\SPS\Procedure\Instruction\InstructionInterface;
use Ikarus\SPS\Procedure\Instruction\Workflow\TargetInstruction;
use Ikarus\SPS\Procedure\ProcedureInterface;

abstract class AbstractContext implements ContextInterface
{
    /** @var PluginManagementInterface */
    private $pluginManager;
    /** @var EngineInterface|null */
    private $engine;
    /** @var ProcedureInterface */
    private $procedure;
    /** @var InstructionInterface */
    private $instruction, $interruption;
    /** @var null|callable */
    private $interruptionCB;

    private $paused = false;
    private $interrupted = false;

    /**
     * @return bool
     */
    public function isInterrupted(): bool
    {
        return $this->interrupted;
    }

    private $repeat = false;
    private $wait = false;

    public $arguments;

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * AbstractContext constructor.
     * @param PluginManagementInterface $pluginManager
     * @param EngineInterface|null $engine
     */
    public function __construct(PluginManagementInterface $pluginManager, EngineInterface $engine = NULL)
    {
        $this->pluginManager = $pluginManager;
        $this->engine = $engine;
    }


    /**
     * @return PluginManagementInterface
     */
    public function getPluginManager(): PluginManagementInterface
    {
        return $this->pluginManager;
    }

    public function executeProcedure(ProcedureInterface $procedure)
    {
        $this->procedure = $procedure;
        $this->instruction = $procedure->getInitialInstruction();
    }

    /**
     * @return ProcedureInterface
     */
    public function getProcedure(): ProcedureInterface
    {
        return $this->procedure;
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    public function repeatForNextLoop()
    {
        $this->repeat = true;
    }

    public function waitForNextLoop()
    {
        $this->wait = true;
    }


    public function exec() {
        restart:
        while($this->instruction) {
            $this->wait = $this->repeat = false;
            $this->instruction->execute($this);

            if($this->repeat)
                return;
            $this->instruction = $this->instruction->getNextInstruction();
            if($this->wait)
                return;
        }

        if($this->interruption) {
            $this->interrupted = false;
            $this->instruction = $this->interruption;
            $this->interruption = NULL;
            if($this->interruptionCB)
                ($this->interruptionCB)();
            $this->interruptionCB = NULL;
            goto restart;
        }
    }

    public function interruptWithInstruction(InstructionInterface $instruction, callable $returnCallback = NULL)
    {
        if($this->interrupted) {
            throw new SPSException("Can not interrupt an interrupted procedure", -154);
        }

        $this->interrupted = true;
        $this->interruption = $this->repeat ? $this->instruction : $this->instruction->getNextInstruction();

        // Because of next instruction call, use a placeholder
        $this->instruction = new TargetInstruction("", $instruction);
        $this->interruptionCB = $returnCallback;
    }

    public function has(): bool {
        return $this->instruction ? true : false;
    }

    /**
     * @return EngineInterface|null
     */
    public function getEngine(): ?EngineInterface
    {
        return $this->engine;
    }

    public function triggerNotice(int $code, $message, string $pluginID = NULL, ...$arguments)
    {
        $alert = new NoticeAlert($code, $message, $pluginID, ...$arguments);
        $this->getPluginManager()->triggerAlert($alert);
    }

    public function triggerWarning(int $code, $message, string $pluginID = NULL, InstructionInterface $emergencyInstruction = NULL, ...$arguments)
    {
        $alert = new WarningAlert($code, $message, $pluginID, ...$arguments);
        $this->getPluginManager()->triggerAlert($alert);

        if($emergencyInstruction)
            $this->interruptWithInstruction($emergencyInstruction);
    }

    public function triggerError(int $code, $message, string $pluginID = NULL, InstructionInterface $emergencyInstruction = NULL, InstructionInterface $continueInstruction = NULL, ...$arguments)
    {
        $alert = new RecoveryAlert($code, $message, $pluginID, ...$arguments);
        $alert->setCallback(function() use ($continueInstruction) {
            $this->paused = false;
            $this->instruction = $continueInstruction ?: $this->instruction->getNextInstruction();
        });

        $this->getPluginManager()->triggerAlert($alert);

        if($emergencyInstruction)
            $this->interruptWithInstruction($emergencyInstruction, function() {
                $this->paused = true;
                $this->wait = true;     // leave the exec loop
            });
    }
}