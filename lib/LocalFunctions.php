<?php
namespace Pitweb;
use Pitweb\Sql as PwSql;

/**
 * Clase encargada de ejecutar funciones que se van a usar solo para el sistema
 * Si se usa para otro sistema deberíamos hacer una clase parecida
 * Esto para no interferir con las funciones nativas del Framework
 * @author pitler
 *
 */
class LocalFunctions 
{
    
    /**
     * Nombre de la clase
     * @var String  - Nombre de la clase 
     */
    //private $className;
    

   
    
    
    /** 	  
     * Función que nos regresa el número máximo  de un campo + 1 para talbas del sistema
     * Sirve para llevar control de consecutivos
     * El campo al que haga referencia debe de ser un campo numérico
     * @param Object 	$mainObj	Objeto principal del sistema	
     * @param String 	$tabla		Tabla a la que se hace la consulta
     * @param String 	$field		Campo a revisar
     * @param Integer $inicio		Número del cual empezaría el consecutivo si no regresa nada la consulta (No existe el campo)
     */
    public function getConsecutivo($connection, $tabla, $field, $inicio = 0)
    {
        //Por default es 1
        $consecutivo=$inicio;

        $consulta = "SELECT MAX($field) AS $field FROM $tabla ";
        
        $params = null;
        $ps = PwSql::setSimpleQuery ( $connection, $consulta );
        
        $sqlResults = Pw::executeSimpleQuery ( $ps, $params, $consulta );
        if ($sqlResults)
        {
            $sqlResults = $sqlResults [0];            
            if ($sqlResults [$field] >= 0)
            {
                $consecutivo = $sqlResults [$field] + 1;
            }
        }
        //Si no trae nada la consulta, se usa el número enviado por default siempre y cuando sea mayor a 1
        else
        {
            if ($inicio > 0)
            {
                $consecutivo = $inicio;
            }
        
        }
        return $consecutivo;
    }
    
 

////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////
    /** 	  
     * Función que nos regresa el número máximo  de un campo + 1 para cualquier tabla
     * Sirve para llevar control de consecutivos
     * El campo al que haga referencia debe de ser un campo numérico
     * @param Object 	$mainObj	Objeto principal del sistema	
     * @param String 	$tabla		Tabla a la que se hace la consulta
     * @param String 	$field		Campo a revisar
     * @param Integer $inicio		Número del cual empezaría el consecutivo si no regresa nada la consulta (No existe el campo)
     */
    public function getAnyConsecutivo($mainObj, $tabla, $field, $inicio = 0)
    {
        //Por default es 1
        $consecutivo = 1;
        
        $consulta = "SELECT MAX($field) AS $field FROM $tabla";
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $params = null;
        $sqlResults = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
        
        if ($sqlResults)
        {
            $sqlResults = $sqlResults [0];
            $sqlResults = $this->getArrayObject ( $mainObj->conId, $sqlResults );
            // $sqlResults = $sqlResults[0];           

            if ($sqlResults [$field])
            {
                $consecutivo = $sqlResults [$field] + 1;
            }
        }
        //Si no trae nada la consulta, se usa el número enviado por default siempre y cuando sea mayor a 1
        else
        {
            if ($inicio > 0)
            {
                $consecutivo = $inicio;
            }
        
        }
        return $consecutivo;
    }
    
/**
	 * Trae un registro de una tabla  con el valor $key=> $value
	 * @param String $key		Valor llave de la tabla
	 * @param String $value	Campo con el texto a regresar
	 */
	public function getIdValue($mainObj,$table, $idField, $idValue, $field)
	{
		
		$fields = array($field);
		$condition = array($idField => $idValue);
		
		$sqlResults = $mainObj->sql->executeQuery($mainObj->connection, $table, $fields,  $condition);
		
		if($sqlResults)
		{
			$sqlItem = $sqlResults[0];
			$value = $sqlItem[$field];
			return $value;
		}
		else
		{
			$this->setError(42,__FUNCTION__, __CLASS__);
		}
		
		return false;
	}
    
    /**
     * Genera un array que se usa para pintar los selects dentro del modelo
     * @param Object	$mainObj		Objeto principal
     * @param String	$table			Tabla donde vamos a buscar la información
     * @param Array		$fields			Campos que quiero regresar
     * @param Array		$condition		Condición para la busqueda
     * @param Array		$order			Array con los campos por los que queremos ordenar	 
     * @param Array		$operation		Operación para la condición n, si no se encuentra en su posición asume poner un = 
     * @param Array		$operador		Operador lógico de la condición n, si no se encuentra en su posición asume poner un AND
     * @param Array 	$default 		Default para que empiece con espacio en blanco, con key = 0
     * @param Boolean 	$sameKey		Indica si la llave y el campo del select son el mismo
     * @param String	$extraField		Indica un campo diferente para tener como tecto en el option
     * @param Boolean	$cache			Indica si cargamos desde cache
     * @param String 	$subString 		Limita el tamaño del texto a mostrar en el select	
     */
    public function getSelectForModel($mainObj, $table, $fields = false, $condition = false, $order = false, $operation = false, $operator = false, $default = null, $sameKey = false, $extraField = false, $cache = false, $substring = false)
    {
        
        //ejecutamos el query
        $sqlData = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition, $order, $operation, $operator );
        $data = null;
        //si trae resultados
        if ($sqlData)
        {
            //Tamaño de resultados
            $size = sizeof ( $sqlData );
            $cont = 1;
            //Asignamos la llave como el primer elemento que traemos en fields
            $key = $fields [0];
            
            //Si hay un campo extra para pintar, aqui se asigna
            if ($extraField)
            {
                $text = $extraField;
            }
            //Si no, tomamos el segundo elemento que mandamos
            else
            {
                $tamaño = sizeof ( $fields );
                if ($tamaño >= 2)
                {
                    $text = $fields [1];
                }
                else
                {
                    $text = "";
                }
            }
            //Si existe un default, ponemos ese 
            if ($default !== null)
            {
            
                $data .= "$default:;";
            }
            
            //Por cada elemento del result
            foreach ( $sqlData as $sqlItem )
            {
                $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                
                //Si esta activa la bandera de sameKey
                if ($sameKey)
                {
                    $data .= "$sqlItem[$key]:$sqlItem[$key]";
                }
                else
                {
                    //$data .= "$sqlItem[$key]:$sqlItem[$text]";
                    
                	if ($substring)
                    {
                        $aux = substr ( $sqlItem[$text], 0, $substring );
                        $data .= "$sqlItem[$key]:$aux";
                    }
                    else
                    {
                        $data .= "$sqlItem[$key]:$sqlItem[$text]";
                    }
                    
                }
                if ($cont < $size)
                {
                    $data .= ";";
                }
                $cont ++;
            }
        }
        
