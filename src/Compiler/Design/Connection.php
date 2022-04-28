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


class Connection implements ConnectionInterface
{
	/** @var string|int */
	private $inputNodeID;
	/** @var string */
	private $inputName;
	/** @var string|int */
	private $outputNodeID;
	/** @var string */
	private $outputName;

	/**
	 * Connection constructor.
	 * @param int|string $inputNodeID
	 * @param string $inputName
	 * @param int|string $outputNodeID
	 * @param string $outputName
	 */
	public function __construct($inputNodeID, string $inputName, $outputNodeID, string $outputName)
	{
		$this->inputNodeID = $inputNodeID;
		$this->inputName = $inputName;
		$this->outputNodeID = $outputNodeID;
		$this->outputName = $outputName;
	}


	/**
	 * @return int|string
	 */
	public function getInputNodeID()
	{
		return $this->inputNodeID;
	}

	/**
	 * @return string
	 */
	public function getInputName(): string
	{
		return $this->inputName;
	}

	/**
	 * @return int|string
	 */
	public function getOutputNodeID()
	{
		return $this->outputNodeID;
	}

	/**
	 * @return string
	 */
	public function getOutputName(): string
	{
		return $this->outputName;
	}
}