<?php

class XXX_DataBase_MySQL_Extension_PDO
{
	const CLASS_NAME = 'XXX_DataBase_MySQL_Extension_PDO';
	
	protected $connection = false;
	
	protected $hasTriedToConnect = false;	
	protected $establishedConnection = false;
	
	protected $settings = array();
	
	protected $selectedDataBase = false;
	
	protected $lastError = false;
	
	public function __construct (array $settings)
	{
		$settings['inTransaction'] = false;
		
		$this->settings = $settings;
	}
	
	public function __destruct ()
	{
		return $this->disconnect();
	}
	
	public function getSettings ()
	{
		return $this->settings;
	}
	
	public function getSettingsDataBase ()
	{
		return $this->settings['defaultDataBase'];
	}
	
	public function getSettingsServer ()
	{
		return $this->settings['server_ID'];
	}
	
	public function hasEstablishedConnection ()
	{
		return $this->establishedConnection;
	}
	
	public function establishConnection ()
	{
		$result = false;
				
		if (!$this->hasTriedToConnect)
		{
			$this->hasTriedToConnect = true;
			
			$connected = false;
			
			if (XXX_Type::isValue($this->settings['address']) && XXX_Type::isValue($this->settings['user']))
			{
				$connected = $this->connect($this->settings['address'], $this->settings['user'], $this->settings['pass']);
			}
			
			if ($connected)
			{
				$this->establishedConnection = true;
				
				$setCharacterSetAndCollation = true;
				
				if (XXX_Type::isValue($this->settings['characterSet']) && XXX_Type::isValue($this->settings['collation']))
				{			
					$setCharacterSetAndCollation = $this->setCharacterSetAndCollation($this->settings['characterSet'], $this->settings['collation']);
				}
				
				if ($setCharacterSetAndCollation)
				{
					$selectedDataBase = true;
					
					if (XXX_Type::isValue($this->settings['defaultDataBase']) && $this->settings['connectionType'] != 'administration')
					{
						$selectedDataBase = $this->selectDataBase($this->settings['defaultDataBase']);
					}
					
					if ($selectedDataBase)
					{
						$result = true;
					}
					else
					{
						$this->disconnect();
					}
				}
				else
				{
					$this->disconnect();
				}
			}
			else
			{
				$this->disconnect();
			}
		}
		
		return $result;
	}
	
	public function connect ($address = '127.0.0.1', $user = 'root', $pass = '')
	{
		$result = true;
		
		$this->disconnect();
		
		try
		{
			$dataSourceName = 'mysql:host=' . $address;
			
			if ($this->settings['persistent'])
			{
				$this->connection = new PDO($dataSourceName, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
			}
			else
			{
				$this->connection = new PDO($dataSourceName, $user, $pass);
			}
		}
		catch (PDOException $e)
		{
			$result = false;
			trigger_error('Unable to connect to address: "' . $address . '"' . XXX_OperatingSystem::$lineSeparator . 'MySQL error: "' . $this->getLastMySQLError() . '"' . XXX_OperatingSystem::$lineSeparator . 'PDO error: "' . $e->getMessage() . '"', E_USER_ERROR);
		}
		
		return $result;
	}
	
	public function disconnect ()
	{
		$result = true;
		
		if ($this->settings['inTransaction'])
		{
			$this->rollBackTransaction();	
		}
		
		if (!$this->settings['doNotDisconnect'])
		{
			if ($this->connection)
			{
				$result = $this->connection->__destruct();
			}
		}
		
		$this->establishedConnection = false;
		
		return $result;
	}
	
	public function setCharacterSetAndCollation ($characterSet = 'utf8', $collation = 'utf8_unicode_ci')
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{		
			$setCharacterSet = $this->query('SET CHARACTER SET "' . $characterSet . '"', false, 'all');
			$setNamesAndCollation = $this->query('SET NAMES "' . $characterSet . '" COLLATE "' . $collation . '"', false, 'all');
				
			if ($setCharacterSet && $setNamesAndCollation)
			{
				$result = true;
			}
			else
			{
				trigger_error('Unable to set character set to: "' . $characterSet . '" and collation to "' . $collation . '"' . XXX_OperatingSystem::$lineSeparator . 'MySQL error: "' . $this->getLastMySQLError() . '"', E_USER_ERROR);
			}
		}
		
		return $result;
	}
	