        /*if($cache)
		{
			$this->setCache->setCache("$table.txt", $data, "selectModel");
	  }*/
        //  }	
        return $data;
    }
    
    
    
    public function getSelectForModelQuery($mainObj, $consulta, $fields, $params = null, $default = false, $sameKey = false, $extraField = false, $cache = false)
    {
        
        $ps = $mainObj->sql->setSimpleQuery( $mainObj->connection, $consulta );
        $sqlData = $mainObj->sql->executeSimpleQuery( $ps, $params, $consulta);
        
        $data = null;
        //si trae resultados
        if ($sqlData)
        {
            //Tamaño de resultados
            $size = sizeof ( $sqlData );
            $cont = 1;
            //Asignamos la llave como el primer elemento que traemos en fields
            $key = $fields [0];
            
            //Si hay un campo extra para pintar, aqui se asigna
            if ($extraField)
            {
                $text = $extraField;
            }
            //Si no, tomamos el segundo elemento que mandamos
            else
            {
                $tamaño = sizeof ( $fields );
                if ($tamaño >= 2)
                {
                    $text = $fields [1];
                }
                else
                {
                    $text = "";
                }
            }
            //Si existe un default, ponemos ese 
            if ($default !== null)
            {
                $data .= "$default:;";
            }
            
            //Por cada elemento del result
            foreach ( $sqlData as $sqlItem )
            {
                $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                
                //Si esta activa la bandera de sameKey
                if ($sameKey)
                {
                    $data .= $sqlItem[$key].":".$sqlItem[$key];
                }
                else
                {
                    $data .= $sqlItem[$key].":".$sqlItem[$text];
                }
                if ($cont < $size)
                {
                    $data .= ";";
                }
                $cont ++;
            }
        }
        
        /*if($cache)
		{
			$this->setCache->setCache("$table.txt", $data, "selectModel");
	  }*/
        //  }	
        return $data;
    }
    
    public function getSelectForModelFieldsArray($sqlData, $fields = false,  $default = false, $sameKey = false, $extraField = false, $cache = false)
    {
        $data = "";
        if ($sqlData)
        {
            //Tamaño de resultados
            $size = sizeof ( $sqlData );
            $cont = 1;
            //Asignamos la llave como el primer elemento que traemos en fields
            $key = $fields [0];
            
            //Si hay un campo extra para pintar, aqui se asigna
            if ($extraField)
            {
                $text = $extraField;
            }
            //Si no, tomamos el segundo elemento que mandamos
            else
            {
                $tamaño = sizeof ( $fields );
                if ($tamaño >= 2)
                {
                    $text = $fields [1];
                }
                else
                {
                    $text = "";
                }
            }
            //Si existe un default, ponemos ese 
            if ($default !== null)
            {
                $data .= "$default:;";
            }
            
            //Por cada elemento del result
            foreach ( $sqlData as $sqlItem )
            {
               // $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                
                //Si esta activa la bandera de sameKey
                if ($sameKey)
                {
                    $data .= "$sqlItem[$key]:$sqlItem[$key]";
                }
                else
                {
                    $data .= "$sqlItem[$key]:$sqlItem[$text]";
                }
                if ($cont < $size)
                {
                    $data .= ";";
                }
                $cont ++;
            }
        }
        
        /*if($cache)
		{
			$this->setCache->setCache("$table.txt", $data, "selectModel");
	  }*/
        //  }	
        return $data;
    }
    
