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


use Ikarus\SPS\Procedure\Runtime\Executable\ExportRegister;
use Ikarus\SPS\Procedure\Runtime\Executable\ImportRegister;

class AbstractRuntime implements RuntimeInterface
{
	protected $imports = [];
	protected $signals = [];

	protected $exports = [];
	protected $triggered = [];

	protected $autocall = [];
	protected $update=[];

	protected $FN;

	/**
	 * @inheritDoc
	 */
	public function trigger(string $name)
	{
		$this->signals[$name] = 1;
	}

	/**
	 * @inheritDoc
	 */
	public function import(string $name, $value)
	{
		$this->imports[$name] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function hasProcedure(string $name): bool {
		return isset($this->FN[$name]) && is_callable($this->FN[$name]);
	}

	/**
	 * @inheritDoc
	 */
	public function callProcedure(string $name, array $arguments = NULL) {
		$this->update[$name] = $arguments;
	}

	/**
	 * @inheritDoc
	 */
	public function update(...$args)
	{
		$this->exports = $this->triggered = [];
		$import = new ImportRegister($this->imports, $this->signals);
		$export = new ExportRegister($this->exports, $this->triggered);

		foreach($this->autocall as $procName) {
			if(is_callable( $this->FN[$procName] ))
				$this->FN[$procName]($args, $import, $export);
		}
		foreach($this->update as $procName => $arguments) {
			if(is_callable( $this->FN[$procName] )) {
				$a = $args;
				$a['i'] = $arguments;
				$this->FN[$procName]($a, $import, $export);
			}
		}

		$this->imports = $this->signals = [];
	}

	/**
	 * @inheritDoc
	 */
	public function export(string $name)
	{
		return $this->exports[$name] ?? NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function exportAll(): array
	{
		return $this->exports;
	}


	/**
	 * @inheritDoc
	 */
	public function hasTrigger(string $name): bool
	{
		return $this->triggered[$name] ?: false;
	}

	/**
	 * @inheritDoc
	 */
	public function getTriggers(): array
	{
		return $this->triggered;
	}
}