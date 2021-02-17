<?php


namespace Ikarus\SPS\Procedure\Exception;


class RecursiveNodeConnectionException extends ProcedureCompilationException
{
	private $nodeID;

	/**
	 * @return mixed
	 */
	public function getNodeID()
	{
		return $this->nodeID;
	}

	/**
	 * @param mixed $nodeID
	 * @return RecursiveNodeConnectionException
	 */
	public function setNodeID($nodeID)
	{
		$this->nodeID = $nodeID;
		return $this;
	}
}