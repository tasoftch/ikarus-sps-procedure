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

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Procedure\Context\ContextInterface;

class EchoInstruction extends AbstractInstruction
{
    private $message;

    public function __construct(string $format, ...$arguments)
    {
        $this->message = vsprintf($format, $arguments);
        $this->message = preg_replace_callback('/&([^;]+);/i', function($ms) {
            switch (strtolower($ms[1])) {
                case 'date': return date("Y-m-d");
                case 'time': return date("G:i:s");
                case 'micro':
                    $mt = microtime(true);
                    return sprintf("%04d", ($mt - (int)$mt) * 1000);
            }
            return $ms[0];
        }, $this->message);
    }

    protected function doExec(ContextInterface $context)
    {
        echo $this->message;
    }
}