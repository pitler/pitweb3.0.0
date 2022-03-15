<?php
set_time_limit ( 300 );
/**
 * Clase que se encarga de generar y ejecutar los querys por medio de PDO
 *
 */
class sqlSrv extends funciones
{
    /**
     * Nombre de la clase
     * @var String Nombre de la clase 
     */
    //private $className;
    

    function __construct()
    {
    
     //$this->className = "sqlPdo";
    }
    
    /**
     * Prepara la consulta para ser ejecutada
     * @param connObj 	Objeto de la conexión
     * @param String 		Consulta a ejecutar
     * @return Objeto prepareStatement
     */
    public function setSimpleQuery($connection, $consulta, $params)
    {
        
        //error_log($consulta);
        $ps = null;
        
      //  $ps = $connection->prepare ( $consulta );
        
       // $this->getVardumpLog($connection);
        $ps = sqlsrv_prepare( $connection, $consulta , $params);
        
        //Si no se genera avisamos
        if ($ps == null)
        {
            //$this->setError(43,"", __CLASS__);
            $this->setError ( 43, __LINE__ . " - " . qlsrv_errors()." :: " . __FUNCTION__, __CLASS__ );
        }
        return $ps;
    }
    
    /**
     * Función que ejecuta el query almacenado en el prepareStatement
     * @param prepareStatement	$ps			PrepareStatement a ejecutar
     * @param Array				$params		Parámetros del query a ejecutar 
     * @param String			$query		Query a ejecutar
     * @param Array				$types		Array con los tipos de datos que se espera recibir 
     * @param Boolean			$iup		Bandera para saber si se ejecuta un insert, update o delete
     * @return Array asociativo y numérico con los resultados del query
     */
    public function executeSimpleQuery($ps, $params = null, $query = "", $types = null, $iup = false)
    {
        $results = null;
        
        try
        {
            //Esta parte es para ejecutar haciendo un bindValue() por cada parametro
            /*$cont = 1;
			foreach ($params as $param)
			{
				$ps->bindValue($cont, $param, $types[$cont-1]);
				$cont++;
			}*/
            /*
			error_log("<<<<");
			error_log(var_dump($params));	
			error_log($query);			*/
            
            //Para debug
           // error_log("Consulta nueva :: ". $query);	
           // $this->getVardumpLog($params);
            
            

            /*if ($iup)
            {
               $result = $ps->execute ( $params );
                $ps->closeCursor();
                return $result;
            }*/

             $results = sqlsrv_execute( $ps ); 
            //$ps->execute ( $params );
            
            //Hacemos el fetch para los resultados
            //Por default genera un array asociativo			
            //$results = $ps->fetchAll ();
            
          //  $ps->closeCursor();
            
            //Si no trae datos y no manda excepción, avisamos
            if (! $results)
            {
                $this->setError ( 42, __LINE__ . " :: " . __FUNCTION__ . " :: ", __CLASS__, 2 );
            }
        }
        catch ( PDOException $e )
        {
            //Vamos y pintamos el error que se genera	
           // $ps->closeCursor();		
          //  $error = $ps->errorInfo ();
            $this->setError ( 41, __LINE__ . " :: " . __FUNCTION__ . " " . $error [2], __CLASS__ );
        }
        
        // $ps->closeCursor();
        // $this->getVardumpLog($results);
        return $results;
    }
    
    /**
     * Contruye una consulta con los datos enviados
     * Ejecuta en automático el setSimpleQuery() y el executeSimpleQuery() para regresar los resultados  
     * @param objConnection 		$connection		Objeto con la conexión
     * @param String				$tabla			Nombre de la tabla para el query
     * @param Array					$fields			Array con los campos que regresará de la tabla, si no trae nada se toma como *
     * @param Array					$condition		Array con las condiciones para el query, puede ser nulo
     * @param Array					$order			Array con los campos por los que queremos ordenar	 
     * @param Array					$operation		Operación para la condición n, si no se encuentra en su posición asume poner un = 
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
                //$params = array_merge($params);
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
     *Función encargada de generar el query para insertar a la base de datos
     * @param Connection	$connection Conexión a la base
     * @param String			$tabla 			Tabla donde se inserta 
     * @param String			$fields			String con los campos a insertar
     * @param String			$datos			String con los '?' por cada elemento insertado
     * @param Array				$params			Array on los valores a insertar		
     */
    public function insertData($connection, $tabla, $fields, $datos, $params, $className)
    {
        
        $consulta = "INSERT INTO  $tabla ($fields) values ($datos) ";
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, null, true );
        
