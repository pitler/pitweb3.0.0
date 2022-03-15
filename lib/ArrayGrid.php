<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
use Pitweb\DBClassGenerator as PwDbClassGenerator;
use Pitweb\Security as PwSecurity;
use Pitweb\Sql as PwSql;
use Pitweb\Date as PwDate;




class ArrayGrid 
{
	


	public $model = "";
	public $tableName = "";
	public $tableData = "";
	public $tableProp = "";

	
	
	

	

	/** 
	 * Función que nos regresa el código que se genera para pintar el grid
	 */	
	protected function getList($resultFlag = false)
    {


		$data = $this->getTemplate("mainTable");

		$tableHead = $this->getTemplate("trHead");
		$headItem = $this->getTemplate("thHead");
		$trBody = $this->getTemplate("trBody");
		$tdBody = $this->getTemplate("tdBody");
		$tdUpdate = $this->getTemplate("tdUpdate");
		$tdDelete = $this->getTemplate("tdDelete");
		$tdAcciones = $this->getTemplate("tdActions");

 		$headItems = "";
 		$body = "";

 		$fields = array();
 		$fieldsType = array();
 		$order = array();

 		//Llaves de la tabla
 		$keys = $this->getKeys();
 		//PwFunciones::getVardumpLog($keys)
 		$keyVals = array();






 		//Por cada elemento del modelo
		foreach ($this->model as $modelItem) 
		{		
				//Si no es editable, lo saltamos
				if(isset($modelItem["editable"]) && $modelItem["editable"] == false)		
				{
					continue;
				}


				//Guardamos el array con los campos a mostrar
				$fields[] = $modelItem["id"];

				$fieldsType[$modelItem["id"]] = array("type" => $modelItem["type"], "value" => isset($modelItem["arrValues"]) ? $modelItem["arrValues"] : "" );

				//Guardamos el ultimo order que encuentre
				if(isset($modelItem["order"]))
				{
					$order = array($modelItem["id"]." ".$modelItem["order"]);
				}		

				//Vamos por los encabezados
				$headItems .= $headItem;
				$headItems = preg_replace("/__DATA__/", $modelItem["label"], $headItems);
		}


		if($this->tableProp["update"] == true  || $this->tableProp["delete"] == true)
		{
			$headItems .= $headItem;
			$headItems = preg_replace("/__DATA__/", "Acciones", $headItems);

		}

		//Ponemos la info del header
		$tableHead = preg_replace("/__ITEMS__/", $headItems, $tableHead);
		$data = preg_replace("/__HEAD__/", $tableHead, $data);

		//ejecutamos el query
		$condition = null;
		$sqlResults = PwSql::executeQuery ( $this->connection,$this->tableName, $fields, $condition, $order );
		
		if($sqlResults)
		{
			$trFields = "";
			
			//Por cada resultado
			$keyVals = array();

			foreach ($sqlResults as $sqlItem) 
			{		


				$tdFields = "";
				$trBodyAux = $trBody;

				//Por cada campo				
				foreach ($fields as $fieldName) 
				{	

					$value =  $sqlItem[$fieldName];

					if(in_array($fieldName, $keys))
					{
						$keyVals[$fieldName] = $value;
					}


					if($fieldsType[$fieldName]["type"] == "select" && isset( $fieldsType[$fieldName]["value"][$sqlItem[$fieldName]]))
					{
						$value = $fieldsType[$fieldName]["value"][$sqlItem[$fieldName]];						
					}

					$tdBodyAux = $tdBody;
					$tdBodyAux = preg_replace("/__DATA__/", $value, $tdBodyAux);
					$tdFields .= $tdBodyAux;
				}


				if($this->tableProp["update"] == true || $this->tableProp["delete"] == true)
				{

					
					$tdFields .= $tdAcciones;
					$acciones = "";
		
					$kv = rawurlencode(PwSecurity::encryptVariable(1, "", json_encode($keyVals)));


					if($this->tableProp["update"] == true)
					{

						$acciones .= $tdUpdate;						
						$acciones = preg_replace("/__KEYS__/", $kv, $acciones);
						
					}

					if($this->tableProp["delete"] == true)
					{
						$acciones .= $tdDelete;
						$acciones = preg_replace("/__KEYS__/", $kv, $acciones);
					}

					$tdFields = preg_replace("/__ACCIONES__/", $acciones, $tdFields);				

				}

				//Ponemos los campos <td> en el <tr>
				$trBodyAux = preg_replace("/__ITEMS__/", $tdFields, $trBodyAux);				
				//Contactenamos los <tr>
				$trFields .= $trBodyAux;				
			}




		}

		

		//Pintamos los <tr> en el body
		$data = preg_replace("/__BODY__/", $trFields, $data);	
		if($resultFlag == true)
		{
			
			return $data;
		}

	    $result = json_encode(array("status" => "true", "content" =>$data, "message" => "", "type" => "success"));
	    
		return $result;

    }




