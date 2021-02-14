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

namespace Ikarus\SPS\Procedure\Compiler\Design\Parser;


use Ikarus\SPS\Procedure\Compiler\Design\Design;
use Ikarus\SPS\Procedure\Compiler\Design\DesignInterface;
use Ikarus\SPS\Procedure\Exception\DesignParserException;

class ReteNodeEditorParser implements DesignParserInterface
{
	private $appName;
	private $appVersion;

	/**
	 * ReteNodeEditorParser constructor.
	 * @param $appName
	 * @param $appVersion
	 */
	public function __construct($appName = 'demo', $appVersion = '0.1.0')
	{
		$this->appName = $appName;
		$this->appVersion = $appVersion;
	}


	/**
	 * @inheritDoc
	 */
	public function parseDesign(string $procedureDesign): DesignInterface
	{
		$procedureDesign = json_decode( $procedureDesign, true );
		list($name, $version) = explode("@", $procedureDesign["id"]);
		if($this->appName == $name && version_compare($this->appVersion, $version) >= 0) {
			$design = new Design();
			$connections = [];
			foreach($procedureDesign["nodes"] as $node) {
				$design->addNode($nid = $node["id"], $node["name"], $node['data']);
				foreach($node["inputs"] as $name => $input) {
					foreach($input['connections'] as $connection) {
						$connections[] = function() use ($design, $nid, $name, $connection) {
							$design->connect($nid, $name, $connection['node'], $connection['output']);
						};
					}
				}
				foreach($node["outputs"] as $name => $input) {
					foreach($input['connections'] as $connection) {
						$connections[] = function() use ($design, $nid, $name, $connection) {
							$design->connect($connection['node'], $connection['input'], $nid, $name);
						};
					}
				}
			}
			array_walk($connections, function($c) {$c();} );
			return $design;
		} else {
			throw new DesignParserException("App name or version does not match");
		}
	}
}