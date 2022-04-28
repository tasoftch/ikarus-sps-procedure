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

namespace Ikarus\SPS\Procedure\Compiler;


use Ikarus\SPS\Procedure\Compiler\Provider\Procedure\ProcedureProviderInterface;
use Ikarus\SPS\Procedure\Exception\NoInitialProcessStartException;
use Ikarus\SPS\Procedure\Model\PreCompilableNodeComponentInterface;
use Ikarus\SPS\Procedure\Model\ValueApproximation\NotConnectedValue;
use Ikarus\SPS\Procedure\Model\ValueApproximation\UndefinedValue;
use Ikarus\SPS\Procedure\Model\ValueApproximation\ValueApproximationInterface;

class PreProcedureCompiler extends AbstractProcedureCompiler implements PreCompilerNodeComponentContextInterface
{
	/** @var array */
	private $problems = [];
	private $problemCount = 0;
	private $ignoreWeakProblems = false;

	private $allNodes = [];
	private $nodeCompilations = [];
	private $currentNodeCompilation;

	private $unhandledNodeIDs = [];


	public function addProblem($level, $code, $message, $nodeID)
	{
		$this->problemCount++;
		$this->problems[] = [
			$level, $code ? $code : $this->problemCount, $message, $nodeID
		];

		usort($this->problems, function($a,$b) {
			$c = $b[0] <=> $a[0];
			if($c==0)
				return $b[1] <=> $a[1];
			return $c;
		});
	}

	/**
	 * @param \Throwable $exception
	 */
	protected function addProblemAsException(\Throwable $exception) {
		$this->addProblem(3, $exception->getCode(), $exception->getMessage(), method_exists($exception, 'getNodeID') ? $exception->getNodeID() : 0);
	}

	public function getValueApproximation(string $inputName): ?ValueApproximationInterface
	{
		if(!isset($this->nodeCompilations[$this->currentNodeCompilation][1][$inputName])) {
			$sn = function($va) use ($inputName) {
				return $this->nodeCompilations[$this->currentNodeCompilation][1][$inputName] = $va;
			};

			$node = $this->allNodes[$this->currentNodeCompilation];
			$connection = $node["@connections"][$inputName] ?? NULL;
			if(!$connection)
				return $sn(new NotConnectedValue());

			list($isInput, $targetNode, $output) = $connection;
			if($isInput) {
				$tid = $targetNode["@id"];
				$va = $this->nodeCompilations[$tid][2][$output] ?? new UndefinedValue();
				return $sn($va);
			}
		}
		return $this->nodeCompilations[$this->currentNodeCompilation][1][$inputName];
	}

	public function setValueApproximation(string $outputName, ValueApproximationInterface $approximation)
	{
		$this->nodeCompilations[$this->currentNodeCompilation][2][$outputName] = $approximation;
	}


	/**
	 * @param $nodeID
	 */
	private function precompileNode($nodeID) {
		$node = $this->allNodes[$nodeID];

		$this->nodeCompilations[$nodeID] = [$nodeID, [], []];
		list($componentName, $options) = $node["@component"];
		$comp = $this->getNodeComponentProvider()->getNodeComponent( $componentName );

		if($comp instanceof PreCompilableNodeComponentInterface) {

			$comp->precompile($this, $options, $node["@data"]);
		}
	}

	private function recursiveFollowConnections($nodeID) {
		$node = $this->allNodes[$nodeID];
		foreach($node["@connections"] ?? [] as $connection) {
			list($isInput, $targetNode) = $connection;
			if($isInput) {
				$tid = $targetNode["@id"];
				if(!isset($this->nodeCompilations[$tid]))
					$this->recursiveFollowConnections($tid);
			}
		}

		if(!isset($this->nodeCompilations[$nodeID]))
			$this->precompileNode($nodeID);
	}

	/**
	 * @inheritDoc
	 */
	public function compile(ProcedureProviderInterface $procedureProvider)
	{
		try {
			$this->allNodes = $this->prepareFromProvider($procedureProvider);
			$this->handledNodeIDs = $this->unhandledNodeIDs;

			$initial = $this->findInitialNodes($this->allNodes);
			$exec = [];

			array_walk($initial, function($n) use(&$exec) {
				$t = $this->recursiveTraceNodeConnections($n);
				if($t)
					$exec[] = $n["@id"];
			});

			if(!$exec)
				throw new NoInitialProcessStartException("Procedure will never start working, because there is no node to begin");

			foreach($exec as $nid) {
				$this->recursiveFollowConnections($nid);
			}

			foreach($this->allNodes as $nid => $node) {
				if(!isset($this->nodeCompilations[$nid])) {
					$this->unhandledNodeIDs[] = $nid;
				}
			}
		} catch (\Throwable $exception) {
			$this->addProblemAsException($exception);
		} finally {
			if($this->ignoreWeakProblems() && $this->problems && $this->problems[0][0] < 3)
				$this->problems = [];
		}
	}

	/**
	 * @return array
	 */
	public function getProblems(): array
	{
		return $this->problems;
	}

	/**
	 * @return bool
	 */
	public function ignoreWeakProblems(): bool
	{
		return $this->ignoreWeakProblems;
	}

	/**
	 * @param bool $ignoreWeakProblems
	 * @return PreProcedureCompiler
	 */
	public function setIgnoreWeakProblems(bool $ignoreWeakProblems): PreProcedureCompiler
	{
		$this->ignoreWeakProblems = $ignoreWeakProblems;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getUnhandledNodeIDs(): array
	{
		return $this->unhandledNodeIDs;
	}
}