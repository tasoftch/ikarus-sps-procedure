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

namespace Ikarus\SPS\Procedure\Compiler\Provider\Procedure;


use Generator;

interface ProcedureProviderInterface
{
	/** @var int Marks a procedure as sub procedure that can be called by other procedures but not from outside */
	const SUB_PROCEDURE_OPTION = 1<<0;
	/** @var int Automatically calls the procedure on update */
	const AUTOCALL_PROCEDURE_OPTION = 1<<1;
	/** @var int Automatically calls the procedure once at runtime startup. */
	const AUTOCALL_ONCE_PROCEDURE_OPTION = 1<<2;

	/** @var int Marks a procedure to be handled as signal triggering procedure */
	const SIGNAL_PROCEDURE_OPTION = 1<<8;

	/**
	 * Should iterate over all procedures to be compiled in the runtime object and yield their names, json and options
	 *
	 * @param $name
	 * @param $jsonDesign
	 * @param $options
	 * @return Generator
	 */
	public function yieldProcedure(&$name, &$jsonDesign, &$options);
}