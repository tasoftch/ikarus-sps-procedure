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


use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
use Ikarus\SPS\Procedure\Instruction\InstructionInterface;
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
    private $instruction;
    private $paused = false;

    private $repeat = false;
    private $wait = false;

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
        $this->exec();
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
        while($this->instruction) {
            $this->wait = $this->repeat = false;
            $this->instruction->execute($this);

            if($this->repeat)
                return;
            $this->instruction = $this->instruction->getNextInstruction();
            if($this->wait)
                return;
        }
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
}