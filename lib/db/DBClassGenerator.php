<?php

/**
 * Clase encargada de generar las clases para el 
 * manejo de las tablas que usan el grid.
 * 
 */

class DBClassGenerator extends funciones
{
	/**
	 * Objeto con los objetos principales del sistema
	 * @var Object - Objeto con los objetos principales del sistema
	 */
	private $mainObj;
	
	/**
 	 * Nombre de la clase
 	 * @var String  - Nombre de la clase 
 	 */
 	private $className;
 	
 	/**
 	 * Nombre de la clase
 	 * @var String  - Nombre de la clase 
 	 */
 	private $tableName;
 	
 	/**
 	 * Nombre de la clase encriptado
 	 * @var String  - Nombre de la clase encriptado 
 	 */
 	private $encClassName;
 	
 	/**
 	 * Permisos de la clase
 	 * @var Array  - Contiene los permisos de la clase [0]::INSERTAR, [1]::ACTUALIZAR, [2]::BORRAR 
 	 */
 	private $permissions;

	public function __construct($mainObj, $className, $tableName)	
	{		

		$this->mainObj = $mainObj;
		$this->className = $className;
		$this->tableName = $tableName;
	}
	
	
	/**
	 * 
	 * Función que verifica si existe la clase de la tabla, si si existe regresa el contenido de ese archivo, 
	 * si no la crea
	 */
	public function verifyClass()
	{		

		$result = false;
		$ruta = PWSYSLIB."db/tables";
		if(file_exists($ruta.'/'.$this->tableName.'.php'))
		{			
			$result = true;
		}		

		return $result;
		
	}
	
public function createClass()
	{
		
		$ruta = "db/tables/".$this->tableName.".php";
		$rutaAux = "db/tables/";
		
		
		$driver = $this->getSqlDriver($this->mainObj->connection);
		
		if($driver == "sqlsrv")
		{ 
			//$consulta = "sp_help $this->tableName";
			$condition = array("TABLE_NAME" =>$this->tableName, "CONSTRAINT_NAME" => 'PK%');
			$fields = array("COLUMN_NAME");
			$tabla = "INFORMATION_SCHEMA.KEY_COLUMN_USAGE";
			$operation = array("LIKE", "LIKE");
		
			$sqlResult = $this->mainObj->sql->executeQuery($this->mainObj->connection, $tabla, $fields, $condition, false, $operation);
			$tableKeyFields = array();
			
			if($sqlResult)
			{
				foreach ($sqlResult as $sqlItem)
				{
					//$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
					$tableKeyFields[$sqlItem["COLUMN_NAME"]] = 1;
				}
			}
			$condition = array("TABLE_NAME" => $this->tableName);
			$isNull = "";
			
			
			$tabla = "INFORMATION_SCHEMA.COLUMNS";
			$fields = array("COLUMN_NAME", "DATA_TYPE", "IS_NULLABLE");
			$isNull = "IS_NULLABLE";
			
			$sqlResult = $this->mainObj->sql->executeQuery($this->mainObj->connection, $tabla, $fields, $condition);
			
			$arrayData = "";
			if($sqlResult)
			{
				$arrayElement = $this->getTemplate("arrayElement");
					
				foreach ($sqlResult as $sqlItem)
				{
						
					//$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
					$arrayElementAux = $arrayElement;
					$arrayElementAux = preg_replace("/__CAMPO__/", $sqlItem["COLUMN_NAME"], $arrayElementAux);
					$key = 0;
					if(isset($tableKeyFields[$sqlItem["COLUMN_NAME"]]) && $tableKeyFields[$sqlItem["COLUMN_NAME"]] == 1)
					{
						$key = 1;
					}
			
					$arrayElementAux = preg_replace("/__KEY__/", $key, $arrayElementAux);
					$arrayElementAux = preg_replace("/__TYPE__/", $sqlItem["DATA_TYPE"], $arrayElementAux);
					$arrayElementAux = preg_replace("/__NULL__/", $sqlItem[$isNull] == "YES" ? "Y" : "N", $arrayElementAux);
			
					$arrayData .= $arrayElementAux;
				}
			}
		//	$this->getVardumpLog($arrayData);
		}
		else
		{
			$consulta = "DESC $this->tableName";
		
			$ps = $this->mainObj->sql->setSimpleQuery($this->mainObj->connection, $consulta);
			$params = null;	
			$sqlResults = $this->mainObj->sql->executeSimpleQuery($ps, $params, $consulta);
				
			$arrayData = "";			
			if($sqlResults)
			{
				$arrayElement = $this->getTemplate("arrayElement");
				
				foreach ($sqlResults as $sqlItem)
				{
						
					//$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
					$arrayElementAux = $arrayElement;
					$arrayElementAux = preg_replace("/__CAMPO__/", $sqlItem["Field"], $arrayElementAux);
					$key = 0;
					if($sqlItem["Key"] == "PRI")
					{
						$key = 1;
					}
					$arrayElementAux = preg_replace("/__KEY__/", $key, $arrayElementAux);
					$arrayElementAux = preg_replace("/__TYPE__/", $sqlItem["Type"], $arrayElementAux);
					$arrayElementAux = preg_replace("/__NULL__/", $sqlItem["Null"], $arrayElementAux);
					$arrayData .= $arrayElementAux;
				}				
			}		
		}
			
		$fileContentData = $this->getTemplate("classStructure");
		$fileContentData = preg_replace("/__ELEMENTSDATA__/", $arrayData, $fileContentData);
		$result = false;
		
		$result = file_put_contents($ruta, $fileContentData);
			
		return $result;
	}
	/*public function createClass()
	{
		
		$ruta = "src/lib/db/tables/".$this->tableName.".php";
	
		$operation = null;
		
		//Para bases de datos en oracle
		 
		    $condition = array("TABLE_NAME" =>$this->tableName, "CONSTRAINT_NAME" => 'PK%');
		    $fields = array("COLUMN_NAME");
            $tabla = "INFORMATION_SCHEMA.KEY_COLUMN_USAGE";
            $operation = array("LIKE", "LIKE");            
		//}
		

		$sqlResult = $this->mainObj->sql->executeQuery($this->mainObj->connection, $tabla, $fields, $condition, false, $operation);

		$tableKeyFields = array();

		if($sqlResult)
		{
			foreach ($sqlResult as $sqlItem)
			{
				$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
				$tableKeyFields[$sqlItem["COLUMN_NAME"]] = 1;
			}				
		}					
			

		$this->getVardumpLog($tableKeyFields);
		$condition = array("TABLE_NAME" => $this->tableName);
		$isNull = "";    
    
	
		    $tabla = "INFORMATION_SCHEMA.COLUMNS";
            $fields = array("COLUMN_NAME", "DATA_TYPE", "IS_NULLABLE");
            $isNull = "IS_NULLABLE";
		//}
    
        $sqlResult = $this->mainObj->sql->executeQuery($this->mainObj->connection, $tabla, $fields, $condition);
		
		//$this->getVardumpLog($sqlResult);
			
		$arrayData = "";			
		if($sqlResult)
		{
			$arrayElement = $this->getTemplate("arrayElement");
			
			foreach ($sqlResult as $sqlItem)
			{
					
				$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
				$arrayElementAux = $arrayElement;
				$arrayElementAux = preg_replace("/__CAMPO__/", $sqlItem["COLUMN_NAME"], $arrayElementAux);
				$key = 0;
				if(isset($tableKeyFields[$sqlItem["COLUMN_NAME"]]) && $tableKeyFields[$sqlItem["COLUMN_NAME"]] == 1)
				{
					$key = 1;
				}
				
				    $arrayElementAux = preg_replace("/__KEY__/", $key, $arrayElementAux);
				    $arrayElementAux = preg_replace("/__TYPE__/", $sqlItem["DATA_TYPE"], $arrayElementAux);
				    $arrayElementAux = preg_replace("/__NULL__/", $sqlItem[$isNull] == "YES" ? "Y" : "N", $arrayElementAux);
				//}
				$arrayData .= $arrayElementAux;
				
			}				
		}		
			
		$fileContentData = $this->getTemplate("classStructure");
		$fileContentData = preg_replace("/__ELEMENTSDATA__/", $arrayData, $fileContentData);
		$result = false;
		$result = file_put_contents($ruta, $fileContentData);
			
		return $result;
	}
	*/
	public function getClassContent()
	{
			$ruta = "db/tables";		
			include_once ($ruta.'/'.$this->tableName.'.php');
			$class = new $this->tableName();
			$data = $class->getTableElements();
			return $data;		
	}
	
	
	
	private function getTemplate($name)
	{
		$template["classStructure"] = <<< TEMP
<?php
class $this->tableName
{
	public \$tableColums = null;
	function __construct()
	{
		  
	}
		
	public function getTableElements()
	{		
		__ELEMENTSDATA__
		
		return \$tableElements;		
	}
}
?>
TEMP;

		$template["arrayElement"] = <<< TEMP
		
		\$tableElements["__CAMPO__"] = array("key" =>__KEY__, "type" =>"__TYPE__",  "null" =>"__NULL__");
TEMP;

		return $template[$name];
		
	}
	
}

?>