 	protected  function doInsert()
    {
    	
    	//Parseamos los datos de la forma
		parse_str($_POST['formParams'], $formParams);

    	//Validamos que no se repitan las lllaves
    	$validateFields = json_decode(rawurldecode(PwFunciones::getPVariable("validateFields")));
    	if($validateFields && sizeof($validateFields) >=1 )
    	{
    		$conditionArr = null;
    		$fieldsArr = null;
    		foreach ($validateFields as $field) 
    		{
    			$conditionArr[$field] = $formParams[$field];
    			$fieldsArr[] = $field;
    		}

    		$result = $this->validateInsert($fieldsArr, $conditionArr);
    		if($result)
    		{
    			return $result;
    		}
    	}


		//Leemos el array y armamos el query con las llaves y los datos
		$keyFields = array();
		$datos = array();

		//Traemos los campos de a tabla
		$tableData = $this->getTableData();

		
		$cont = 0;		

		$fields = array();
		$datos = array();
		$params= array();



		foreach ($this->model as $modelItem)
		{

			//Nombre del campo
			$name = $modelItem["id"];

			//Valor del campo
			$value = PwFunciones::getVariable(isset($formParams[$name]) ? $formParams[$name] : null);


  			//Si es un check y viene vacio, ponemos el vacio
			if($modelItem["type"] == "check" && !$value)
			{
			    	
			  	$value = $modelItem["value"];
			}

			//Si es un consecutivo
			if(isset($modelItem["consecutivo"]) && $modelItem["consecutivo"] == true)
			{
				$value = PwFunciones::getConsecutivo ( $this->connection, $this->tableName, $name);	
			}
			

			//Si es una fecha, depende del motor de base de datos le damos el formato
			if($modelItem["type"] == "date")
			{
			    switch (DBASE)
			    {
			    	//Traemos la fecha en formato mySql
			    	case 1 : 
			        $value = PwFunciones::getDateFormat($value, 2);
			        break;
			        //Para oracle
			        case 2 : 
			        $value = PwFunciones::getDateFormat($value, 1);
			        break;
			        //Para mysql
			        case 3 : 
			        $value = PwFunciones::getDateFormat($value, 3);
			        break;				
			    }
			}
			    
			 $fields[] = $name;
			 $datos[] = "?";
			 $params[] = $value;

		}


		if($datos && $fields)			
		{
			$strDatos= implode(",", $datos);
			$strFields = implode(",", $fields);

			$sqlResult = PwSql::insertData($this->connection, $this->tableName, $strFields, $strDatos, $params);

		}

		$data = self::getList(true);
    	$result = json_encode(array("status" => "true", "content" =>$data, "message" => "Datos insertados con éxito", "type" => "success", "modal" => "close"));		

		return $result;
    }

	
	/**	  
	 * Función encargada de ejecutar actualizaciones en la tabla usada por el grid	 * 
	 */
	protected function doUpdate()
	{
	    
		$consulta = false;
		$content = "";
		//Verificamos si se tienen permisos para actualizar
		
		
		//Leemos el array y armamos el query con las llaves y los datos
		$keyFields = array();
		$datos = array();

		//Traemos los campos de a tabla
		$tableData = $this->getTableData();

		//Parseamos los datos de la forma
		parse_str($_POST['formParams'], $formParams);

		
		$cont = 0;		

		foreach ($this->model as $modelItem)
		{

			//Si no es editable, continuamos
			if(isset($modelItem["editable"]) && $modelItem["editable"] == true)
			{
				continue;
			}

			//Nombre del campo 
			$name = $modelItem["id"];
			$value = PwFunciones::getVariable($formParams[$name]);

			$tableItem = $tableData[$name];
			if($tableItem["key"]  == 1)
			{
				$keyFields[$name] = $value;
			}
			else
			{

				//Si es una fecha, depende del motor de base de datos le damos el formato
				if($tableItem["type"] == "date")
				{
				    switch (DBASE)
				    {
				    	//Traemos la fecha en formato mySql
				    	case 1 : 
				        $value = PwFunciones::getDateFormat($value, 2);
				        break;
				        //Para oracle
				        case 2 : 
				        $value = PwFunciones::getDateFormat($value, 1);
				        break;
				        //Para mysql
				        case 3 : 
				        $value = PwFunciones::getDateFormat($value, 3);
				        break;
				
				    }
			    }
			    
			    //Esto es para fechas DATETIME de SQL , se manda en formato YYYYmmdd 
			    if($tableItem["type"] == 'datetime')
			    {

			        //$value =  $this->mainObj->date->getSqlDate($value);
			        //$value = str_replace("-", "", $value);
 					switch (DBASE)
				    {
				    	//Para mySql
				    	case 1 : 
				        $value = PwFunciones::getDateFormat($value, 11);
				        break;
				        //Para oracle
				        case 2 : 
				        $value = PwFunciones::getDateFormat($value, 10);
				        break;
				        //Para sqlServer
				        case 3 : 
				        $value = PwFunciones::getDateFormat($value, 12);
				        break;
				
				    }

			    }

			    //Si es smalldate time			    
				if($tableItem["type"] == 'smalldatetime')
			    {
			         $value = PwFunciones::getDateFormat($value, 12);			        
			    }

			    //Si es un check y viene vacio, ponemos el vacio
			    if($modelItem["type"] == "check" && !$value)
			    {
			    	
			    	$value = $modelItem["value"];
			    }
			    $datos[$name] = $value;			   
			}
		}

		//Si el array de llaves lleva al menos 1 valor
		//Ejecutamos el update
		if($datos && sizeof($keyFields) >= 1)
		{				
			PwSql::updateData($this->connection, $this->tableName, $datos, $keyFields);
		}


		$data = self::getList(true);
    	$result = json_encode(array("status" => "true", "content" =>$data, "message" => "Datos actalizados con éxito", "type" => "success", "modal" => "close"));
		return $result;

	}	

