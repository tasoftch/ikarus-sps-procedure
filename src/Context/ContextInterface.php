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


use Ikarus\SPS\CyclicEngine;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
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
     * Returns true, if the instruction should not do anything.
     * A procedure can be paused manually or while resolving an error procedure
     *
     * @return bool
     */
    public function isPaused(): bool;

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
}