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

class OutputRegister extends AbstractRegister
{
	protected $signals = [];
	protected $outputs = [];

	public function __construct(string $serialized = NULL)
	{
		parent::__construct();
		if($s = unserialize($serialized)) {
			foreach($s as $key => $info) {
				@ list($type, $opts) = $info;
				if($opts & 1) {
					// Signal input
					$this->signals[$key] = 0;
				} else {
					$this->outputs[ $key ] = [$type, /* Connected or not */  $opts & 8 ? true : false];
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return $this->hasOutput($offset);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		if(!$this->hasOutput($offset))
			throw (new SocketNotFoundException("Output $offset does not exist", 404))->setSocketName($offset);
		return parent::offsetGet($offset);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		if(!$this->hasOutput($offset))
			throw (new SocketNotFoundException("Output $offset does not exist", 404))->setSocketName($offset);
		parent::offsetSet($offset, $value);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		if(!$this->hasOutput($offset))
			throw (new SocketNotFoundException("Output $offset does not exist", 404))->setSocketName($offset);
		parent::offsetSet($offset, NULL);
	}

	/**
	 * Shows all possible outputs defined for the given node
	 * @return array
	 */
	public function getAvailableOutputNames(): array {
		return array_keys($this->outputs);
	}

	/**
	 * Checks, if an output exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasOutput(string $name): bool {
		return array_key_exists($name, $this->outputs);
	}

	/**
	 * Returns the type of an output
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function getOutputType(string $name): ?string {
		return $this->outputs[$name][0] ?? NULL;
	}

	/**
	 * Checks, if an output has a connection
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isOutputConnected(string $name): bool {
		return $this->outputs[$name][1] ?? false;
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
	 * Triggers a signal that will be sent to a connected node's input socket.
	 *
	 * @param string $name
	 */
	public function triggerSignal(string $name) {
		if(!$this->signalExists($name))
			throw (new SocketNotFoundException("Signal socket $name does not exist", 404))->setSocketName($name);
		$this->signals[$name] = 1;
	}

	/**
	 * Returns all triggered signals
	 *
	 * @return array
	 */
	public function getTriggeredSignals(): array {
		return array_keys( array_filter( $this->signals ) );
	}
}