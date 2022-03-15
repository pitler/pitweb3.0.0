<?php
set_time_limit ( 300 );

/**
 * Clase que se encarga de generar y ejecutar los querys por medio del javaBridge
 *
 */
class sqlBridge extends funciones
{
    /**
     * Nombre de la clase
     * @var String Nombre de la clase 
     */
    //private $className;
    

    function __construct()
    {
        //$this->className = "sqlBridge";
    }
    
    /**
     * Ya que con el javaBridge no podemos asignar las variables al query mediante bindVariables, tenemos que
     * sustituir los '?' donde van las variables por la variable como tal.
     * Esta función es de pantalla para hacerlo transparente entre  drivers de base de datos y el javaBridge
     * 
     * @param 	connObj 	Objeto de la conexión
     * @param 	String 		Consulta a ejecutar
     * @return 	Array 		Regresa la conexión y la consulta enviadas en un array sin haberles modificado 
     * nada simulando que regresa un prepareStatement
     */
    public function setSimpleQuery($connection, $consulta)
    {
        $ps = array ($connection, $consulta );
        return $ps;
    }
    
    /**
     * Función que simula un execute, lo que hace es tomar el query, cambia ?=>'?'
     * Sustituye '?' por los valores que corresponden que vienen en el array $params
     * Manda a ejecutar por medio de javaBridge,  mandando la consulta completa ya generada
     * @param Array		$ps			Array que contiene en el primer elemnto la conexión y en el segundo el query
     * @param Array		$params		Parámetros del query a ejecutar 
     * @param String	$query		Query a ejecutar, solo es de manera estética,  puede ir vacio
     * @return Array asociativo con los resultados del query
     */
    public function executeSimpleQuery($ps, $params = null, $query = "", $iud = false)
    {
        $results = null;
        
        //Tomamos la conexión y la consulta
        $connection = $ps [0];
        $consulta = $ps [1];
        
        //Cambio los ? por '?'  
        $sign = array ("?" );
        $consulta = str_replace ( "?", "'?'", $consulta );
        
        //Reemplazo las ocurrencias del '?' por sus variables en $params
        $consulta = str_replace ( array ('%', '?' ), array ('%%', '%s' ), $consulta );
        $consulta = vsprintf ( $consulta, $params );
        
        //error_log($consulta);
        //$this->getVardumpLogQuery($params);

       // error_log("Consulta nueva :: ". $consulta);
        //Esto es para ejecutar inserts o updates
        if ($iud == true)
        {
        	//error_log("Consulta nueva :: ". $consulta);	
            $result =  $connection->ejecutaSentencia ( $consulta );
                        
            return $result;
        }
        
        //Enviamos a ejecutar el query, le mandamos la conexion y la consulta
        $results = $this->fetchResultJson ( $connection, $consulta );
        
        return $results;
    }
    
