<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\SPS\Procedure\Plugin;


use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Plugin\Cyclic\AbstractCyclicPlugin;
use Ikarus\SPS\Plugin\EngineDependentPluginInterface;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Procedure\Context\CyclicContext;

class CyclicProcedurePlugin extends AbstractCyclicPlugin implements EngineDependentPluginInterface, ProcedurePluginInterface
{
    use ProcedurePluginTrait;
    private $runningProcedureContexts = [];

    /** @var EngineInterface */
    private $engine;

    public function update(CyclicPluginManagementInterface $pluginManagement)
    {
        $ID = $this->getIdentifier();

        if($pluginManagement->hasCommand("$ID.RUNPROC")) {
            $info = $pluginManagement->getCommand("$ID.RUNPROC");
            $pluginManagement->clearCommand("$ID.RUNPROC");

            $cmd = array_shift($info);
            $proc = $this->getProcedure( $cmd );
            if($proc) {
                $ctx = new CyclicContext($pluginManagement, $this->engine);
                $ctx->arguments = $info;
                $this->runningProcedureContexts[] = $ctx;
                $ctx->executeProcedure($proc);
            }
        }

        $this->runningProcedureContexts = array_filter($this->runningProcedureContexts, function(CyclicContext $ctx) {
            return $ctx->has();
        });

        foreach($this->runningProcedureContexts as $ctx) {
            $ctx->exec();
        }
    }

    /**
     * @param EngineInterface $engine
     */
    public function setEngine(?EngineInterface $engine): void
    {
        $this->engine = $engine;
    }
}