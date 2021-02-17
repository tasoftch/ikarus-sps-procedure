<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Ikarus\SPS\Procedure\Runtime;


interface RuntimeInterface
{
	/**
	 * Sets a trigger redy for the next update
	 *
	 * @param string $name
	 */
	public function trigger(string $name);

	/**
	 * Imports a value into the procedures.
	 * Those values are fetched from the import nodes in scenes.
	 *
	 * @param string $name
	 * @param scalar|callable $value
	 */
	public function import(string $name, $value);

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasProcedure(string $name): bool;

	/**
	 * @param string $name
	 * @param array|null $arguments
	 */
	public function callProcedure(string $name, array $arguments = NULL);

	/**
	 * Updates the procedures.
	 * Calculates all nodes against their connections and follows the passed triggers (or continues them)
	 * The passed arguments here are forwarded to the node component's executable closure after $nodeData, $inputs, $outputs ...$args
	 * @param mixed ...$args
	 */
	public function update(...$args);

	/**
	 * exports calculated values from the procedures.
	 * All values that are exported out of scenes can be fetched.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function export(string $name);

	/**
	 * Exports all received values during an update cycle.
	 *
	 * @return array
	 */
	public function exportAll(): array;

	/**
	 * Returns true, if a trigger reached the given scene export
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasTrigger(string $name): bool;

	/**
	 * Returns all triggered signals during update
	 *
	 * @return array
	 */
	public function getTriggers(): array;
}