    /**
     * Contruye una consulta con los datos enviados
     * Ejecuta en automático el setSimpleQuery() y el executeSimpleQuery() para regresar los resultados  
     * @param objConnection $connection	Objeto con la conexión
     * @param String				$tabla			Nombre de la tabla para el query
     * @param Array					$fields			Array con los campos que regresará de la tabla, si no trae nada se toma como *
     * @param Array					$condition	Array con las condiciones para el query, puede ser nulo
     * @param Array					$order			Array con los campos por los que queremos ordenar	 
     * @param Array					$operation	Operación para la condición n, si no se encuentra en su posición asume poner un = 
     * @param Array					$operador		Operador lógico de la condición n, si no se encuentra en su posición asume poner un AND
     * @return Array asociativo y numérico con los resultados del query o null si no tiene resultados
     */
    public function executeQuery($connection, $tabla, $fields = false, $condition = false, $order = false, $operation = false, $operador = false)
    {
        
        $consulta = "";
        $params = null;
        $contParams = 0;
        //Si no traemos conexión
        if (! $connection)
        {
            $this->setError ( 34, "", __CLASS__ . '::' . __FUNCTION__ );
        }
        
        //Solo si tenemos una tabla válida
        if ($tabla)
        {
            $consulta .= " SELECT ";
            
            //Si tenemos campos a regresar
            if ($fields && sizeof ( $fields ) >= 1)
            {
                $tamaño = sizeof ( $fields );
                $x = 1;
                
                // Ponemos cada campo separado por ','
                foreach ( $fields as $value )
                {
                    $consulta .= " $value ";
                    
                    //Si no es el último elemento, le ponemos ','
                    if ($x < $tamaño)
                    {
                        $consulta .= ",";
                    }
                    $x ++;
                }
            }
            
            //Si no se definen campos para regresar, regresamos todos
            else
            {
                $consulta .= " * ";
            }
            
            //Le decimos a que tabla ir
            $consulta .= " FROM $tabla ";
            
            //Si existe el Array de condiciones, ponemos el 'WHERE' y por cada condición ponemos el signo de '?'
            if ($condition)
            {
                
                $consulta .= " WHERE ";
                $x = 0;
                
                //Revisamos cada condición
                foreach ( $condition as $field => $value )
                {
                    //Nos dice si tenemos arrays como parametros de la condición
                    $inFlag = false;
                    //echo "Campo :: $field valor :: $value <br>";
                    //Asignamos el valor a params
                    $params [$contParams] = $value;
                    
                    //A partir de la segunda condición
                    if ($x > 0)
                    {
                        $pos = $x - 1;
                        
                        //Si existe el operador lo asignamos
                        if ($operador [$pos])
                        {
                            $consulta .= " $operador[$pos] ";
                        }
                        
                        //Si no existe operador para ese elemento ponemos 'AND'
                        else
                        {
                            $consulta .= " AND ";
                        }
                    }
                    //Ponemos el campo
                    $consulta .= " $field ";
                    
                    //si existe una operación la ponemos
                    if ($operation [$x])
                    {
                        $consulta .= " $operation[$x] ";
                        //Si son operaciones especiales, por cada elemento del array asignamos el valor y a params 
                        if (trim ( $operation [$x] ) == "IN" || trim ( $operation [$x] ) == "NOT IN")
                        {
                            //echo "Pos :: $pos  Value = $value<br>";
                            $consulta .= "(";
                            $tamañoIn = sizeof ( $value );
                            $xIn = 1;
                            $xAux = $x;
                            //quitamos la posición a evaluar en params
                            unset ( $params [$contParams] );
                            //Por cada valor en el array de la condición					
                            foreach ( $value as $item )
                            {
                                //unset($params[$xAux]);
                                $consulta .= " ? ";
                                $params [$contParams] = $item;
                                $contParams ++;
                                
                                //Ponemos comas para separar si hay mas de 1 elemento
                                if ($xIn < $tamañoIn)
                                {
                                    $consulta .= ",";
                                }
                                $xIn ++;
                            }
                            $consulta .= ")";
                            $inFlag = true;
                        }
                    }
                    
                    //Si no existe, ponemos '='
                    else
                    {
                        $consulta .= " = ";
                    }
                    //Si tuvimos alguna condición especial le ponemos su ? 
                    if ($inFlag == false)
                    {
                        $consulta .= " ? ";
                    }
                    $x ++;
                    $contParams ++;
                }
            }
            //Si tiene parametros de ordenamiento
            if ($order)
            {
                $consulta .= " ORDER BY ";
                
                $tamaño = sizeof ( $order );
                $x = 1;
                
                // Ponemos cada campo separado por ','
                foreach ( $order as $value )
                {
                    $consulta .= " $value ";
                    //Si no es el último elemento, le ponemos ','
                    if ($x < $tamaño)
                    {
                        $consulta .= ",";
                    }
                    $x ++;
                }
            }
            //Si tenemos una consulta válida
            if ($consulta != "")
            {
                //Reorganizamos el array			
                if ($params)
                {
                    $params = array_merge ( $params );
                }
                
                //Mandamos a ejecutar
                $ps = $this->setSimpleQuery ( $connection, $consulta );
                $sqlResults = $this->executeSimpleQuery ( $ps, $params, $consulta );
                return $sqlResults;
            }
            else
            {
                $this->setError ( 47, "", __CLASS__ . '::' . __FUNCTION__ );
            }
        
        }
        //Si no tiene tabla, avisamos y regresamos null
        $this->setError ( 46, "", __CLASS__ . '::' . __FUNCTION__ );
        return null;
    
    }
    
    
    
    
    
    
    
