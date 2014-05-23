<?php

class XXX_DataBase_MySQL_DataBase_Model
{
	public $project = false;
	public $abstractionLayer = false;
	public $dataBase = false;
	
	public function __construct ($project = '', $abstractionLayer = '', $dataBase = '')
	{
		if ($project != '')
		{
			$this->project = $project;
		}
		
		if ($this->project == '')
		{
			if (XXX::$deploymentInformation['project'] != '')
			{
				$this->project = XXX::$deploymentInformation['project'];
			}
		}
		
		if ($abstractionLayer != '')
		{
			$this->abstractionLayer = $abstractionLayer;
		}
		
		if ($dataBase != '')
		{
			$this->dataBase = $dataBase;
		}
		
		return XXX_DataBase_MySQL_Connections::add(false, $this->abstractionLayer, array(), false, 'local');
	}
	
	public function createDataBase ()
	{
		return XXX_DataBase_MySQL_Connections::$abstractionLayers[$this->abstractionLayer]->createDataBase(XXX_DataBase_MySQL_Connections::$dataBases[$this->dataBase]);
	}
		
	public function deleteDataBase ()
	{
		return XXX_DataBase_MySQL_Connections::$abstractionLayers[$this->abstractionLayer]->deleteDataBase(XXX_DataBase_MySQL_Connections::$dataBases[$this->dataBase]);
	}
}

?>