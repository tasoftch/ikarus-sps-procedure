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
use Ikarus\SPS\Procedure\Model\NodeComponentInterface;

class BinaryFileProcedureCompiler extends AbstractExternalProcedureCompiler
{
	/** @var string */
	private $filename;

	/**
	 * BinaryFileProcedureCompiler constructor.
	 * @param string $filename
	 */
	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}


	/**
	 * @inheritDoc
	 */
	public function compile(ProcedureProviderInterface $procedureProvider)
	{
		$allNodes = $this->prepareFromProvider($procedureProvider);

		foreach($this->usedProcedures as $procName => $info) {
			list($options, $nodes) = $info;
			$init = $this->findInitialNodes($nodes, $options & ProcedureProviderInterface::SIGNAL_PROCEDURE_OPTION ? true : false);
			echo $procName, "\n";
			array_walk($init, function($n) {
				echo "\t(${n['@id']}) -> {$n['@component']}\n";
			});
		}


		$content = "<?php
/**
 * Compiled procedures by Ikarus SPS at " . date("d.m.Y G:i:s") . "
 */
" . $this->stringifyClassImports();

		$content .= "\nreturn function(array \$static_props = []) {
	\$CPS = [\n";

		foreach($this->usedComponents as $cn => $component) {
			$c = $this->exportExternalCodeForComponent($component);
			$content .= sprintf("\t\t'%s' => %s,\n", $cn, $c);
		}

		$content = trim($content, "\ \t\n\r\0\x0B,") . "\n\t];\n";
		$cid = (int) ((microtime(true) - time()) * 1e6);

		$content .= "\treturn new class(\$static_props) extends AbstractRuntime {
		private \$p;
		public function __construct(\$props) {
			\$this->p = \$props;
		}
	};\n};";
		file_put_contents($this->getFilename(), $content);
	}

	/**
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->filename;
	}
}