     /**
     * Contruye una consulta con los datos enviados
     * Ejecuta en automático el setSimpleQuery() y el executeSimpleQuery() para regresar los resultados  
     * @param objConnection $connection	Objeto con la conexión
     * @param String				$tabla			Nombre de la tabla para el query
     * @param Array					$fields			Array con los campos que regresará de la tabla, si no trae nada se toma como *
     * @param Array					$conditions	Array con las condiciones para el query, puede ser nulo
     * @param Array					$order			Array con los campos por los que queremos ordenar	 
     * @param Array					$operation	Operación para la condición n, si no se encuentra en su posición asume poner un = 
     * @param Array					$operador		Operador lógico de la condición n, si no se encuentra en su posición asume poner un AND
     * @return Array asociativo y numérico con los resultados del query o null si no tiene resultados
     */
    public function executeJoinQuery($connection, $tables, $fields = false, $conditions = false, $order = false, $operation = false, $operador = false)
    {
        
        
        $consulta = "";
        $params = null;
        $contParams = 0;
        //Si no traemos conexión
        if (! $connection)
        {
            $this->setError ( 34, "", __CLASS__ . '::' . __FUNCTION__ );
        }
        
        //Solo si tenemos una tabla válida
        if ($tables)
        {
            $consulta .= " SELECT ";
            
            //Si tenemos campos a regresar
            if ($fields && sizeof ( $fields ) >= 1)
            {
                $tamaño = sizeof ( $fields );
                $x = 1;
                
                // Ponemos cada campo separado por ','
                foreach ( $fields as $value )
                {
                    $consulta .= " $value ";
                    
                    //Si no es el último elemento, le ponemos ','
                    if ($x < $tamaño)
                    {
                        $consulta .= ",";
                    }
                    $x ++;
                }
            }
            
            //Si no se definen campos para regresar, regresamos todos
            else
            {
                $consulta .= " * ";
            }
            
            //Le decimos a que tabla ir
            $consulta .= " FROM  ";
            
            
            $tamTables = sizeof ( $tables );
            $xTab = 1;
            foreach ($tables as $key=>$table)
            {   
                $consulta .= " $table AS $key ";
                //Si no es el último elemento, le ponemos ','
                if ($xTab < $tamTables)
                {
                    $consulta .= ",";
                }
                $xTab ++;
            }
            
            //Si existe el Array de condiciones, ponemos el 'WHERE' y por cada condición ponemos el signo de '?'
            if ($conditions)
            {
                
                $consulta .= " WHERE ";
                $x = 0;
                
                //Revisamos cada condición
                foreach ( $conditions as $type => $condition  )
                {
                    //$field => $value
                    error_log("Tipo : $type ::");
                    $this->getVardumpLog($condition);
                    
                    if($type == "variables")
                    {
                        foreach ( $condition as $field => $value )
                        {
                            //Nos dice si tenemos arrays como parametros de la condición
                            $inFlag = false;
                            //echo "Campo :: $field valor :: $value <br>";
                            //Asignamos el valor a params
                            $params [$contParams] = $value;
                            
                            //A partir de la segunda condición
                            if ($x > 0)
                            {
                                $pos = $x - 1;
                                
                                //Si existe el operador lo asignamos
                                if ($operador [$pos])
                                {
                                    $consulta .= " $operador[$pos] ";
                                }
                                
                                //Si no existe operador para ese elemento ponemos 'AND'
                                else
                                {
                                    $consulta .= " AND ";
                                }
                            }
                            //Ponemos el campo
                            $consulta .= " $field ";
                            
                            //si existe una operación la ponemos
                           /* if ($operation [$x])
                            {
                                $consulta .= " $operation[$x] ";
                                //Si son operaciones especiales, por cada elemento del array asignamos el valor y a params 
                                if (trim ( $operation [$x] ) == "IN" || trim ( $operation [$x] ) == "NOT IN")
                                {
                                    //echo "Pos :: $pos  Value = $value<br>";
                                    $consulta .= "(";
                                    $tamañoIn = sizeof ( $value );
                                    $xIn = 1;
                                    $xAux = $x;
                                    //quitamos la posición a evaluar en params
                                    unset ( $params [$contParams] );
                                    //Por cada valor en el array de la condición					
                                    foreach ( $value as $item )
                                    {
                                        //unset($params[$xAux]);
                                        $consulta .= " ? ";
                                        $params [$contParams] = $item;
                                        $contParams ++;
                                        
                                        //Ponemos comas para separar si hay mas de 1 elemento
                                        if ($xIn < $tamañoIn)
                                        {
                                            $consulta .= ",";
                                        }
                                        $xIn ++;
                                    }
                                    $consulta .= ")";
                                    $inFlag = true;
                                }
                            }
                            
                            //Si no existe, ponemos '='
                            else
                            {
                                $consulta .= " = ";
                            }*/
                            
                            
                        }                        
                    }
                    
                    if($type == "tables")
                    {
                        
                        foreach ( $condition as $field => $value )
                        {
                             $consulta .= " $field  = $value ";
                        }
                        
                    }
                    
                    
                    
                    //Si tuvimos alguna condición especial le ponemos su ? 
                    if ($inFlag == false)
                    {
                        $consulta .= " ? ";
                    }
                    $x ++;
                    $contParams ++;
                }
            }
            //Si tiene parametros de ordenamiento
            if ($order)
            {
                $consulta .= " ORDER BY ";
                
                $tamaño = sizeof ( $order );
                $x = 1;
                
                // Ponemos cada campo separado por ','
                foreach ( $order as $value )
                {
                    $consulta .= " $value ";
                    //Si no es el último elemento, le ponemos ','
                    if ($x < $tamaño)
                    {
                        $consulta .= ",";
                    }
                    $x ++;
                }
            }
            
            
            //error_log("Consulta join 2 : $consulta");
            //Si tenemos una consulta válida
            if ($consulta != "")
            {
                //Reorganizamos el array			
                if ($params)
                {
                    $params = array_merge ( $params );
                }
                
                //Mandamos a ejecutar
               // $ps = $this->setSimpleQuery ( $connection, $consulta );
               $sqlResults= null;
               // $sqlResults = $this->executeSimpleQuery ( $ps, $params, $consulta );
                return $sqlResults;
            }
            else
            {
                $this->setError ( 47, "", __CLASS__ . '::' . __FUNCTION__ );
            }
        
        }
        //Si no tiene tabla, avisamos y regresamos null
       // $this->setError ( 46, "", __CLASS__ . '::' . __FUNCTION__ );
       // return null;
    
    }
    
    
    
    
    
