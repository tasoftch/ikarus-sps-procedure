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


use Ikarus\SPS\Procedure\ProcedureInterface;

trait ProcedurePluginTrait
{
    /** @var ProcedureInterface[] */
    protected $procedures = [];
    protected $identifier;

    /**
     * @param ProcedureInterface[] $procedures
     * @return static
     */
    public function setProcedures(array $procedures)
    {
        $this->procedures = $procedures;
        return $this;
    }

    /**
     * @return ProcedureInterface[]
     */
    public function getProcedures(): array
    {
        return $this->procedures;
    }

    /**
     * @param ProcedureInterface $object
     */
    public function addProcedure(ProcedureInterface $object) {
        $this->procedures[$object->getName()] = $object;
    }

    /**
     * @param $procedure
     */
    public function removeProcedure($procedure) {
        if($procedure instanceof ProcedureInterface) {
            if(($idx = array_search($procedure, $this->procedures)) !== false)
                unset($this->procedures[$idx]);
        } elseif(isset($this->procedures[$procedure])) {
            unset($this->procedures[$procedure]);
        }
    }

    /**
     * @param $name
     * @return ProcedureInterface|null
     */
    public function getProcedure($name): ?ProcedureInterface {
        return $this->procedures[$name instanceof ProcedureInterface ? $name->getName() : $name] ?? NULL;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}