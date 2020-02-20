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


use Ikarus\SPS\Alert\AlertRecoveryInterface;
use Ikarus\SPS\CyclicEngine;
use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
use Ikarus\SPS\Procedure\Instruction\InstructionInterface;
use Ikarus\SPS\Procedure\ProcedureInterface;

interface ContextInterface
{
    /**
     * Gets the current plugin manager
     * @return PluginManagementInterface
     */
    public function getPluginManager(): PluginManagementInterface;

    /**
     * Gets the executing procedure where the instruction belongs to.
     *
     * @return ProcedureInterface
     */
    public function getProcedure(): ProcedureInterface;

    /**
     * @return EngineInterface|null
     */
    public function getEngine(): ?EngineInterface;

    /**
     * Returns true, if the instruction should not do anything.
     * A procedure can be paused manually or while resolving an error procedure
     *
     * @return bool
     */
    public function isPaused(): bool;

    /**
     * This method returns true, if the procedure is in an interrupted state, because of an emergency case.
     *
     * @return bool
     */
    public function isInterrupted(): bool;

    /**
     * Calling this method from an instruction stops further executation of the instructions and returns to the sps control.
     */
    public function waitForNextLoop();

    /**
     * Tells the context, that this instruction should be invoked again on next cycle
     * CycleEngine only!
     * @see CyclicEngine
     */
    public function repeatForNextLoop();

    /**
     * Calling this method will pause the procedure and execute the given instruction instead until the instruction chain finishes.
     * After then it returns to the current procedure's instructions.
     *
     * The return callback gets invoked before leaving the interrupted mode.
     * Please do not stack interruptions.
     *
     * @param InstructionInterface $instruction
     * @param callable|null $returnCallback
     */
    public function interruptWithInstruction(InstructionInterface $instruction, callable $returnCallback = NULL);

    /**
     * Triggers a notice in the sps.
     *
     * @param int $code
     * @param $message
     * @param string|null $pluginID
     * @param mixed ...$arguments
     */
    public function triggerNotice(int $code, $message, string $pluginID = NULL, ...$arguments);

    /**
     * Triggers a warning in the sps.
     *
     * If an emergency instruction is defined, it will be executed before continuing the procedure
     *
     * @param int $code
     * @param $message
     * @param string|null $pluginID
     * @param InstructionInterface|null $emergencyInstruction
     * @param mixed ...$arguments
     */
    public function triggerWarning(int $code, $message, string $pluginID = NULL, InstructionInterface $emergencyInstruction = NULL, ...$arguments);

    /**
     * Triggers an error in the sps.
     *
     * The current procedure gets paused.
     * If an emergency instruction is defined, it will be executed.
     * Then this procedure gets suspended until the alert gets recovered (resume method).
     *  Calling the resume method will continue with the passed instruction until there is no next instruction or directly with the current next instruction.
     *
     * @param int $code
     * @param $message
     * @param string|null $pluginID
     * @param InstructionInterface|null $emergencyInstruction
     * @param InstructionInterface|null $continueInstruction
     * @param mixed ...$arguments
     *
     * @see AlertRecoveryInterface::resume()
     */
    public function triggerError(int $code, $message, string $pluginID = NULL, InstructionInterface $emergencyInstruction = NULL, InstructionInterface $continueInstruction = NULL, ...$arguments);
}