    /**
     *Función encargada de generar el query para insertar a la base de datos
     * @param Connection	$connection Conexión a la base
     * @param String			$tabla 			Tabla donde se inserta 
     * @param String			$fields			String con los campos a insertar
     * @param String			$datos			String con los '?' por cada elemento insertado
     * @param Array				$params			Array on los valores a insertar		
     */
    public function insertData($connection, $tabla, $fields, $datos, $params)
    {
        
        $consulta = "INSERT INTO  $tabla ($fields) values ($datos) ";
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
        
        if ($sqlResult == 0)
        {
            $this->setError ( 48, __LINE__ . " :: " . __FUNCTION__ . ":: $consulta", __CLASS__ );
        }
        
        return $sqlResult;
    
    }
    
    public function insertDataDate($connection, $tabla, $fields, $datos, $params)
    {
        
        $consulta = "INSERT INTO  $tabla ($fields) values ";
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
        
        if ($sqlResult == 0)
        {
            //$this->setError(48,$consulta, $this->className);
            $this->setError ( 48, __LINE__ . " :: " . __FUNCTION__ . ":: $consulta", __CLASS__ );
        }
        
        return $sqlResult;
    
    }
    
    /**
     * Función que hace un update a la tabla con los valores dados
     * @param Object	$connection		Conexión al javaBridge
     * @param String	$tabla				Tabla que recibe los cambios
     * @param Array		$datos				Array con los datos a cambiar
     * @param Array		$keyFields		Array con las llaves de la tabla
     */
    public function updateData($connection, $tabla, $datos, $keyFields)
    {
        
        $consulta = "UPDATE $tabla SET ";
        $params = array ();
        
        $x = 0;
        
        foreach ( $datos as $key => $value )
        {
            if ($x > 0)
            {
                $consulta .= ", ";
            }
            
            //error_log("Key = $key  <br>	Value = $value");
            if (strstr ( $value, "to_date" ))
            {
                $valueAux = explode ( "//", $value );
                $value = $valueAux [1];
                $consulta .= "$key = $valueAux[0]";
            }
            
            else
            {
                $consulta .= "$key = ? ";
            }
            
            $params [] = $value;
            $x ++;
        }
        
        $consulta .= " WHERE ";
        
        $x = 0;
        foreach ( $keyFields as $field => $value )
        {
            if ($x > 0)
            {
                $consulta .= " AND ";
            }
            
            if (strstr ( $value, "to_date" ))
            {
                $valueAux = explode ( "//", $value );
                $value = $valueAux [1];
                $consulta .= "$field = $valueAux[0]";
            }
            else
            {
                $consulta .= " $field = ? ";
            }
            
            //$consulta .= " $field = ? ";
            $params [] = $value;
            $x ++;
        }
        
        //error_log("Consulta nueva :: ". $consulta);
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
        
        if ($sqlResult == 0)
        {
            //$this->setError(44,$consulta, $this->className);
            $this->setError ( 44, __LINE__ . " :: " . __FUNCTION__, __CLASS__ );
        }
        
        return $sqlResult;
    
    }
    