        return $sqlResult;
    
    }
    
    /**
     * 
     * Manda el insert como consulta completa, sustituyendo los valores en el query
     * Esto para hacer los inserts sin pasar los datos como parametros
     * @param Connection	$connection	Conexion
     * @param String		$tabla		Tabla donde se guarda la información
     * @param String		$fields		Campos de la tabla
     * @param String		$datos		Campos  a guardar
     * @param Array			$params		Parámetros a guardar
     * @param String		$className	Clase de donde viene
     */
    public function insertDataAux($connection, $tabla, $fields, $datos, $params, $className)
    {
        
        $consulta = "INSERT INTO  $tabla ($fields) values ($datos) ";
        
        //Cambio los ? por '?'  
        $sign = array ("?" );
        $consulta = str_replace ( "?", "'?'", $consulta );
        
        //Reemplazo las ocurrencias del '?' por sus variables en $params
        $consulta = str_replace ( array ('%', '?' ), array ('%%', '%s' ), $consulta );
        $consulta = vsprintf ( $consulta, $params );

        //Los nulos deben de ser null mas no 'null'        
        $consulta = preg_replace("/'null'/", "null", $consulta);        
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        $sqlResult = $this->executeSimpleQuery ( $ps, null, $consulta, null, true );
        
        
        return $sqlResult;
    
    }
    
/**
     * 
     * Manda el insert como consulta completa, sustituyendo los valores en el query
     * Esto para hacer los inserts sin pasar los datos como parametros
     * @param Connection	$connection	Conexion
     * @param String		$tabla		Tabla donde se guarda la información
     * @param String		$fields		Campos de la tabla
     * @param String		$datos		Campos  a guardar
     * @param Array			$params		Parámetros a guardar
     * @param String		$className	Clase de donde viene
     */
    public function updateDataAux($connection, $tabla, $datos, $keyFields)
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
            // $consulta .= " $field = ? ";
            $params [] = $value;
            $x ++;
        }
        
        //Cambio los ? por '?'  
        $sign = array ("?" );
        $consulta = str_replace ( "?", "'?'", $consulta );
        
        //Reemplazo las ocurrencias del '?' por sus variables en $params
        $consulta = str_replace ( array ('%', '?' ), array ('%%', '%s' ), $consulta );
        $consulta = vsprintf ( $consulta, $params );

        //Los nulos deben de ser null mas no 'null'        
        $consulta = preg_replace("/'null'/", "null", $consulta);        
       // error_log("ConsultaUpdte $consulta");
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        $sqlResult = $this->executeSimpleQuery ( $ps, null, $consulta, null, true );
        
        
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
            // $consulta .= " $field = ? ";
            $params [] = $value;
            $x ++;
        }
        
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, null, true );
        
        /*if($sqlResult == 0)
		{
			$this->setError(44,$consulta, $this->className);
		}*/
        
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
        
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, null, true );
        
        /*if($sqlResult == 0)
		{
			$this->setError(49,$consulta, $this->className);
		}*/
        
        return $sqlResult;
    
    }
    
  
    
    public function insertaBitacora($connection, $cveUsuario, $modulo, $tabla, $accion, $datos, $sistema)
    {               
        $consulta = "INSERT INTO FL_BITACORA 
        ( CVE_USUARIO, FECHA, MODULO, TABLA, ACCION, DATOS, SISTEMA, IP) 
	    VALUES
	    (?,SYSDATE,?,?,?,?,?,?) ";        
        $ip = $_SERVER ['REMOTE_ADDR'];
        $ps = $this->setSimpleQuery ( $connection, $consulta );
        $params = array ($cveUsuario,  $modulo, $tabla, $accion, $datos, $sistema, $ip );
        //    
        $sqlResult = $this->executeSimpleQuery ( $ps, $params, $consulta, true );
    }
}

?>