public function getSelectForModelArray($sqlData, $default = false, $sameKey = false, $extraField = false, $cache = false)
    {
        $data = "";
        if ($sqlData)
        {
            //Tamaño de resultados
            $size = sizeof ( $sqlData );
            $cont = 1;
            
            //Si hay un campo extra para pintar, aqui se asigna
            if ($extraField)
            {
                $text = $extraField;
            }
            //Si no, tomamos el segundo elemento que mandamos
            else
            {
                $text = "";
                
            }
            //Si existe un default, ponemos ese 
            if ($default !== null)
            {
                $data .= "$default:;";
            }
            
            //Por cada elemento del result
            foreach ( $sqlData as $key => $sqlItem )
            {
               // $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                
                //Si esta activa la bandera de sameKey
                if ($sameKey)
                {
                    $data .= "$key:$key";
                }
                else
                {
                    $data .= "$key:$sqlItem";
                }
                if ($cont < $size)
                {
                    $data .= ";";
                }
                $cont ++;
            }
        }
        
        /*if($cache)
		{
			$this->setCache->setCache("$table.txt", $data, "selectModel");
	  }*/
        //  }	
        return $data;
    }
    
    
    /**
     * Se encarga de generar archivos de excel con los datos del grid
     * **Toma el archivo en cache para generarlo, si no existe, lo genera
     * @param Object		$mainObj				Objeto principal
     * @param String		$fileName				Nombre del archivo 
     * @param String		$title 					TÍtulo de la hoja de excel
     * @param Array			$model					Array con el modelo del grid
     * @param String	 	$consulta				Consulta para traer los datos a pintar
     * @param Array			$fields					Array con los campos que queremos regresar en órden
     * @param Array			$params					Array con los parámetros del query
     * @param Array			$ocultos				Array con los campos que queremos ocultar
     * @param Boolean		$jQueryDownload			Bandera para saber si mandamos a descargar el archivo(true) o guardarlo en disco(false)
     * @param Array			$fieldFormat		 	Array con los campos que necesitan un formato especifico, debe de venir NOMBRE=>formato
     * @param Array         $extraDatos             Array para sobreescribir los datos que no se encuentran en la BD
     */
    public function generaExcel($mainObj, $fileName, $title, $model, $consulta, $fields, $params, $ocultos, $jQueryDownload = false, $fieldFormat = false, $extraDatos = false)
    {
        
        
        require_once PITWEB."assets/plugins/phpExcel/Classes/PHPExcel.php";
        $objPHPExcel = new PHPExcel ();
        $encabezados = $model;
        //Lista de columnas en excel
        $cols = array ("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ" );
        // Propiedades del documento
        $objPHPExcel->getProperties ()->setCreator ( "PII" )->setLastModifiedBy ( "PII" )->setTitle ( $title )->setSubject ( $title )->setDescription ()->setKeywords ()->setCategory ();
        
        $letra = 0;
        $cont = 1;
        $objPHPExcel->setActiveSheetIndex ( 0 );
        $encabezadosArr = array ();
        $colExtraOptions = array();
        //Ponemos los encabezados
        foreach ( $encabezados as $item )
        {
            
            $celda = $cols [$letra];
            $encabezado = isset ( $item ["label"] ) ? $item ["label"] : null;
            $auxEncabezado = $item ["name"];
            if (! $encabezado)
            {
                $encabezado = $item ["name"];
                
            }
            
            if (isset ( $ocultos [$item ["name"]] ))
            {
                continue;
            }      
            
            if (isset ( $encabezadosArr [$encabezado] ))
            {
                continue;
            }           
            if(isset($extraDatos[$auxEncabezado])){
                $tempArray = explode(";", $extraDatos[$auxEncabezado]);
                $resultArray = array();
                foreach($tempArray as $tempArrayAux){
                    $temp = explode(":",$tempArrayAux);
                    $resultArray[$temp[0]] = $temp[1];
                }
                $colExtraOptions[$letra] = $resultArray;
            }
           
            $encabezadosArr [$encabezado] = 1;
            $objPHPExcel->getActiveSheet ()->setCellValue ( "$celda$cont", $encabezado );
            $objPHPExcel->getActiveSheet ()->getColumnDimension ( "$celda" )->setAutoSize ( true );
            
            $letra ++;
        }
        //$this->getVardumpLog($extraOptions);
        $hoy = date ( 'd/m/Y' );
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $data = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
       //$this->getVardumpLog($data);
        if (! $data && $jQueryDownload)
        {
            $this->setError ( 97, "$title", __CLASS__ );
            return "false";
        }
        
        $itCont = 2;
        foreach ( $data as $items )
        {
            $items = $this->getArrayObject ( $mainObj->conId, $items );
            
            $objPHPExcel->setActiveSheetIndex ( 0 );
            $letra = 0;
            
            foreach ( $fields as $item )
            {   
                $dato = $items [$item];
                $celda = $cols [$letra];
                if (! $celda)
                {
                    continue;
                }
                
                $format = "";
                if(isset($fieldFormat[$item]))
                {
                    switch ($fieldFormat[$item])
                    {
                        case "String" :
                                $objPHPExcel->getActiveSheet ()->setCellValueExplicit ( "$celda$itCont", $dato,PHPExcel_Cell_DataType::TYPE_STRING);
                            break;
                        
                        /*case "Wrap" :
                            $objPHPExcel->getActiveSheet()->setCellValue("$celda$itCont", "$dato");
                            $objPHPExcel->getActiveSheet()->getStyle("$celda$itCont")->getAlignment()->setWrapText(true);                            
                            break;
                        */    
                        default :
                            break;
                    }
                }
                else
                {   
                    if(isset($colExtraOptions[$letra])){
                        //error_log("if extra");
                        $objPHPExcel->getActiveSheet ()->setCellValueExplicit ( "$celda$itCont", $colExtraOptions[$letra][$dato]);
                    }else{
                        //error_log("else extra");
                        $objPHPExcel->getActiveSheet ()->setCellValueExplicit ( "$celda$itCont", $dato);
                    }
                }
                
                $letra ++;
            }
            $itCont ++;
        }
        
        // Rename worksheet
        $objPHPExcel->getActiveSheet ()->setTitle ( $title );
        
        // Set active sheet index to th  e first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex ( 0 );
        $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
       
        //Para descargas normales, forza a bajarlo desde el navegador
        if (! $jQueryDownload)
        {
            header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
            header ( "Content-Disposition: attachment;filename=" . $fileName . ".xlsx" );
            header ( 'Cache-Control: max-age=0' );
            $objWriter->save ( 'php://output' );
            exit ();
        }
        
        //Para descarga por jQuery y Ajax, guarda el archivo en la carpeta files/temp y manda el link de 
        //descarga para que jQuery se encargue de eso en la ruta que regresa
        else
        {
            $filePath = 'files/temp/' . $fileName . '.xlsx';
            $objWriter->save ( $filePath );
            return $filePath;
        
        }
        
        return "false";
    
     // Redirect output to a client’s web browser (Excel2007)
    

    }
    
    /**
     * Función que valida que no existan llaves repetidas al hacer insert
     * @param Object	$mainObj		Objeto principal
     * @param Array		$condition		Array con las condiciones para el query en formato CAMPO=>valor
     * @param Array		$params			Array con los parámetros
     * @param String	$tabla			Nombre de la tabla en donde buscamos
     * @param String	$clase			Nombre de la clase que intenta insertar
     */
    public function validaInsert($mainObj, $condition, $tabla, $clase)
    {
        
        $result = false;
        $sqlResults = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, null, $condition );
        
        if (sizeof ( $sqlResults ) == 0)
        {
            $result = true;
        }
        //FALTA PONER LOS DATOS ENVIADOS
        else
        {
            
            $this->setError ( 52, "", $clase );
        }
        
        return $result;
    }
    
   
    
    /**
     * Genera un array que se usa para pintar los selects dentro del modelo
     * @param Object	$mainObj		Objeto principal
     * @param String	$table			Tabla donde vamos a buscar la información
     * @param Array		$keyFields		Array con los campos pares a regresar
     * @param Array		$fields			Campos que quiero regresar
     * @param Array		$condition		Condición para la busqueda
     * @param Array		$order			Array con los campos por los que queremos ordenar	 
     * @param Array		$operation		Operación para la condición n, si no se encuentra en su posición asume poner un = 
     * @param Array		$operador		Operador lógico de la condición n, si no se encuentra en su posición asume poner un AND
     * @param Array 	$default 		Default para que empiece con espacio en blanco, con key = 0
     * @param Boolean 	$sameKey		Indica si la llave y el campo del select son el mismo
     * @param String	$extraField		Indica un campo diferente para tener como tecto en el option
     * @param Boolean	$cache			Indica si cargamos desde cache
     */
    public function getSelectFilter($mainObj, $table, $keyFields, $fields = false, $condition = false, $order = false, $operation = false, $operator = false, $default = false, $sameKey = false, $extraField = false, $cache = false)
    {
        //ejecutamos el query
        $sqlData = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition, $order, $operation, $operator );
        $data = null;
        //si trae resultados
        if ($sqlData)
        {
            //Tamaño de resultados
            $size = sizeof ( $sqlData );
            
            //Asignamos la llave como el primer elemento que traemos en fields			
            $key = $keyFields [0];
            
            //Si hay un campo extra para pintar, aqui se asigna
            if ($extraField)
            {
                $text = $extraField;
            }
            
            //Si no, tomamos el segundo elemento que mandamos
            else
            {
                $tamaño = sizeof ( $keyFields );
                if ($tamaño >= 2)
                {
                    $text = $keyFields [1];
                }
                else
                {
                    $text = "";
                }
            }
            //Si existe un default, ponemos ese 
            if ($default !== null)
            {
                $data [$default] = "";
            }
            
            //Por cada elemento del result
            foreach ( $sqlData as $sqlItem )
            {
                $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                
                //Si esta activa la bandera de sameKey
                if ($sameKey)
                {
                    $data [$sqlItem [$key]] = $sqlItem [$key];
                }
                else
                {
                    $data [$sqlItem [$key]] = $sqlItem [$text];
                }
            }
        }
        return $data;
    }
    
  
    
    /**
     * Trae el nombre del módulo en donde estamos
     * @param	Object $mainObj	Objeto principal del sistema
     * @param	String $clase		Nombre de la clase a buscar	 
     */
    public function getModuleName($mainObj, $clase)
    {
        $result = "";
        $fields = array ("DESC_CLASE" );
        $table = "SYS_MODULOS";
        $condition = array ("CLASE" => $clase );
        $sqlResults = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition );

        if ($sqlResults)
        {
            $sqlItem = $sqlResults [0];
            $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
            
            $result = $sqlItem ["DESC_CLASE"];
        }
        else
        {
            $this->setError ( 42, __FUNCTION__, __CLASS__ );
        }
        
        return $result;
    }
    
    /**
     *Función que valida que la fecha del archivo sea t-1 respecto a la fecha del archivo
     *@param	String	$fechaArchivo	Fecha que viene del archivo en formato de string
     *@param	Date		$fecha				Fecha de la consulta
     *** quitar
     */
    public function validaFechaAnterior($mainObj, $fechaArchivo, $fecha)
    {
        $result = false;
        //Convierto a Date la fecha del archivo
        $fechaArchivo = date ( "Ymd", strtotime ( $fechaArchivo ) );
        
        $fechaAux = $mainObj->date->restaDias ( $fecha, 1 );
        $esDiaHabil = $mainObj->date->esDiaHabil ( $mainObj, $fechaAux );
        while ( ! $esDiaHabil )
        {
            $fechaAux = $mainObj->date->restaDias ( $fechaAux, 1 );
            $esDiaHabil = $mainObj->date->esDiaHabil ( $mainObj, $fechaAux );
        }
        
        //convertimos al mismo formato para comparar las fechas
        $fechaAux = date ( "Ymd", strtotime ( $fechaAux ) );
        if ($fechaAux === $fechaArchivo)
        {
            $result = true;
        }
        return $result;
    }
    
   
    
    /**
     * Trae las llaves de los perfiles para el filtro en el grid
     * @param Objeto 		$mainObj				Objeto principal del sistema
     */
    public function getPerfiles($mainObj)
    {
        
        $result = array ();
        $consulta = " SELECT CVE_PERFIL, DESC_PERFIL
	    FROM SYS_PERFILES
	    ORDER BY DESC_PERFIL ";
        
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $params = null;
        $sqlData = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
        if ($sqlData)
        {
            foreach ( $sqlData as $sqlItem )
            {
                $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                $result [$sqlItem ["CVE_PERFIL"]] = $sqlItem ["DESC_PERFIL"];
            }
        }
        
        return $result;
    }
    
    /**
     * Trae las llaves de los perfiles para el filtro en el grid
     * @param Objeto 		$mainObj				Objeto principal del sistema
     */
    public function getClientes($mainObj)
    {
    
    	$result = array ();
    	$consulta = " SELECT CVE_OPERADORA, PIZARRA_OPERADORA
	    FROM CLIENTES
	    ORDER BY PIZARRA_OPERADORA ";
    
    	$ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
    	$params = null;
    	$sqlData = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
    	if ($sqlData)
    	{
    		foreach ( $sqlData as $sqlItem )
    		{
    			$sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
    			$result [$sqlItem ["CVE_OPERADORA"]] = $sqlItem ["PIZARRA_OPERADORA"];
    		}
    	}
    
    	return $result;
    }
    
