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

namespace Ikarus\SPS\Procedure\Runtime\Executable;


use Ikarus\SPS\Procedure\Exception\SocketNotFoundException;

class InputRegister extends AbstractRegister
{
	protected $inputs = [];
	protected $signals = [];

	/**
	 * InputRegister constructor.
	 * @param callable $callback
	 * @param string|null $serialized
	 */
	public function __construct(callable $callback, string $serialized = NULL)
	{
		parent::__construct();
		if($s = unserialize($serialized)) {
			foreach($s as $key => $info) {
				@ list($type, $opts, $defOrCon) = $info;
				if($opts & 1) {
					// Signal input
					$this->signals[$key] = 0;
				} else {
					$this->inputs[ $key ] = [$type, /* Connected or not */  $opts & 8 ? true : false];
					if($opts & 8) {
						list($nd, $nm) = explode(":", $defOrCon, 2);
						$this->contents[$key] = function() use ($nd, $nm, $callback) { return $callback($nd, $nm); };
					} else {
						$this->contents[$key] = $defOrCon;
					}
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return $this->hasInput($offset);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		if(!$this->hasInput($offset))
			throw (new SocketNotFoundException("Input $offset does not exist", 404))->setSocketName($offset);
		if(is_callable( $v = parent::offsetGet($offset)))
			$v = $v();
		return $v;
	}

	/**
	 * Shows all possible inputs defined for the given node
	 * @return array
	 */
	public function getAvailableInputNames(): array {
		return array_keys($this->inputs);
	}

	/**
	 * Checks, if an input exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasInput(string $name): bool {
		return array_key_exists($name, $this->inputs);
	}

	/**
	 * Returns the type of an input
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function getInputType(string $name): ?string {
		return $this->inputs[$name][0] ?? NULL;
	}

	/**
	 * Checks, if an input has a connection
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isInputConnected(string $name): bool {
		return $this->inputs[$name][1] ?? false;
	}

	/**
	 * Checks if a given signal name exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function signalExists(string $name): bool {
		return array_key_exists($name, $this->signals);
	}

	/**
	 * Checks if the given signal name was triggered
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasSignal(string $name): bool {
		return $this->signals[$name] ?: false;
	}

	/**
	 * Can not change input registers
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
	}

	/**
	 * Can not change input registers
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
	}
}