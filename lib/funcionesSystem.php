<?php

/**
 * Clase encargada de ejecutar funciones que se van a usar solo para el sistema
 * Si se usa para otro sistema deberíamos hacer una clase parecida
 * Esto para no interferir con las funciones nativas del Framework
 * @author pitler
 *
 */
class funcionesSystem extends funciones
{
    
    /**
     * Nombre de la clase
     * @var String  - Nombre de la clase 
     */
    //private $className;
    

    function __construct()
    {
    }
    
    /** 	  
     * Función que nos regresa el número máximo  de un campo + 1 para talbas del sistema
     * Sirve para llevar control de consecutivos
     * El campo al que haga referencia debe de ser un campo numérico
     * @param Object 	$mainObj	Objeto principal del sistema	
     * @param String 	$tabla		Tabla a la que se hace la consulta
     * @param String 	$field		Campo a revisar
     * @param Integer $inicio		Número del cual empezaría el consecutivo si no regresa nada la consulta (No existe el campo)
     */
    public function getConsecutivo($mainObj, $tabla, $field, $inicio = 0)
    {
        //Por default es 1
        $consecutivo=$inicio;

        $consulta = "SELECT MAX($field) AS $field FROM $tabla ";
        
        $params = null;
       
        
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        
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
    public function getSelectForModel($mainObj, $table, $fields = false, $condition = false, $order = false, $operation = false, $operator = false, $default = false, $sameKey = false, $extraField = false, $cache = false, $substring = false)
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
     */
    public function generaExcel($mainObj, $fileName, $title, $model, $consulta, $fields, $params, $ocultos, $jQueryDownload = false, $fieldFormat = false)
    {
        
        
        require_once "src/phpExcel/Classes/PHPExcel.php";
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

        //Ponemos los encabezados
        foreach ( $encabezados as $item )
        {
            
            $celda = $cols [$letra];
            $encabezado = isset ( $item ["label"] ) ? $item ["label"] : null;
            
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
            
            $encabezadosArr [$encabezado] = 1;
            $objPHPExcel->getActiveSheet ()->setCellValue ( "$celda$cont", $encabezado );
            $objPHPExcel->getActiveSheet ()->getColumnDimension ( "$celda" )->setAutoSize ( true );
            
            $letra ++;
        }
        $hoy = date ( 'd/m/Y' );
        $ps = $mainObj->sql->setSimpleQuery ( $mainObj->connection, $consulta );
        $data = $mainObj->sql->executeSimpleQuery ( $ps, $params, $consulta );
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
                    $objPHPExcel->getActiveSheet ()->setCellValue ( "$celda$itCont", $dato);
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
    
  
    public function getJsFondos($sqlData, $key = false, $all = false)
    {
        $data = "";
        $changeItem = $this->getTemplate ( "changeItem" );
        $jsItemData = $this->getTemplate ( "jsItemOption" );
        
        if ($sqlData)
        {
            $fondosArray = array ();
            
            foreach ( $sqlData as $item )
            {
                $fondoAux = array ();
                $fraccion = $item ["CVE_OPERADORA"];
                if (isset ( $fondosArray [$fraccion] ))
                {
                    $fondoAux = $fondosArray [$fraccion];
                    $fondoAux [$item ["CVE_FONDO"]] = array ($item ["CVE_FONDO"], $item ["DESC_FONDO"], $item ["CVE_OPERADORA"] );
                    $fondosArray [$fraccion] = $fondoAux;
                }
                else
                {
                    $fondoAux [$item ["CVE_FONDO"]] = array ($item ["CVE_FONDO"], $item ["DESC_FONDO"], $item ["CVE_OPERADORA"] );
                    $fondosArray [$fraccion] = $fondoAux;
                }
            }
            
            $varAux = "";
            $changeAux = "";
            
            $cont2 = 1;
            
            foreach ( $fondosArray as $arrayFondos )
            {
                $fondoAux = 0;
                $changeItemDataAux = $changeItem;
                $cont = 0;
                $varAux = "";
                
                if ($all)
                {
                    $cont = 1;
                    $jsItemDataAux = $jsItemData;
                    $jsItemDataAux = preg_replace ( "/__NUM__/", 0, $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__OPTNOMBRE__/", "Todos", $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__OPTVALUE__/", 0, $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__COMBONUM__/", 0, $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/selected/", "false", $jsItemDataAux );
                    $varAux .= $jsItemDataAux;
                }
                foreach ( $arrayFondos as $arrayItem )
                {
                    $jsItemDataAux = $jsItemData;
                    $jsItemDataAux = preg_replace ( "/__NUM__/", $cont . $arrayItem [0], $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__OPTNOMBRE__/", rawurldecode ( $arrayItem [1] ), $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__OPTVALUE__/", $arrayItem [0], $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/__COMBONUM__/", $cont, $jsItemDataAux );
                    $jsItemDataAux = preg_replace ( "/selected/", $arrayItem [0] == $key ? "true" : "false", $jsItemDataAux );
                    $varAux .= $jsItemDataAux;
                    $fondoAux = $arrayItem [2];
                    $cont ++;
                }
                
                $changeItemDataAux = preg_replace ( "/__NUM__/", $fondoAux, $changeItemDataAux );
                $changeItemDataAux = preg_replace ( "/__JSITEMOPTION__/", $varAux, $changeItemDataAux );
                $changeAux .= $changeItemDataAux;
                $cont2 ++;
            }
            $data = $changeAux;
        }
        return $data;
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
        $esDiaHabil = $mainObj->date->esDiaHabilMandatos ( $mainObj, $fechaAux );
        while ( ! $esDiaHabil )
        {
            $fechaAux = $mainObj->date->restaDias ( $fechaAux, 1 );
            $esDiaHabil = $mainObj->date->esDiaHabilMandatos ( $mainObj, $fechaAux );
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
	
/**
	 * Selecciona el texto indicado de la tabla de SITE_TEXT
	 * @param Object	$mainObj		Objeto principal del sistema
	 * @param String  $field			Campo que regresa
	 * @param Array		$condition	Array con las condiciones para el query, puede ser nulo
	 * @param Array		$order			Array con los campos por los que queremos ordenar	 
	 * @param Array		$operation	Operación para la condición n, si no se encuentra en su posición asume poner un = 
	 * @param Array		$operador		Operador lógico de la condición n, si no se encuentra en su posición asume poner un AND	
	 */
	public function getMenu($mainObj, $id)
	{
		
		$menuTemp = $this->getTemplate("menu$id");
		$menuItemTemp = $this->getTemplate("menuItem$id");
		
		$data = $menuTemp;
		$menuItems = "";
		$dataAux = "";
		
		
		$fields = array("CLASE", "NOMBRE_CLASE", "DESC_CLASE");
		$condition = array("MENU" => 1, "STATUS" => 1);
		$order = array("ORDEN");		
		$sqlResults = $mainObj->sql->executeQuery($mainObj->connection, "SITE_MODULOS", $fields,  $condition, $order);		
		$cont = 1;		
		if($sqlResults)
		{
			foreach ($sqlResults as $sqlItem)
			{
				$menuItemTempAux = $menuItemTemp;
				$modulo =  $sqlItem["CLASE"]; //$mainObj->security->encryptVariable(1, "", $sqlItem["CLASE"]);
				
				$actual = $this->getGVariable("mod");
				$menuItemTempAux = preg_replace("/__MODULE__/", $modulo, $menuItemTempAux);
				$menuItemTempAux = preg_replace("/__CLASS__/", $actual == $modulo ? "current" : "", $menuItemTempAux);
				$menuItemTempAux = preg_replace("/__NAME__/", $sqlItem["NOMBRE_CLASE"], $menuItemTempAux);
				$cont++;
				$dataAux .= $menuItemTempAux;
			}
			$menuItems = $dataAux;
		}
		else
		{
			$this->setError(42,__FUNCTION__, __CLASS__);
		}
		
		$data = preg_replace("/__MENUITEMS__/", $menuItems, $data);
		
		
		return $data;
	}
	
	
	/**
	 * Regresa el valor de una variable de sesión encriptada
	 * @param Object $mainObj
	 * @param String $variable
	 * @return string
	 */
/*	private function getSessionVariable($mainObj, $variable)
	{
		
		$result = "";
		
		if($variable)
		{
			$result =  $mainObj->security->decryptVariable(2, $variable);
		}
		return $result;
		
	}*/
	
	
/**
	 * 
	 * Trae uno o mas parametros del sistema	 * 
	 * @param Object	$mainObj		Objeto principal del sistema	 
	 * @param Array		$fields			Array con los cmapos a regresar
	 * @param	Array		$condition	Array con las condiciones
	 */
/*	public function getSiteParams($mainObj, $fields, $condition = false)
	{
		$data = array();
		$sqlResults = $mainObj->sql->executeQuery($mainObj->connection, "SITE_PARAMS", $fields,  $condition);				
		if($sqlResults)
		{
			$sqlResults = $sqlResults[0];
			foreach ($sqlResults as $name=> $sqlItem)
			{
		         $data[$name] = $sqlItem;
			}
		}
		else
		{
			$this->setError(42,__FUNCTION__, __CLASS__);
		}
		
		return $data;
	}
	*/
	
	
	
    
    private function getTemplate($name)
    {
        
        $template ["changeItem"] = <<< TEMP
		if (selec == __NUM__)
    {
			__JSITEMOPTION__
    }
TEMP;
        
        $template ["jsItemOption"] = <<< TEMP
		var seleccionar__NUM__ = new Option("__OPTNOMBRE__","__OPTVALUE__","",selected);
    combo[__COMBONUM__] = seleccionar__NUM__;
TEMP;
        
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