    /**
     *Función encargada de generar el query para eliminar datos de la base de datos
     * @param Connection	$connection Conexión a la base
     * @param String			$tabla 			Tabla donde se inserta 
     * @param String			$condition	Array asociativo con el campo y valor el cual buscar para borrar
     */
    public function deleteData($connection, $tabla, $condition)
    {
        
        $consulta = "DELETE FROM $tabla ";
        
        $params = array ();
        if ($condition)
        {
            $consulta .= " WHERE ";
            $x = 0;
            
            //Revisamos cada condición
            foreach ( $condition as $field => $value )
            {
                //Asignamos el valor a params
                $params [] = $value;
                
                //A partir de la segunda condición
                if ($x > 0)
                {
                    $consulta .= " AND ";
                }
                //Ponemos el campo
                $consulta .= " $field  =  ? ";
                $x ++;
            }
        }
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        //$this->getVardumpLog($params);
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
        
        if ($sqlResult == 0)
        {
            //$this->setError(49,$consulta, $this->className);
            $this->setError ( 49, __LINE__ . " :: " . __FUNCTION__, __CLASS__ );
        }
        
        return $sqlResult;
    
    }
    
    /**
     * Ejecuta un query generado por medio de javaBridge
     * @param connObject	$jbConnection	Conexión al javaBridge
     * @param String			$consulta			Consulta a ejecutar		
     */
    private function fetchResultJson($jbConnection, $consulta)
    {
        $javaResult = false;
        
        try
        {
            //Ejecuta el query en el javaBridge
            $javaResult = $jbConnection->ejecutaQueryJson ( $consulta );
            //Como lo regresa en formato de json, entonces lo decodificamos
            $javaResult = json_decode ( $javaResult );
        }
        catch ( Exception $e )
        {
            echo $e->getMessage ();
            $this->setError ( 45, __LINE__ . " :: " . __FUNCTION__, __CLASS__ );
            return false;
        }
        
        //Regresa aun array con un elemento que es el resultado
        //Por eso siempre mandamos el primer elemento
        
        return $javaResult;
    }
    
    /**	  
     * Función que integra el archivo solicitado por medio del javaBridge
     * Llamamos la función integraArchivo en webLogic
     * @param Object		$mainObj				Objeto principal del sistema
     * @param Integer		$cveOperadora		Clave de la operadora
     * @param String		$fecha					Fecha seleccionada por el usuario
     * @param Array			$archivos				Array serializado con los nombres de los archivos
     */
    public function integraArchivo($mainObj, $cveOperadora, $fecha, $archivos)
    {
        $javaResult = false;
        
        $this->getVardumpLog($mainObj->conAux);
        try
        {   
            $javaResult = $mainObj->conAux->integraArchivo ( ( int ) $cveOperadora, ( string ) $fecha, ( string ) $archivos );
        
        }
        catch ( Exception $e )
        {
            
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return "0";
        }
       
        return $javaResult;
    }
    
    
    
