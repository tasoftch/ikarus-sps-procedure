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
		$content = "<?php
/**
 * Compiled procedures by Ikarus SPS at " . date("d.m.Y G:i:s") . "
 */
" . $this->stringifyClassImports();

		$content .= "\nreturn function(array \$static_props = []) {
	return new class(\$static_props) extends AbstractRuntime {
		private \$p;
		
		public function getProperty(string \$name) {
			return \$this->p[\$name] ?? NULL;
		}
		
		public function getProperties() {
			return \$this->p;
		}
		
		public function __construct(\$props) {
			\$this->p = \$props;
			\$CPS = [\n";

		foreach($this->usedComponents as $cn => $component) {
			$c = $this->exportExternalCodeForComponent($component);
			$content .= sprintf("\t\t'%s' => %s,\n", $cn, $c);
		}

		$content = trim($content, "\ \t\n\r\0\x0B,") . "\n\t];\n";

		$content .= "
	\$OUTP = [];
	\$this->OUTPC = &\$OUTP;
	\$outp_fn = function(\$upd, \$node, \$socket) use (&\$OUTP, &\$ND) {
		if(!isset(\$OUTP[\$node]) || !\$OUTP[\$node])
			\$ND[\$node](\$upd);
		return \$OUTP[\$node][\$socket] ?? NULL;
	};
	\$NDC = [];
	\$this->NDC = &\$NDC;
	\$ND = [\n";

		$array_export = function($array) {
			return var_export(serialize($array), true);
		};

		foreach($allNodes as $node) {
			$nid = $node['@id'];
			list($comp, $options) = $node["@component"];

			$content .= sprintf("\t%d => function(\$upd) use (&\$NDC, &\$OUTP, \$outp_fn, &\$CPS) {\n", $nid);

			$av_ips = array_map(function($v) {$v[1]*=1;return$v;}, $node["@inputs"] ?? []);
			$av_ops = array_map(function($v) {$v[1]*=1;return$v;}, $node["@outputs"] ?? []);

			$nodeData = array_filter($node["@data"], function($v, $k) use (&$av_ips, &$av_ops) {
				if(isset($av_ips[$k])) {
					$av_ips[$k][2] = $v;
					return false;
				}
				if(isset($av_ops[$k])) {
					$av_ops[$k][2] = $v;
					return false;
				}
				return true;
			}, ARRAY_FILTER_USE_BOTH);

			if($nodeData)
				$content .= sprintf("\t\tif(!isset(\$NDC[$nid]))\$NDC[$nid] = new NodeData(\$upd, %s);\n",
					$array_export($nodeData)
				);
			else
				$content .= "\t\tif(!isset(\$NDC[$nid]))\$NDC[$nid] = new NodeData(\$upd);\n";


			foreach($node["@connections"] as $s_name => $connection) {
				list($input, $n, $nm) = $connection;
				if($input) {
					@$av_ips[$s_name][1] |= 8;
					@$av_ips[$s_name][2] = "{$n['@id']}:$nm";
				}
				else {
					@$av_ops[$s_name][1] |= 8;
				}
			}

			if($av_ips)
				$content .= sprintf("\t\t\$ips = new InputRegister(function(\$n,\$s) use (\$upd, \$outp_fn) {return \$outp_fn(\$upd, \$n, \$s);}, %s);\n", $array_export($av_ips));
			else
				$content .= "\t\t\$ips = new InputRegister(function(){});\n";

			if($av_ops)
				$content .= sprintf("\t\t\$ops = new OutputRegister(%s);\n",
					$array_export($av_ops)
				);
			else
				$content .= "\t\t\$ops = new OutputRegister();\n";


			$content .= "\t\t\$OUTP[$nid] = \$ops;\n";
			$content .= sprintf("\t\t\$CPS['%s'](\$NDC[$nid], \$ips, \$ops, ...\$upd);\n", $comp);
			$content .= "\t},\n";
		}

		$content = trim($content, "\ \t\n\r\0\x0B,") . "\n\t\t\t];\n";
		$content .= "
			\$FN = [\n";
		$autoupdated = [];
		foreach($this->usedProcedures as $procName => $info) {
			list($options, $nodes) = $info;
			if($options & ProcedureProviderInterface::AUTOCALL_PROCEDURE_OPTION)
				$autoupdated[] = var_export($procName, true);

			$isSignal = $options & ProcedureProviderInterface::SIGNAL_PROCEDURE_OPTION ? true : false;

			$init = $this->findInitialNodes($nodes, $isSignal);
			$exec = [];

			array_walk($init, function($n) use($isSignal, &$exec) {
				$t = $this->recursiveTraceNodeConnections($n, $isSignal);
				if($t)
					$exec[] = $n["@id"];
			});

			if($exec) {
				$content .= sprintf("\t'%s' => function(\$upd_args) use (&\$ND) {\n", $procName);

				foreach($exec as $nid) {
					$content .= "\t\t\$ND[$nid](\$upd_args);\n";
				}

				$content .= "\t},\n";
			}
		}
		$content = trim($content, "\ \t\n\r\0\x0B,") . "\n\t\t\t];\n";
		$content .= "\t\t\t\$this->FN = \$FN;
			\$this->autocall = [". implode(",", $autoupdated) ."];
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