	protected function doDelete()
	{

		$keyParams = rawurldecode(PwFunciones::getPVariable("formParams"));    
		$keyParams = json_decode( PwSecurity::decryptVariable(1,$keyParams));       
		
		if($keyParams)
		{	
			foreach ($keyParams as $key => $value) 
			{
				$keyFields[$key] = $value;
			}

			$sqlResult = PwSql::deleteData($this->connection,$this->tableName,$keyFields);		
		}

		$data = self::getList(true);
    	$result = json_encode(array("status" => "true", "content" =>$data, "message" => "Datos eliminados con éxito", "type" => "success", "modal" => "close"));
		return $result;


	}


	private function validateInsert($fields = null, $condition = null)
	{

		$result =  "";


		$order = null;
		$tabla = "SYS_MODULOS";
		
		$sqlResults = PwSql::executeQuery($this->connection, $tabla, $fields, $condition, $order);

		$type = "success";
		$message = "";


		if(sizeof($sqlResults) >= 1)
		{
			$type = "error";
			$message = "La llave a insertar ya ha sido usada";
			return  json_encode(array("status" => "true", "content" =>"", "message" => $message, "type" => $type, "modal" => "open"));
		}


		return null;

	}

	
	
	
	public function getTableData()
	{
		

		//include_once (PWSYSLIB."db/DBClassGenerator.php");
		
		//$classObject = new DBClassGenerator($this->mainObj, $this->className, $this->tableName);

		$data = PwDbClassGenerator::verifyClass($this->tableName);
		
		if(!$data)
		{
			$result = PwDbClassGenerator::createClass($this->tableName, $this->connection);		
			if($result === false)
			{				
				//error_log("No se puede generar el objeto de la tabla");
				PwFunciones::getLogErro(201);
			}	
		}
		
		$data = PwDbClassGenerator::getClassContent($this->tableName);
		
		return $data;		
	}	
}
?>