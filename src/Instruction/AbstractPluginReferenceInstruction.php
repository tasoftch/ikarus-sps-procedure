<?php

namespace Ikarus\SPS\Procedure\Instruction;


use Ikarus\SPS\Plugin\PluginInterface;
use Ikarus\SPS\Procedure\Context\ContextInterface;

abstract class AbstractPluginReferenceInstruction extends AbstractInstruction
{
    /** @var string|null */
    private $pluginID;

    /**
     * AbstractPluginReferenceInstruction constructor.
     * @param string|null $pluginID
     */
    public function __construct(string $pluginID = NULL)
    {
        $this->pluginID = $pluginID;
    }

    /**
     * @return string|null
     */
    public function getPluginID(): ?string
    {
        return $this->pluginID;
    }

    public function execute(ContextInterface $context)
    {
        if($context->isPaused()) {
            $context->repeatForNextLoop();
            return false;
        }
        $plugin = ($this->getPluginID()) ? $this->getPlugin($this->getPluginID(), $context) : NULL;
        return $this->doExec($context, $plugin);
    }



    abstract protected function doExec(ContextInterface $context, PluginInterface $plugin = NULL);

    /**
     * Searches for a registered plugin in the sps engine.
     *
     * @param string $pluginID
     * @param ContextInterface $context
     * @return PluginInterface|null
     */
    protected function getPlugin(string $pluginID, ContextInterface $context): ?PluginInterface {
        if($e = $context->getEngine()) {
            /** @var PluginInterface $plugin */
            foreach($e->getPlugins() as $plugin) {
                if($plugin->getIdentifier() == $pluginID)
                    return $plugin;
            }
        }
        return NULL;
    }
}