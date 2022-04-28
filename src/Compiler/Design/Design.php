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

namespace Ikarus\SPS\Procedure\Compiler\Design;


use Ikarus\SPS\Procedure\Exception\NodeDoesNotExistException;

class Design implements DesignInterface
{
	private $nodes = [];
	private $connections = [];

	/**
	 * @param $id
	 * @param string $component
	 * @param array|null $data
	 */
	public function addNode($id, string $component, array $data = NULL) {
		$this->nodes[$id] = [$component,$data];
	}

	/**
	 * @param $inputNodeID
	 * @param string $inputName
	 * @param $outputNodeID
	 * @param string $outputName
	 */
	public function connect($inputNodeID, string $inputName, $outputNodeID, string $outputName) {
		if(!isset($this->nodes[$inputNodeID]))
			throw (new NodeDoesNotExistException("Can not find input node $inputNodeID"))->setNode($inputNodeID);
		if(!isset($this->nodes[$outputNodeID]))
			throw (new NodeDoesNotExistException("Can not find output node $outputNodeID"))->setNode($outputNodeID);

		$this->connections[] = new Connection($inputNodeID, $inputName, $outputNodeID, $outputName);
	}

	/**
	 * @inheritDoc
	 */
	public function getNodeIDs(): array
	{
		return array_keys($this->nodes);
	}

	/**
	 * @inheritDoc
	 */
	public function getNodeComponent($nodeID): string
	{
		return $this->nodes[$nodeID][0];
	}

	/**
	 * @inheritDoc
	 */
	public function getCustomNodeData($nodeID): ?array
	{
		return $this->nodes[$nodeID][1];
	}

	/**
	 * @inheritDoc
	 */
	public function getConnections(): ?array
	{
		return $this->connections;
	}
}