/**
     * Trae las llaves de los perfiles para el filtro en el grid
     * @param Objeto 		$mainObj				Objeto principal del sistema
     */
    public function getTipoUsuario($mainObj)
    {
        
        $result = array ();
        $consulta = " SELECT ID, NOMBRE
	    FROM SITE_TIPO_USUARIO
	    ORDER BY NOMBRE ";
        
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $params = null;
        $sqlData = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
        if ($sqlData)
        {
            foreach ( $sqlData as $sqlItem )
            {
                $result [$sqlItem ["ID"]] = $sqlItem ["NOMBRE"];
            }
        }
        
        return $result;
    }
    
    /**
     * Trae las llaves de los perfiles para el filtro en el grid
     * @param Objeto 		$mainObj				Objeto principal del sistema
     */
    public function getSitePerfiles($mainObj)
    {
    
    	$result = array ();
    	$consulta = " SELECT CVE_PERFIL, DESC_PERFIL
	    FROM SITE_PERFILES
	    ORDER BY DESC_PERFIL ";
    
    	$ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
    	$params = null;
    	$sqlData = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
    	if ($sqlData)
    	{
    		foreach ( $sqlData as $sqlItem )
    		{
    			$sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
    			$result [$sqlItem ["CVE_PERFIL"]] = $sqlItem ["DESC_PERFIL"];
    		}
    	}
    
    	return $result;
    }
    
    
    
    /**
     * Trae las llaves de los modulos para el filtro en el grid
     * @param Objeto 		$mainObj				Objeto principal del sistema
     */
    public function getModulos($mainObj)
    {
        
        $result = array ();
        $consulta = " SELECT CLASE, NOMBRE_CLASE
	    FROM SYS_MODULOS
	    ORDER BY NOMBRE_CLASE";
        
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $params = null;
        $sqlData = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
        if ($sqlData)
        {
            foreach ( $sqlData as $sqlItem )
            {
                $sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
                $result [$sqlItem ["CLASE"]] = $sqlItem ["NOMBRE_CLASE"];
            }
        }
        
        return $result;
    }
    