 /**	  
     * Función que integra el archivo solicitado por medio del javaBridge
     * Llamamos la función integraArchivo en webLogic
     * @param Object		$mainObj				Objeto principal del sistema
     * @param Integer		$cveOperadora		Clave de la operadora
     * @param String		$fecha					Fecha seleccionada por el usuario
     * @param Array			$archivos				Array serializado con los nombres de los archivos
     */
    public function cargaInsumos($mainObj, $strOperadora, $cveOperadora, $strFondo, $cveFondo, $fecha, $archivos)
    {
        
        
        $javaResult = false;
        
        try
        {  
            $javaResult = $mainObj->conAux->cargaArchivosConciliaciones ( (string) $strOperadora, ( int ) $cveOperadora, (string) $strFondo,  (int) $cveFondo,  ( string ) $fecha, ( string ) $archivos );
        }
        catch ( Exception $e )
        {
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return "0";
        }
       
        return $javaResult;
    }
    
	/**	  
     * Función que integra el archivo solicitado por medio del javaBridge
     * Llamamos la función integraArchivo en webLogic
     * @param Object		$mainObj				Objeto principal del sistema
     * @param Integer		$cveOperadora		Clave de la operadora
     * @param String		$fecha					Fecha seleccionada por el usuario
     * @param Array			$archivos				Array serializado con los nombres de los archivos
     */
    public function cargaInsumosAscii($mainObj, $cveOperadora, $fecha, $archivos)
    {
        $javaResult = false;
        
        try
        {  
            $javaResult = $mainObj->conAux->cargaArchivosASCIIConciliacion ( ( int ) $cveOperadora, ( string ) $fecha, ( string ) $archivos );
            
        }
        catch ( Exception $e )
        {
            
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return "0";
        }
        
        return $javaResult;
    }

    
     public function generaAscci($mainObj, $cveOperadora, $fecha)
     {        
        
        $javaResult = false;
        
        try
        {   
            $javaResult = $mainObj->conAux->generaAscii( ( int ) $cveOperadora, ( string ) $fecha);
        
        }
        catch ( Exception $e )
        {   
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return false;
        }
        
        return $javaResult;
    }
    
    
    /**	  
     * Función que integra el archivo solicitado por medio del javaBridge
     * Llamamos la funcinón integraArchivo en webLogic
     * @param Object		$mainObj						Objeto principal del sistema
     * @param Integer		$cveOperadora				Clave de la operadora
     * @param String		$fecha							Fecha seleccionada por el usuario
     * @param Integer		$tipoConciliacion		El tipo de conciliación a mandar
     */
    public function generaConciliaciones($mainObj, $cveOperadora, $fecha, $tipoConciliacion)
    {
        $javaResult = false;
        try
        {            
            switch ($tipoConciliacion)
            {
                //Para Equity
                case 1 :
                    $javaResult = $mainObj->conAux->generaConciliaciones ( ( int ) $cveOperadora, ( string ) $fecha );
                    break;
                //Para divisas
                case 2 :
                    $javaResult = $mainObj->conAux->generaConciliacionesDivisas ( ( int ) $cveOperadora, ( string ) $fecha );
                    break;
                //Para dividendos
                case 3 :
                    $javaResult = $mainObj->conAux->generaConciliacionesDividendos ( ( int ) $cveOperadora, ( string ) $fecha );
                    break;
                //Para derechos
                case 4 :
                    $javaResult = $mainObj->conAux->generaConciliacionesDerechos ( ( int ) $cveOperadora, ( string ) $fecha );
                    break;
            }        
        }
        catch ( Exception $e )
        {
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return false;
        }
        
        return $javaResult;
    }
    
