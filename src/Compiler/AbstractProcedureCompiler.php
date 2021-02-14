<?php


namespace Ikarus\SPS\Procedure\Compiler;


use Ikarus\SPS\Procedure\Compiler\Design\ConnectionInterface;
use Ikarus\SPS\Procedure\Compiler\Design\Parser\DesignParserInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\NodeComponent\NodeComponentProviderInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\Procedure\ProcedureProviderInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\Socket\SocketProviderInterface;
use Ikarus\SPS\Procedure\Exception\NodeComponentNotFoundException;
use Ikarus\SPS\Procedure\Exception\SocketNotFoundException;
use Ikarus\SPS\Procedure\Model\NodeComponentInterface;
use Ikarus\SPS\Procedure\Model\VolatileSocketNodeComponentInterface;

abstract class AbstractProcedureCompiler implements ProcedureCompilerInterface
{
	/** @var NodeComponentProviderInterface */
	private $nodeComponentProvider;
	/** @var SocketProviderInterface */
	private $socketProvider;
	/** @var DesignParserInterface */
	private $designParser;

	protected $usedComponents = [];
	protected $usedSockets = [];

	/**
	 * This method checks if all components and sockets exist and creates a redesign of the whole project.
	 * The redesign is returned as array containing all nodes globally indexed.
	 * The connections are resolved always from input to output (for expressions) or from output to input (for signals)
	 *
	 * @param ProcedureProviderInterface $procedureProvider
	 * @return array
	 */
	protected function prepareFromProvider(ProcedureProviderInterface $procedureProvider): array {
		$this->usedComponents = $this->usedSockets = $nodeData = [];

		$getComponent = function($component):  NodeComponentInterface {
			if(NULL === ($comp = $this->usedComponents[$component] ?? NULL)) {
				$comp = $this->getNodeComponentProvider()->getNodeComponent($component);
				if(!$comp)
					throw (new NodeComponentNotFoundException("No component found with name $component"))->setComponentName($component);
				$this->usedComponents[$component] = $comp;
			}
			return $comp;
		};

		$signalSockets = [];

		$getSocket = function($name) use (&$signalSockets) {
			if(NULL === ($sk = $this->usedSockets[$name] ?? NULL)) {
				if(!$this->getSocketProvider()->socketExists($name))
					throw (new SocketNotFoundException("Socket $name not found"))->setSocketType($name);
				$this->usedSockets[$name] = $d = [ $this->getSocketProvider()->getSocketType($name), $this->getSocketProvider()->isSignalSocket($name) ];
				if($d[1])
					$signalSockets[$name] = $d[0];
			}
		};

		$globalNodeID = 1;

		foreach($procedureProvider->yieldProcedure($name, $design, $options) as $r) {
			if(!$name) {
				trigger_error("Procedure provider does not yield a valid name", E_USER_WARNING);
				continue;
			}

			$localNodeData = [];

			$design = $this->getDesignParser()->parseDesign( $design );
			foreach($design->getNodeIDs() as $nid) {
				$nc = $design->getNodeComponent($nid);
				$comp = $getComponent($nc);

				$nd = [
					"@component" => $nc,
					"@data" => $d = (array) $design->getCustomNodeData($nid),
				];

				if($comp instanceof VolatileSocketNodeComponentInterface)
					$comp->refreshFromNodeData($d);

				foreach ($comp->getInputs() as $input) {
					list($t, $signal) = $getSocket($input->getType());
					$nd['@inputs'][$input->getName()] = [$t, $signal];
				}
				foreach ($comp->getOutputs() as $output) {
					list($t, $signal) = $getSocket($output->getType());
					$nd['@outputs'][$output->getName()] = [$t, $signal];
				}

				$localNodeData[$nid] = $nd;
			}

			/** @var ConnectionInterface $connection */
			foreach($design->getConnections() as $connection) {
				list(, $isignal) = $localNodeData[$connection->getInputNodeID()]["@inputs"][$connection->getInputName()];
				list(, $osignal) = $localNodeData[$connection->getOutputNodeID()]["@outputs"][$connection->getOutputName()];

				if($isignal || $osignal) {
					$localNodeData[$connection->getOutputNodeID()]["@connections"][] = $connection;
				} else {
					$localNodeData[$connection->getInputNodeID()]["@connections"][] = $connection;
				}
			}

			foreach($localNodeData as $node) {
				$node["@id"] = $globalNodeID;
				$nodeData[$globalNodeID++] = $node;
			}
		}
		return $nodeData;
	}


	/**
	 * @return NodeComponentProviderInterface
	 */
	public function getNodeComponentProvider(): NodeComponentProviderInterface
	{
		return $this->nodeComponentProvider;
	}

	/**
	 * @param NodeComponentProviderInterface $nodeComponentProvider
	 * @return static
	 */
	public function setNodeComponentProvider(NodeComponentProviderInterface $nodeComponentProvider)
	{
		$this->nodeComponentProvider = $nodeComponentProvider;
		return $this;
	}

	/**
	 * @return SocketProviderInterface
	 */
	public function getSocketProvider(): SocketProviderInterface
	{
		return $this->socketProvider;
	}

	/**
	 * @param SocketProviderInterface $socketProvider
	 * @return static
	 */
	public function setSocketProvider(SocketProviderInterface $socketProvider)
	{
		$this->socketProvider = $socketProvider;
		return $this;
	}

	/**
	 * @return DesignParserInterface
	 */
	public function getDesignParser(): DesignParserInterface
	{
		return $this->designParser;
	}

	/**
	 * @param DesignParserInterface $designParser
	 * @return static
	 */
	public function setDesignParser(DesignParserInterface $designParser)
	{
		$this->designParser = $designParser;
		return $this;
	}
}