/**
	 * Selecciona el archivo que funcionará como logo principal
	 * @param String $path			Carpeta dond elo buscará
	 * @param String $fileName	Nombre del archivo
	 */
	public function getSysParam($mainObj, $field, $condition = false, $order = false, $operation = false, $operator = false)
	{
		
		$fields = array($field);
		$sqlResults = $mainObj->sql->executeQuery($mainObj->connection, "SITE_PARAMS", $fields,  $condition, $order, $operation, $operator);
		if($sqlResults)
		{
			$sqlItem = $sqlResults[0];
			$value = $sqlItem[$field];
			return $value;
		}
		else
		{
			$this->setError(42,__FUNCTION__, __CLASS__);
		}
		
		return false;
	}
    
	
	
	//Valida que se tenga permisos para ver la ruta por perfil
	public function getPermisosPerfil($mainObj, $folderName, $ruta, $cveItem )
	{
		$result = array("VISUALIZAR_CARPETA" => 0,"VISUALIZAR_ARCHIVOS" => 0,"CARGA"=> 0, 
				"DESCARGA"=> 0, "ELIMINA_ARCHIVOS" => 0,  "HEREDA_HIJOS" => 0, "HEREDA_PADRE" =>0);
		
		$rutaAux = str_replace(FILEROOT, "#__ROOT__#", $ruta);
		
		$consulta = "SELECT VISUALIZAR_CARPETA, VISUALIZAR_ARCHIVOS, CARGA, DESCARGA, ELIMINA_ARCHIVOS, 
				HEREDA_HIJOS, HEREDA_PADRE 
				from SITE_PERMISOS
				WHERE
				NOMBRE LIKE  ?  AND RUTA like ?
				AND TIPO = ?
				AND CVE_PERFIL = ?";
		
		$params = array($folderName, $rutaAux, 1, $cveItem);
		$ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
		$sqlResults = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
		$result = false;
	
		if($sqlResults)
		{
			$sqlResults = $sqlResults[0];
			$result["VISUALIZAR_CARPETA"] = $sqlResults["VISUALIZAR_CARPETA"] == 0 ? " " : " checked";
			$result["VISUALIZAR_ARCHIVOS"] = $sqlResults["VISUALIZAR_ARCHIVOS"] == 0 ? " " : " checked";
			$result["CARGA"] = $sqlResults["CARGA"] == 0 ? " " : " checked";
			$result["DESCARGA"] = $sqlResults["DESCARGA"] == 0 ? " " : " checked";
			$result["ELIMINA_ARCHIVOS"] = $sqlResults["ELIMINA_ARCHIVOS"] == 0 ? " " : " checked";
			$result["HEREDA_HIJOS"] = $sqlResults["HEREDA_HIJOS"] == 0 ? " " : " checked";
			$result["HEREDA_PADRE"] = $sqlResults["HEREDA_PADRE"] == 0 ? " " : " checked";
		}
		
		return $result;
	}
	
	
	//Valida que se tenga permisos para ver la ruta por perfil
	public function getPermisos($mainObj, $folderName, $ruta, $cveItem, $tipo, $strVal )
	{
		$result = array("VISUALIZAR_CARPETA" => 0,"VISUALIZAR_ARCHIVOS" => 0, "CARGA"=> 0, 
				"DESCARGA"=> 0, "ELIMINA_ARCHIVOS" => 0,  "HEREDA_HIJOS" => 0, "HEREDA_PADRE" => 0);
	
		$rutaAux = str_replace(FILEROOT, "#__ROOT__#", $ruta);		
		
		$consulta = "SELECT VISUALIZAR_CARPETA, VISUALIZAR_ARCHIVOS, CARGA, DESCARGA, ELIMINA_ARCHIVOS, 
				HEREDA_HIJOS, HEREDA_PADRE
				from SITE_PERMISOS
				WHERE
				NOMBRE LIKE  ?  AND RUTA like ?
				AND TIPO = ? AND $strVal = ?";
	
		$params = array($folderName, $rutaAux, $tipo, $cveItem);
		$ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
		$sqlResults = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
		$result = false;
	
		if($sqlResults)
		{
			$sqlResults = $sqlResults[0];
			$result["VISUALIZAR_CARPETA"] = $sqlResults["VISUALIZAR_CARPETA"] == 0 ? " " : " checked = 1";
			$result["VISUALIZAR_ARCHIVOS"] = $sqlResults["VISUALIZAR_ARCHIVOS"] == 0 ? " " : " checked = 1";
			$result["CARGA"] = $sqlResults["CARGA"] == 0 ? " " : " checked = 1";
			$result["DESCARGA"] = $sqlResults["DESCARGA"] == 0 ? " " : " checked = 1";
			$result["ELIMINA_ARCHIVOS"] = $sqlResults["ELIMINA_ARCHIVOS"] == 0 ? " " : " checked = 1";
			$result["HEREDA_HIJOS"] = $sqlResults["HEREDA_HIJOS"] == 0 ? " " : " checked";
			$result["HEREDA_PADRE"] = $sqlResults["HEREDA_PADRE"] == 0 ? " " : " checked";
		}
	
		return $result;
	}
	
	
	/**
	 * Salva lospermisos para los hijos de la carpeta asignada
	 * @param Object 	$mainObj				Objeto principal del sistema
	 * @param String 	$folderPath			Path del folder a guardar
	 * @param Integer $tipo						Tipo de permiso, perfil o usuario
	 * @param String 	$strTipo				Clave del campo a guardar
	 * @param String 	$cveItem				Valor del campo a guardar
	 * @param String 	$cvePerfil			Clave delperfil
	 * @param String 	$cveUsuario			Clave del usuario
	 * @param Array 	$perValues			Array con la claves de los permisos
	 * @return boolean
	 */
	public function saveChildPermissionsInicial($mainObj, $folderPath, $tipo,$strTipo, $cveItem,  $cvePerfil, $cveUsuario, $perValues, $operadoraName)
	{

		$tiempo_inicio = microtime(true);
		/*$iterator = new RecursiveIteratorIterator
		( new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST);		
		*/
		$iterator = $mainObj->files->getGlobDirectoryItemsRecursive($folderPath);
		

		 
		$rootPath = str_replace(FILEROOT,"#__ROOT__#", $folderPath);
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			$rootPath = str_replace('\\', '/', $rootPath);
		}
		
		
		$deleteQuery = "DELETE FROM SITE_PERMISOS WHERE
			TIPO = ? AND $strTipo = ? AND NOMBRE_OPERADORA = ? AND RUTA LIKE ?";

		$psDelete = $mainObj->sql->setSimpleQuery($mainObj->connection, $deleteQuery);
		$paramsDelete = array($tipo, $cveItem,$operadoraName, "%$rootPath%");
		$delRes = $mainObj->sql->executeSimpleQuery($psDelete, $paramsDelete, $deleteQuery, null, true);
		
		 /*$insertQuery = "INSERT INTO SITE_PERMISOS (TIPO, CVE_PERFIL, CVE_USUARIO, NOMBRE, RUTA, ULTIMO_NIVEL, VISUALIZAR_CARPETA,
						VISUALIZAR_ARCHIVOS, CARGA, DESCARGA, ELIMINA_ARCHIVOS, HEREDA)
						VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
						
			 $psInsert = $mainObj->sql->setSimpleQuery($mainObj->connection, $insertQuery);
						
		*/
		
		$executeFlag = false;
		 $cont= 0;
		 $paramsInsert = array();
		 
		foreach($iterator as $file)
		{
			if(is_dir($file))
			//if($file->isDir())
			{
				
				$nivel = $this->getFolderLevel($file);
				//error_log("$file Nivel = $nivel");
				$executeFlag = true;
				$folderPathRoot = str_replace(FILEROOT,"#__ROOT__#", $file);
				
				if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') 
				{
        	$folderPathRoot = str_replace('\\', '/', $folderPathRoot);
    		}
    		
				$folderName = basename($file);
				$paramsInsert[] = array($tipo,$cvePerfil, $cveUsuario,$operadoraName, $folderName,$folderPathRoot, $nivel, $perValues[1], $perValues[2],
				$perValues[3], $perValues[4], $perValues[5], $perValues[6], $perValues[7]);
				//$sqlResult = $mainObj->sql->executeSimpleQuery($psInsert, $paramsInsert, $insertQuery, null, true);
			}
		}	
		
		if($executeFlag == true)
		{
			
			$sql = "INSERT INTO SITE_PERMISOS (TIPO, CVE_PERFIL, CVE_USUARIO,NOMBRE_OPERADORA, NOMBRE, RUTA, NIVEL, VISUALIZAR_CARPETA,
						VISUALIZAR_ARCHIVOS, CARGA, DESCARGA, ELIMINA_ARCHIVOS, HEREDA_HIJOS, HEREDA_PADRE)
						VALUES ";
			
			$paramArray = array();
			
			$sqlArray = array();
			
			foreach($paramsInsert as $row)
			{
				$sqlArray[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
				foreach($row as $element)
				{
					$paramArray[] = $element;
				}
			}
			
			$sql .= implode(',', $sqlArray);
			$psInsert = $mainObj->sql->setSimpleQuery($mainObj->connection,$sql);
			$sqlResult = $mainObj->sql->executeCleanQuery($psInsert, $paramArray);
			
		}
		$tiempo_fin = microtime(true);
		$tiempo = bcsub($tiempo_fin, $tiempo_inicio, 4);
		error_log("Tiempo proceso = ". ($tiempo/60));
		
		return true;
	}
	
	
	
	public function saveChildPermissions($mainObj, $folderPath, $tipo,$strTipo, $cveItem,  $cvePerfil, $cveUsuario, $perValues, $operadoraName)
	{
	
		$tiempo_inicio = microtime(true);
		
		//Normal con iterator:
		//Tiempo proceso = 18.4408		
		/*$iterator = new RecursiveIteratorIterator
		( new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST);
		/*foreach($iterator as $file)
		{
			
			if($file->isDir())
			{
				
				
				$folderPathRoot = str_replace(FILEROOT,"#__ROOT__#", $file);
				if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
				{
					$folderPathRoot = str_replace('\\', '/', $folderPathRoot);
				}
				
				$nivel = $this->getFolderLevel($folderPathRoot);
				error_log("$folderPathRoot::$nivel");
				
			}
		}*/
		
		//Normal recursivo
		//Tiempo proceso = 11.041
		/*
		$iterator = $mainObj->files->getGlobDirectoryItemsRecursive($folderPath);		
		foreach($iterator as $file)
		{
			$folderPathRoot = str_replace(FILEROOT,"#__ROOT__#", $file);
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
			{
				$folderPathRoot = str_replace('\\', '/', $folderPathRoot);
			}
			$nivel = $this->getFolderLevel($file);
			error_log("$folderPathRoot::$nivel");
		}*/
		
		
		//Recursivo con nivel
		//Tiempo proceso = 9.5988666666667	

		$selectQuery = "SELECT NOMBRE, HEREDA_HIJOS,HEREDA_PADRE
				FROM 
				SITE_PERMISOS 
				WHERE
				TIPO = ?
				AND $strTipo = ?
				AND NIVEL = ?
				AND NOMBRE_OPERADORA = ?
				AND NOMBRE = ?
				AND RUTA = ?";
		
		$psSelect = $mainObj->sql->setSimpleQuery($mainObj->connection, $selectQuery);
		
		
		$selectPapaQuery = "SELECT *
		FROM
		SITE_PERMISOS
		WHERE
		TIPO = ?
		AND $strTipo = ?
		AND NIVEL = ?
		AND NOMBRE_OPERADORA = ?
		AND NOMBRE = ?
		AND RUTA = ?";
		
		$psPapaSelect = $mainObj->sql->setSimpleQuery($mainObj->connection, $selectPapaQuery);
		
		
		$insertQuery = "INSERT INTO SITE_PERMISOS (TIPO, CVE_PERFIL, CVE_USUARIO,NOMBRE_OPERADORA, NOMBRE, RUTA, NIVEL, 
				VISUALIZAR_CARPETA,VISUALIZAR_ARCHIVOS, CARGA, DESCARGA, ELIMINA_ARCHIVOS, HEREDA_HIJOS,
			HEREDA_PADRE)
		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		$psInsert = $mainObj->sql->setSimpleQuery($mainObj->connection, $insertQuery);
		
		$updateQuery = $updateQuery = "UPDATE SITE_PERMISOS SET
			VISUALIZAR_CARPETA = ?, VISUALIZAR_ARCHIVOS = ? , CARGA = ? , DESCARGA = ? ,
			ELIMINA_ARCHIVOS = ?, HEREDA_HIJOS = ?, HEREDA_PADRE = ?
			WHERE
			TIPO = ? AND $strTipo = ? AND NOMBRE_OPERADORA = ?  AND NOMBRE = ? AND RUTA = ? AND  NIVEL = ?";
		
		$psUpdate = $mainObj->sql->setSimpleQuery($mainObj->connection, $updateQuery);
		
		$array = array();
		$iterator = $mainObj->files->getGlobDirectoryItemsRecursive2($folderPath, $array);		
		$padreArray = array();
		foreach($iterator as $file)
		{
			//Sacamos el nivel y el path
		 	$arrFile = explode("::", $file);
		 	$path = $arrFile[0];
		 	$nivel = $arrFile[1];
		 	
		 	$folderName = basename($path);
		 	$folderPathRoot = str_replace(FILEROOT,"#__ROOT__#", $path);
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
			{
				$folderPathRoot = str_replace('\\', '/', $folderPathRoot);
			}

			//Revisamos si existe en la base
			$selectParams = array($tipo, $cveItem, $nivel,$operadoraName, $folderName, $folderPathRoot);			
			$sqlResults = $mainObj->sql->executeSimpleQuery($psSelect, $selectParams, $selectQuery, null, false, false, false);
		
			//Si existe
			if($sqlResults && (sizeof($sqlResults) > 0))
			{				
				
				$sqlItem = $sqlResults[0];
				$heredaPadre = $sqlItem["HEREDA_PADRE"];
				$heredaHijo= $sqlItem["HEREDA_HIJOS"];
				
				//Si hereda del padre
				if($heredaPadre == 1)
				{
					//Sacamos al padre y vemos sus datos, 
					//si tiene la bandera de heredar a hijos sig, si no corto
					$arrayTop = explode("/", $folderPathRoot);
					array_pop($arrayTop);					
					$papa  = implode("/", $arrayTop);
					$papaName = basename($papa);
					$nivelPapa = $nivel-1;					
					$papaKey = "$papa::$nivelPapa";
					$papaData = null;
					
					//Si ya hay datos del papa en el array, los traemos
					if(isset($padreArray[$papaKey]))
					{
						$papaData = $padreArray[$papaKey];
					}
					//Si no hay datos en el array, los traemos de la base y los guardamos en el array
					else
					{						
						$selectPapaParams = array($tipo, $cveItem, $nivelPapa,$operadoraName, $papaName, $papa);
											
						$sqlPapaResults = $mainObj->sql->executeSimpleQuery($psPapaSelect, $selectPapaParams, $selectPapaQuery, null, false, false, false);						
						if($sqlPapaResults)
						{
							$papaData = $sqlPapaResults[0];						
							$padreArray[$papaKey] = $papaData;
						}
					}
					
					if($papaData)
					{
						if($papaData["HEREDA_HIJOS"] == 1)
						{
						   $updateParams = array( $papaData["VISUALIZAR_CARPETA"], $papaData["VISUALIZAR_ARCHIVOS"],
						   $papaData["CARGA"], $papaData["DESCARGA"], $papaData["ELIMINA_ARCHIVOS"], 
						   $papaData["HEREDA_HIJOS"],$papaData["HEREDA_PADRE"], $tipo,$cveItem,$operadoraName, $folderName,
						   $folderPathRoot,$nivel,);
						   $sqlUpdateResults = $mainObj->sql->executeSimpleQuery($psUpdate, $updateParams, $updateQuery, null, true);
						}
					}
				}
				//Si no hereda del padre, no hago nada
				/*
				else 
				{
					error_log("No hredo del padre");
				}*/
			}			
			//No existe
			//Meto lo que traigo de default
			else
			{
				$insertParams = array($tipo,$cvePerfil, $cveUsuario,$operadoraName, $folderName,$folderPathRoot, $nivel,
				$perValues[1], $perValues[2],$perValues[3], $perValues[4], $perValues[5], $perValues[6], 
				$perValues[7]);
				$sqlInsertResults = $mainObj->sql->executeSimpleQuery($psInsert, $insertParams, $insertQuery, null, true);
			}
		}

		/*$tiempo_fin = microtime(true);
		$tiempo = bcsub($tiempo_fin, $tiempo_inicio, 4);
		error_log("Tiempo proceso = ". ($tiempo/60));*/
	
		return true;
	}
	
	
	//Inserta un registro en la base cada vez que se cambie la contraseña del usuario
	public function addUserKey($mainObj, $tipo, $cveUsuario,$llave )
	{
		//Llaves
		$fields = "CVE_USUARIO, FECHA, LLAVE";
		$datos = "?,?,?";
		$fechaLlave = date("Ymd");
		$values = array($cveUsuario, $fechaLlave,$llave);
		$tabla = "SITE_HISTORICO_LLAVES";
		if($tipo == 2)
		{
			$tabla = "SYS_HISTORICO_LLAVES";
		}
		$result = $mainObj->sql->insertData($mainObj->connection, $tabla, $fields, $datos, $values, "");
	}
	
	
	/**
	 * Trae el nombre del módulo en donde estamos
	 * @param	Object $mainObj	Objeto principal del sistema
	 * @param	String $nombreOpradora	Nombre de la operadora
	 */
	public function getTipoOperadora($mainObj, $nombreOpradora)
	{
		$result = false;
		$fields = array ("TIPO" );
		$table = "SYS_OPERADORAS";
		$condition = array ("RAIZ" => $nombreOpradora );
		$sqlResults = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition );	
		if ($sqlResults)
		{
			$sqlItem = $sqlResults [0];
			$sqlItem = $this->getArrayObject ( $mainObj->conId, $sqlItem );
			$result = $sqlItem ["TIPO"];
		}
		else
		{
			$this->setError ( 42, __FUNCTION__, __CLASS__ );
		}
	
		return $result;
	}
	
	
	
	
	
    private function getTemplate($name)
    {
        
        $template ["fileList"] = <<< TEMP
	<div id = "fileListItem">
		<a href= javascript:;" onmousedown="toggleDiv('showDiv__NUM__');" class = "topLink">__FILELISTNAME__</a>
		<div id = "showDiv__NUM__" style="display:none">
			<ul class = "fileListList">
			__FILELISTITEMS__
			</ul>
		</div>
	</div>
	
TEMP;
        
        $template ["fileListItem"] = <<< TEMP
		<li class = "__CLASS__">
		__FILELISTITEM__
		</li>
	
TEMP;
        
        return $template [$name];
    }
}
?>