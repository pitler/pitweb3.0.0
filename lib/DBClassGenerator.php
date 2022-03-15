<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
use Pitweb\Sql as PwSql;

/**
 * Clase encargada de generar las clases para el 
 * manejo de las tablas que usan el grid.
 * 
 */

class DBClassGenerator 
{
	
	
	
	/**
	 * 
	 * Función que verifica si existe la clase de la tabla, si si existe regresa el contenido de ese archivo, 
	 * si no la crea
	 */
	public static function verifyClass($tableName)
	{		

		$result = false;
		$ruta = "db/tables";
		if(file_exists($ruta.'/'.$tableName.'.php'))
		{			
			$result = true;
		}		

		return $result;
		
	}
	
	public static function createClass($tableName, $connection)
	{
		
		$ruta = "db/tables/".$tableName.".php";
		$rutaAux = "db/tables/";
	
    	//Vamos por el tipo de controlador
		$driver = PwSql::getSqlDriver($connection);


		
		if($driver == "sqlsrv")
		{ 
			
			$condition = array("TABLE_NAME" =>$tableName, "CONSTRAINT_NAME" => 'PK%');
			$fields = array("COLUMN_NAME");
			$tabla = "INFORMATION_SCHEMA.KEY_COLUMN_USAGE";
			$operation = array("LIKE", "LIKE");
		
			$sqlResult = PwSql::executeQuery($connection, $tabla, $fields, $condition, false, $operation);
			$tableKeyFields = array();
			
			if($sqlResult)
			{
				foreach ($sqlResult as $sqlItem)
				{					
					$tableKeyFields[$sqlItem["COLUMN_NAME"]] = 1;
				}
			}
			$condition = array("TABLE_NAME" => $tableName);
			$isNull = "";
			
			
			$tabla = "INFORMATION_SCHEMA.COLUMNS";
			$fields = array("COLUMN_NAME", "DATA_TYPE", "IS_NULLABLE");
			$isNull = "IS_NULLABLE";
			
			$sqlResult = PwSql::executeQuery($connection, $tabla, $fields, $condition);
			
			$arrayData = "";
			if($sqlResult)
			{
				$arrayElement = self::getTemplate("arrayElement");
					
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
		}
		elseif($driver == 'oci')
		{
			/*$condition = array("TABLE_NAME" => $tableName);    
	    	$tabla = "USER_TAB_COLS";
	    	$fields = array("COLUMN_NAME", "DATA_TYPE", "NULLABLE");    
	    	$sqlResult = PwSql::executeQuery($connection, $tabla, $fields, $condition);*/

	    	$consulta = "SELECT TC.COLUMN_NAME, TC.DATA_TYPE, TC.DATA_LENGTH, TC.DATA_PRECISION, TC.DATA_SCALE, TC.NULLABLE, TC.COLUMN_ID, NVL(LLAVES.CONSTRAINT_TYPE, '-') AS CONSTRAINT_TYPE
FROM USER_TAB_COLS TC LEFT OUTER JOIN (SELECT IC.TABLE_NAME, IC.COLUMN_NAME, CONS.CONSTRAINT_TYPE
                                       FROM USER_IND_COLUMNS IC, USER_CONSTRAINTS CONS
                                       WHERE CONS.TABLE_NAME = IC.TABLE_NAME
                                           AND CONS.INDEX_NAME = IC.INDEX_NAME
                                           AND CONS.CONSTRAINT_TYPE = 'P'    
                                           AND IC.TABLE_NAME = ?)LLAVES ON LLAVES.TABLE_NAME = TC.TABLE_NAME AND LLAVES.COLUMN_NAME = TC.COLUMN_NAME
where TC.TABLE_NAME = ?
ORDER BY TC.COLUMN_ID";    
			$params = array($tableName, $tableName);
			$ps = PwSql::setSimpleQuery($connection, $consulta);			
			$sqlResult = PwSql::executeSimpleQuery($ps, $params, $consulta);
			
			
			//$sqlResult = $this->sql->getPersonalData($this->connection, $consulta);
			
			$arrayData = "";			
			if($sqlResult)
			{
				$arrayElement = self::getTemplate("arrayElement");
				
				foreach ($sqlResult as $sqlItem)
				{
						
					//$sqlItem = $this->getArrayObject ( $this->mainObj->conId, $sqlItem );
					$arrayElementAux = $arrayElement;
					$arrayElementAux = preg_replace("/__CAMPO__/", $sqlItem["COLUMN_NAME"], $arrayElementAux);
					$key = 0;
					/*if(isset($tableKeyFields[$sqlItem["COLUMN_NAME"]]) && $tableKeyFields[$sqlItem["COLUMN_NAME"]] == 1)
					{
						$key = 1;
					}*/
					$arrayElementAux = preg_replace("/__KEY__/",  $sqlItem["CONSTRAINT_TYPE"] == "P" ? 1 : 0, $arrayElementAux);
					$arrayElementAux = preg_replace("/__TYPE__/", $sqlItem["DATA_TYPE"], $arrayElementAux);
					$arrayElementAux = preg_replace("/__NULL__/", $sqlItem["NULLABLE"], $arrayElementAux);
					$arrayData .= $arrayElementAux;
				}				
			}	
		}
		else
		{
			//$consulta = "DESC ? ";
			$consulta = "DESC $tableName";
		
			$ps = PwSql::setSimpleQuery($connection, $consulta);
			$params = array();	
			$sqlResults = PwSql::executeSimpleQuery($ps, $params, $consulta);
				
			$arrayData = "";			
			if($sqlResults)
			{
				$arrayElement = self::getTemplate("arrayElement");
				
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
			
		$fileContentData = self::getTemplate("classStructure");
		$fileContentData = preg_replace("/__CLASSNAME__/", $tableName, $fileContentData);
		
		$fileContentData = preg_replace("/__ELEMENTSDATA__/", $arrayData, $fileContentData);
		$result = false;
		
		$result = file_put_contents($ruta, $fileContentData);
			
		return $result;
	}

	public static function getClassContent($tableName)
	{
			$ruta = "db/tables";		
			include_once ($ruta.'/'.$tableName.'.php');
			$class = new $tableName();
			$data = $class->getTableElements();
			return $data;		
	}
	
	
	
	private static function getTemplate($name)
	{
		$template["classStructure"] = <<< TEMP
<?php
class __CLASSNAME__
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