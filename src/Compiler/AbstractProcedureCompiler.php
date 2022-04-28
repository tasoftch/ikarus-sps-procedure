<?php


namespace Ikarus\SPS\Procedure\Compiler;


use Ikarus\SPS\Procedure\Compiler\Design\ConnectionInterface;
use Ikarus\SPS\Procedure\Compiler\Design\Parser\DesignParserInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\NodeComponent\NodeComponentProviderInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\Procedure\ProcedureProviderInterface;
use Ikarus\SPS\Procedure\Compiler\Provider\Socket\SocketProviderInterface;
use Ikarus\SPS\Procedure\Exception\NodeComponentNotFoundException;
use Ikarus\SPS\Procedure\Exception\RecursiveNodeConnectionException;
use Ikarus\SPS\Procedure\Exception\SignalComplicationException;
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
	protected $usedProcedures = [];

	/**
	 * @param $nodeComponent
	 * @param $nodeID
	 */
	protected function nodeComponentNotFound($nodeComponent, $nodeID) {
		throw (new NodeComponentNotFoundException("No component found with name $nodeComponent"))
			->setComponentName($nodeComponent)
			->setNodeID($nodeID);
	}

	protected function socketNotFound($socketName, $nodeID, $reference) {
		throw (new SocketNotFoundException("Socket $socketName not found"))
			->setSocketName($socketName)
			->setNodeID($nodeID)
			->setReference($reference);
	}

	/**
	 * This method checks if all components and sockets exist and creates a redesign of the whole project.
	 * The redesign is returned as array containing all nodes globally indexed.
	 * The connections are resolved always from input to output (for expressions) or from output to input (for signals)
	 *
	 * @param ProcedureProviderInterface $procedureProvider
	 * @return array
	 */
	protected function prepareFromProvider(ProcedureProviderInterface $procedureProvider): array {
		$this->usedProcedures = $this->usedComponents = $this->usedSockets = $nodeData = [];

		$getComponent = function($component, $nodeID):  NodeComponentInterface {
			if(NULL === ($comp = $this->usedComponents[$component] ?? NULL)) {
				$comp = $this->getNodeComponentProvider()->getNodeComponent($component);
				if(!$comp)
					$this->nodeComponentNotFound($component, $nodeID);
				$this->usedComponents[$component] = $comp;
			}
			return $comp;
		};

		$signalSockets = [];

		$getSocket = function($name, $nodeID, $ref) use (&$signalSockets) {
			if(NULL === ($sk = $this->usedSockets[$name] ?? NULL)) {
				if(!$this->getSocketProvider()->socketExists($name))
					$this->socketNotFound($name, $nodeID, $ref);
				else {
					$this->usedSockets[$name] = $d = [ $this->getSocketProvider()->getSocketType($name), $this->getSocketProvider()->isSignalSocket($name) ];
					if($d[1])
						$signalSockets[$name] = $d[0];
				}
			}
		};

		$globalNodeID = 1;

		foreach($procedureProvider->yieldProcedure($name, $design, $options) as $r) {
			if(!$name) {
				trigger_error("Procedure provider does not yield a valid name", E_USER_WARNING);
				continue;
			}

			if(!$design) {
				trigger_error("Procedure $name does not contain a design", E_USER_WARNING);
				continue;
			}

			$localNodeData = [];

			$design = $this->getDesignParser()->parseDesign( $design );
			foreach($design->getNodeIDs() as $nid) {
				$nc = $design->getNodeComponent($nid);
				$comp = $getComponent($nc, $nid);

				if($comp->getOptions() & $comp::REQUIRES_SIGNAL_OPTION) {
					if($options & ProcedureProviderInterface::SIGNAL_PROCEDURE_OPTION) {
						// OK
					} else
						throw new SignalComplicationException("Can not compile a signal requiring component inside an expressive procedure", -88);
				}

				$d = (array) $design->getCustomNodeData($nid);
				if($comp instanceof VolatileSocketNodeComponentInterface) {
					$d = $comp->refreshFromNodeData($d);
				}


				$nd = [
					"@component" => [$nc, $comp->getOptions()],
					"@data" => $d,
				];

				foreach ($comp->getInputs() as $input) {
					list(, $signal) = $getSocket($input->getType(), $nid, $input->getName());
					$nd['@inputs'][$input->getName()] = [$input->getType(), $signal];
				}
				foreach ($comp->getOutputs() as $output) {
					list(, $signal) = $getSocket($output->getType(), $nid, $output->getName());
					$nd['@outputs'][$output->getName()] = [$output->getType(), $signal];
				}

				$localNodeData[$nid] = $nd;
			}

			/** @var ConnectionInterface $connection */
			foreach($design->getConnections() as $connection) {
				list(, $isignal) = $localNodeData[$connection->getInputNodeID()]["@inputs"][$connection->getInputName()];
				list(, $osignal) = $localNodeData[$connection->getOutputNodeID()]["@outputs"][$connection->getOutputName()];

				if($isignal || $osignal) {
					$localNodeData[$connection->getOutputNodeID()]["@connections"][$connection->getOutputName()] = [
						0,
						&$localNodeData[$connection->getInputNodeID()],
						$connection->getInputName()
					];
					$localNodeData[$connection->getInputNodeID()]["@connections"][$connection->getInputName()] = [
						1,
						&$localNodeData[$connection->getOutputNodeID()],
						$connection->getOutputName()
					];
				} else {
					$localNodeData[$connection->getInputNodeID()]["@connections"][$connection->getInputName()] = [
						1,
						&$localNodeData[$connection->getOutputNodeID()],
						$connection->getOutputName()
					];
					$localNodeData[$connection->getOutputNodeID()]["@connections"][$connection->getOutputName()] = [
						0,
						&$localNodeData[$connection->getInputNodeID()],
						$connection->getInputName()
					];
				}
			}

			$this->usedProcedures[$name] = [
				$options,
				[]
			];

			foreach($localNodeData as $nid => &$node) {
				$node["@id"] = $nid;
				$nodeData[$nid] = $node;
				$this->usedProcedures[$name][1][] = $node;
			}
		}
		return $nodeData;
	}

	/**
	 * This methods figure out all nodes that have no output connection (expression mode) or no input connection (signal mode).
	 * Those nodes need to be performed first, and then follow their connections.
	 *
	 * @param array $bunchOfBundledNodes
	 * @param bool $signal
	 * @return array
	 */
	protected function findInitialNodes(array $bunchOfBundledNodes, bool $signal = false): array {
		$init = [];
		foreach($bunchOfBundledNodes as $node) {
			list(, $opts) = $node["@component"];

			if($opts & NodeComponentInterface::ACCEPTS_INITIAL_OPTION) {
				if($signal) {
					$hasConnection = 0;
					foreach($node["@connections"] as $connection) {
						list($isInput) = $connection;
						if($isInput) {
							$hasConnection = 1;
							break;
						}
					}
					if(!$hasConnection)
						$init[] = $node;
				} else {
					$hasConnection = 0;
					foreach($node["@connections"] ?? [] as $connection) {
						list($isInput) = $connection;
						if(!$isInput) {
							$hasConnection = 1;
							break;
						}
					}
					if(!$hasConnection)
						$init[] = $node;
				}
			}
		}
		return $init;
	}

	protected function recursiveTraceNodeConnections(array $node, bool $isSignal = false, array $stack = []): ?array {
		if(!isset($node["@id"]))
			return NULL;


		if(in_array($node["@id"], $stack))
			throw (new RecursiveNodeConnectionException("Node connections for #{$node['@id']} is recursive"))->setNodeID($node["@id"]);

		$stack[] = $nid = $node["@id"];
		$trace = [
			$nid => []
		];


		foreach($node["@connections"] ?? [] as $connection) {
			list($input, $nd, $nm) = $connection;
			$a=NULL;

			if($isSignal && !$input) {
				if($a = $this->recursiveTraceNodeConnections($nd, $isSignal, $stack))
					$trace[$nid]['s'][$nm] = $a;
			}

			elseif(!$isSignal && $input) {
				if($a = $this->recursiveTraceNodeConnections($nd, $isSignal, $stack))
					$trace[$nid]['e'][$nm] = $a;
			}
		}
		return $trace;
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