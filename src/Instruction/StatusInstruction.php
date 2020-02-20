<?php

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Plugin\PluginInterface;
use Ikarus\SPS\Procedure\Context\ContextInterface;
use Ikarus\SPS\Procedure\Plugin\ControllablePluginInterface;

class StatusInstruction extends AbstractPluginReferenceInstruction
{
    private $status = 0;

    /**
     * StatusInstruction constructor.
     * @param string $pluginID
     * @param int $status
     */
    public function __construct(string $pluginID, int $status)
    {
        parent::__construct($pluginID);
        $this->status = $status;
    }

    protected function doExec(ContextInterface $context, PluginInterface $plugin = NULL)
    {
        if($plugin instanceof ControllablePluginInterface) {
            $plugin->setStatus( $this->status );
        }
    }
}