	public function selectDataBase ($dataBase, $force = false)
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			if ($dataBase == $this->selectedDataBase && !$force)
			{
				$result = true;
			}
			else
			{
				$result = $this->query('USE ' . $dataBase . ';', false, 'all');
				
				$this->selectedDataBase = $dataBase;
			}
		}
		
		return $result;
	}
	
	public function getSelectedDataBase ()
	{
		return $this->selectedDataBase;
	}
	
	public function query ($query, $responseType = false, $requiredConnectionType = 'administration', $moveResultFromMySQLMemoryToPHPMemory = true)
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			if (XXX_DataBase_MySQL_Factory::validateConnectionTypeForQuery($this->settings['connectionType'], $requiredConnectionType))
			{
				$queryStart = XXX_TimestampHelpers::getCurrentMillisecondTimestamp();
				
				if ($moveResultFromMySQLMemoryToPHPMemory)
				{
					$this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
				}
				else
				{
					$this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
				}
				
				$queryResult = $this->connection->query($query);
				
				$queryEnd = XXX_TimestampHelpers::getCurrentMillisecondTimestamp();
				
				if ($queryResult)
				{			
					$result = array();
					
					switch ($responseType)
					{
						case 'ID':
							$result['ID'] = $this->connection->lastInsertId();
							break;
						case 'record':
							$result['record'] = array();
							$result['total'] = 0;
							
							if ($queryResult[0])
							{
								$result['record'] = $queryResult[0];
								$result['total'] = 1;
							}
							
							$queryResult->free();
							break;
						case 'records':
							$result['records'] = $queryResult;
							$result['total'] = XXX_Array::getFirstLevelItemTotal($result['records']);
							
							$queryResult->free();
							break;
						case 'affected':
							$result['affected'] = $this->connection->rowCount();
							break;
					}
					
					$result['success'] = true;
					$result['queryMillisecondTime'] = $queryEnd - $queryStart;
					
					/*XXX_Profiler::addTimeToGroupTiming('mySQL', $result['queryMillisecondTime']);
					XXX_Profiler::incrementGroupCounter('mySQL', 1);*/
				}
				else
				{
					trigger_error('Unable to execute query:' . XXX_OperatingSystem::$lineSeparator . '' . $query . '' . XXX_OperatingSystem::$lineSeparator . 'MySQL error: "' . $this->getLastMySQLError() . '"', E_USER_ERROR);
				}
			}
			else
			{
				$result = false;
				trigger_error('Required connectionType "' . $requiredConnectionType . '" doesn\'t work with the current connectionType "' . $this->settings['connectionType'] . '"' . XXX_OperatingSystem::$lineSeparator . 'For query:' . XXX_OperatingSystem::$lineSeparator . '' . $query . '', E_USER_ERROR);
			}
		}
		
		return $result;
	}
	
	public function beginTransaction ()
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			if ($this->connection->beginTransaction())
			{
				$this->settings['inTransaction'] = true;
				
				$result = true;
			}
		}
		
		return $result;
	}
	
	public function isInTransaction ()
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			$result = $this->settings['inTransaction'];
		}
		
		return $result;
	}
	
	public function commitTransaction ()
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			if ($this->settings['inTransaction'])
			{
				if ($this->connection->commit())
				{
					$this->settings['inTransaction'] = false;
					
					$result = true;
				}
			}
		}
		
		return $result;
	}
	
	public function rollBackTransaction ()
	{
		$result = false;
		
		if (!$this->establishedConnection)
		{
			$this->establishConnection();
		}
		
		if ($this->establishedConnection)
		{
			if ($this->settings['inTransaction'])
			{							
				if ($this->connection->rollBack())
				{
					$this->settings['inTransaction'] = false;
					
					$result = true;
				}
			}
		}
		
		return $result;
	}
	
	public function getLastMySQLError ()
	{
		$result = $this->lastError;
		
		if ($this->connection)
		{
			$result = $this->connection->errorInfo(2);
		}
		else
		{
			$result = mysqli_connect_error();
		}
		
		if ($result !== false)
		{
			$this->lastError = $result;
		}
		
		return $result;
	}
}

?>