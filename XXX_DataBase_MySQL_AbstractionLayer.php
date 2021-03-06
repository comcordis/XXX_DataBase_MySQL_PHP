<?php


class XXX_DataBase_MySQL_AbstractionLayer
{
	const CLASS_NAME = 'XXX_DataBase_MySQL_AbstractionLayer';
	
	protected $debugQueries = true;
	
	protected $connection = false;
	
	protected $queryTemplates = array();
	
	public function open ($connection)
	{
		$this->connection = $connection;
		
		return ($this->connection === false ? false : true);
	}
	
	public function close ()
	{
		$result = false;
		
		if ($this->connection !== false)
		{
			$result = $this->connection->disconnect();
			
			$this->connection = false;
		}
		
		return $result;
	}
	
	public function selectDataBase ($dataBase, $force = false)
	{
		if (!XXX_Type::isValue($dataBase) && $this->connection !== false)
		{
			$tempSettings = $this->connection->getSettings();
			
			if (XXX_Type::isValue($tempSettings['dataBase']))
			{
				$dataBase = $tempSettings['dataBase'];
			}
		}	
	
		return ($this->connection !== false) ? $this->connection->selectDataBase($dataBase, $force) : false;
	}
		
	public function getSelectedDataBase ()
	{
		return ($this->connection !== false) ? $this->connection->getSelectedDataBase() : false;
	}
	
	public function getConnectionSettingsDataBase ()
	{
		return ($this->connection !== false) ? $this->connection->getSettingsDataBase() : false;
	}
	
	public function getConnectionSettingsServer ()
	{
		return ($this->connection !== false) ? $this->connection->getSettingsServer() : false;
	}
	
	public function testConnection ()
	{
		$result = false;
		
		$queryResult = $this->executeQueryTemplate('Administration>testConnection');
		
		if ($queryResult && $queryResult['total'] > 0)
		{
			$result = true;
		}
		
		return $result;
	}
	
	public function query ($query, $responseType = false, $requiredConnectionType = 'administration', $simplifyResult = false, $moveResultFromMySQLMemoryToPHPMemory = true)
	{
		$result = false;
		
		if ($this->connection !== false)
		{
			$result = $this->connection->query($query, $responseType, $requiredConnectionType, $moveResultFromMySQLMemoryToPHPMemory);
			
			if ($result)
			{
				if ($simplifyResult)
				{
					switch ($responseType)
					{
						case 'ID':
							$result = $result['ID'];
							break;
						case 'record':
							if ($result['total'] == 1)
							{
								$result = $result['record'];
							}
							else
							{
								$result = false;
							}
							break;
						case 'records':
							if ($result['total'] > 0)
							{
								$result = $result['records'];
							}
							else
							{
								$result = false;
							}
							break;
						case 'affected':
							$result = $result['affected'];
							break;
						case 'success':
							$result = $result['success'];
							break;
					}
				}
			}
		}
		
		return $result;
	}
	
	public function beginTransaction ()
	{
		return ($this->connection !== false) ? $this->connection->beginTransaction() : false;
	}
	
	public function isInTransaction ()
	{
		return ($this->connection !== false) ? $this->connection->isInTransaction() : false;
	}
	
	public function commitTransaction ()
	{
		return ($this->connection !== false) ? $this->connection->commitTransaction() : false;
	}
	
	public function rollbackTransaction ()
	{
		return ($this->connection !== false) ? $this->connection->rollbackTransaction() : false;
	}
	
	public function executeQueryTemplate ($name, $values = array(), $simplifyResult = false, $moveResultFromMySQLMemoryToPHPMemory = true)
	{
		$result = false;
		
		if ($values === false)
		{
			$values = array();
		}
		
		if ($this->connection !== false)
		{
			$queryTemplateID = XXX_DataBase_MySQL_QueryTemplate::getIDByName($name);
						
			if ($queryTemplateID !== false)
			{
				$processedQueryTemplateInput = XXX_DataBase_MySQL_QueryTemplate::processInput($queryTemplateID, $values);
				
				if ($processedQueryTemplateInput !== false)
				{
					if (XXX_Type::isEmpty($processedQueryTemplateInput['dataBase']) || $this->selectDataBase($processedQueryTemplateInput['dataBase']))
					{						
						$result = $this->query($processedQueryTemplateInput['queryString'], $processedQueryTemplateInput['responseType'], $processedQueryTemplateInput['requiredConnectionType'], false, $moveResultFromMySQLMemoryToPHPMemory);
						
						if ($result)
						{
							if (XXX_Type::isFilledArray($processedQueryTemplateInput['responseColumnTypeCasting']))
							{
								$result = XXX_DataBase_MySQL_QueryTemplate::processResult($result, $processedQueryTemplateInput['responseColumnTypeCasting']);
							}
							
							if (XXX_PHP::$debug && $this->debugQueries)
							{
								$debugNotification = '';
								$debugNotification .= 'template: ' . $name . XXX_OperatingSystem::$lineSeparator;
								if ($processedQueryTemplateInput['dataBase'])
								{
									$debugNotification .= 'dataBase: ' . $processedQueryTemplateInput['dataBase'] . XXX_OperatingSystem::$lineSeparator;
								}
								$debugNotification .= 'query:' . XXX_OperatingSystem::$lineSeparator . $processedQueryTemplateInput['queryString'] . XXX_OperatingSystem::$lineSeparator;
								
								switch ($processedQueryTemplateInput['responseType'])
								{
									case 'ID':
										$debugNotification .= 'Returned ID: ' . $result['ID'] . XXX_OperatingSystem::$lineSeparator;
										break;
									case 'record':
										$debugNotification .= 'Returned ' . $result['total'] . ' record(s):' . XXX_OperatingSystem::$lineSeparator;
										
										$debugNotification .= print_r($result['record'], true) . XXX_OperatingSystem::$lineSeparator;
										break;
									case 'records':
										$debugNotification .= 'Returned ' . $result['total'] . ' record(s):' . XXX_OperatingSystem::$lineSeparator;
										
										$debugNotification .= print_r($result['records'], true) . XXX_OperatingSystem::$lineSeparator;
										break;
									case 'affected':
										$debugNotification .= 'Affected ' . $result['affected'] . ' record(s)' . XXX_OperatingSystem::$lineSeparator;
										break;
								}
								$debugNotification .= 'In ' . $result['queryMillisecondTime'] . 'ms';
								
								trigger_error($debugNotification);
							}
							
							if ($simplifyResult)
							{
								switch ($processedQueryTemplateInput['responseType'])
								{
									case 'ID':
										$result = $result['ID'];
										break;
									case 'record':
										if ($result['total'] == 1)
										{
											$result = $result['record'];
										}
										else
										{
											$result = false;
										}
										break;
									case 'records':
										if ($result['total'] > 0)
										{
											$result = $result['records'];
										}
										else
										{
											$result = false;
										}
										break;
									case 'affected':
										$result = $result['affected'];
										break;
									case 'success':
										$result = $result['success'];
										break;
								}
							}
						}
						else
						{
							trigger_error($processedQueryTemplateInput['queryString'] . ' - ' . $this->connection->getLastMySQLError());
						}
					}
				}
			}			
		}		
		
		return $result;
	}
	
	
	
}

?>