	/**	  
     * Función que integra el archivo solicitado por medio del javaBridge
     * Llamamos la funcinón integraArchivo en webLogic
     * @param Object		$mainObj					Objeto principal del sistema
     * @param Integer		$operadora					Descripción de la operadora
     * @param Integer		$cveOperadora				Clave de la operadora
     * @param String		$fondo						Descripción del fondo
     * @param Integer		$cveFondo					Clave del fondo
     * @param String		$fecha						Fecha seleccionada por el usuario
     * @param integer		$codigo						Cave del archivo a conciliar
     * @param String		$archivos					Lista de archivos a cargar
     * @param Integer		$tipo						El tipo proceso: 1-carga archivos; 2-ConciliaArcvhivos
     */
    public function archivosConciliaciones($mainObj, $operadora, $cveOperadora, $fondo, $cveFondo, $fecha, $codigo = null, $archivos = null, $tipo)
    {
        $javaResult = false;
        try
        {            
            switch ($tipo)
            {
                //Carga de archivos
                case 1 :
                    $javaResult = $mainObj->conAux->cargaArchivosConciliaciones ( (string) $operadora, ( int ) $cveOperadora, ( string ) $fondo, (int) $cveFondo, ( string ) $fecha, (string) $archivos);
                    break;
                //Conciliación de archivo
                case 2 :
                	$javaResult = $mainObj->conAux->generaConciliaciones2 ( (string) $operadora, ( int ) $cveOperadora, ( string ) $fondo, (int) $cveFondo, ( string ) $fecha, (int) $codigo );
                    break;
                case 101 :
                	$javaResult = $mainObj->conAux->generaConciliaciones2 ( (int) $cveOperadora, ( int ) $cveOperadora, ( string ) $fondo, (int) $cveFondo, ( string ) $fecha, (int) $codigo );
                    break;
                case 3 :
                	$javaResult = $mainObj->conAux->generaValidaciones ( (string) $operadora, ( int ) $cveOperadora, ( string ) $fondo, (int) $cveFondo, ( string ) $fecha);
                    break;
                default :
                    break;
            }        
        }
        catch ( Exception $e )
        {
            $this->setError ( 331, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return false;
        }
        
        return $javaResult;
    }
    
/**	  
     * Función que valida que la formula enviada sea valida
     * Llamamos la función integraArchivo en webLogic
     * @param Object		$mainObj				Objeto principal del sistema
     * @param String		$regla					String a verificar

     */
    public function validaRegla($mainObj,  $regla)
    {
        $javaResult = "false";
         
        try
        { 
            $javaResult = $mainObj->conAux->validaFormula( ( string ) $regla);
         
        }
        catch ( Exception $e )
        {
            
            $this->setError ( 149, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return "false";
        }
        
        return $javaResult;
    }
    
    
    
    
/**	  
     * Función que integra la balanza por medio del jB
     * Llamamos la función cargaArchivosBalanza en webLogic
     * @param Object		$mainObj				Objeto principal del sistema
     * @param Integer		$cveOperadora		Clave de la operadora
     * @param String		$fecha					Fecha seleccionada por el usuario
     * @param Array			$archivos				Array serializado con los nombres de los archivos
     */
    public function cargaArchivosBalanza($mainObj, $cveOperadora,$fondo,  $fecha)
    {
        $javaResult = false;
        //error_log("integro bridge");
        try
        {   
            $javaResult = $mainObj->conAux->cargaArchivosBalanza ( ( string ) $cveOperadora, (string) $fondo, ( string ) $fecha );
            
        }
        catch ( Exception $e )
        {
            
            $this->setError ( 150, __FUNCTION__ . " :: " . $e->getMessage (), __CLASS__ );
            return "0";
        }
       
        return $javaResult;
    }
    
    
    public function insertaBitacora($connection, $cveUsuario, $modulo, $tabla, $accion, $datos, $sistema)
    {               
        $consulta = "INSERT INTO FL_BITACORA 
        ( CVE_USUARIO, FECHA, MODULO, TABLA, ACCION, DATOS, SISTEMA, IP) 
	    VALUES
	    (?,GETDATE(),?,?,?,?,?,?) ";        
        $ip = $_SERVER ['REMOTE_ADDR'];
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        $params = array ($cveUsuario,  $modulo, $tabla, $accion, $datos, $sistema, $ip );        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
    }

}

?>