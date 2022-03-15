<?php
/**
 * Clase encargada de la seguridad del sistema
 * Crea y verifica sesiones
 * Verifica acceso a los módulos
 * Verifica las acciones que se pueden hacer por módulo
 * Encripta y desencripta variables
 * @author pitler
 *
 */
class moduleSecurity extends funciones
{
    
    function __construct()
    {
    
    }
    
    /**	
     * Genera las llaves usadas para la sesión
     * Estas llaves son unicas por sesión
     * Una parte se guarda en la cookie y la otra en la sesión
     */
    
    public function createSessionKey()
    {
        //Comprobamos si la sesión ya fue iniciada
        $existeSesion = isset ( $_SESSION ["autentified"] );
        
        //Si no existe la sesion
        if (! $existeSesion)
        {
            //Generamos una llave unica			
            $llaveBase = md5 ( uniqid ( rand (), true ) );
            //Tomamos su tamaño
            $longitud = strlen ( $llaveBase );
            //Sacamos la mitad de ese tamaño
            $mitad = $longitud / 2;
            //Una mitad se guarda en la variable autentified y nos sirve como llave de sesión
            $serverKey = substr ( $llaveBase, 0, $mitad );
            $_SESSION ["autentified"] = $serverKey;
            //La otra parte se guarda en la cookie del cliente
            $llaveCliente = substr ( $llaveBase, $mitad, $longitud );
            setcookie ( "sessionKey", $llaveCliente );
            $_COOKIE ["sessionKey"] = $llaveCliente;
            
        }
    }
    
    /**
     * Verifica la seguridad del modulo, si es visible o no al público o por usuario
     * @param String 		$name 		El nombre del modulo
     * @param Sql 			$sql 		Objeto sql
     * @param connection 	$connection La conexion a la base
     * @param int 			$user 		Cuando se necesite verificacion por usuario	 *
     * @return Regresa 1 si es exitoso, 2 Si no se tiene permiso, 3 modulo desactivado,  4 No se ha declarado el modulo, 5 no existe el modulo en el servidor
     *
     */
    public function verifyModuleAccess($module, $mainObj, $user = false)
    {
        
        
        //Verificamos que exista el módulo
        if (file_exists ( SITECLASESPATH . "$module.php" ))
        {
            $tabla = "SYS_DETALLE_PERFIL";
            $condition = array ("CVE_PERFIL" => $this->decryptVariable ( 2, "cvePerfil" ), "CLASE" => $module);
            $fields = array ("VISUALIZAR" );
            $sqlResult = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, false, $condition );
            
            $sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
            
            //Si tenemos resultados
            if ($sqlResult)
            {
                
                $sqlResult = $sqlResult [0];
                $sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
                
                // Si tenemos permiso de visualización
                if ($sqlResult ["VISUALIZAR"] == 1)
                {
                    return true;
                }
                //Si no tenemos permisos de visualización, avisamos y regresamos false evitando el acceso
                else
                {
                    $this->setError ( 5, __CLASS__ . '::' . __FUNCTION__ . " , Modulo :: " . MODULENAME . ".php",__CLASS__, 2 );
                    return false;
                }
            }
            //Si no existen resultado para ese módulo, avisamos y regresamos false evitando el acceso
            else
            {
                $this->setError ( 4, __CLASS__ . '::' . __FUNCTION__ . " , Modulo :: " . MODULENAME . ".php",__CLASS__, 2 );
                return false;
            }
        }
        
        //Si no encontramos la clase, avisamos y regresamos false evitando el acceso
        else
        {
            $this->setError ( 2, "", __CLASS__ . '::' . __FUNCTION__ . " , Módulo :: " . MODULENAME, __CLASS__ );
            return false;
        }
        return false;
    }
    
    /**
     * 
     * Varifica la seguridad de las clases a mostrar	 
     * @param Object $mainObj 	Objeto principal del sistema
     * @param String $name 		El nombre del modulo
     * @return Regresa 1 si es exitoso, 2 Si no se tiene permiso, 3 modulo desactivado,  4 No se ha declarado el modulo, 5 no existe el modulo en el servidor
     */
    public function verifyModelAccess($mainObj, $module)
    {
        //
        //Verificamos que exista el módulo	    
        if (file_exists ( SITEMODELPATH . "$module.php" ))
        {
            $tabla = "SYS_DETALLE_PERFIL";
            $condition = array ("CVE_PERFIL" => $this->decryptVariable ( 2, "cvePerfil" ), "CLASE" => $module );
            $fields = array ("VISUALIZAR" );
            $sqlResult = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, false, $condition );
            
            //Si tenemos resultados
            if ($sqlResult)
            {
                
                $sqlResult = $sqlResult [0];
                $sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
                // Si tenemos permiso de visualización
                if ($sqlResult ["VISUALIZAR"] == 1)
                {
                    return true;
                }
                //Si no tenemos permisos de visualización, avisamos y regresamos false evitando el acceso
                else
                {
                    $this->setError ( 5, __FUNCTION__ . " , Módulo :: " . $module . ".php", __CLASS__, 2 );
                    return false;
                }
            }
            //Si no existen resultado para ese módulo, avisamos y regresamos false evitando el acceso
            else
            {
                $this->setError ( 4, $module . ".php", __CLASS__, 2 );
                echo "false";
                return false;
            }
        }
        
        //Si no encontramos la clase, avisamos y regresamos false evitando el acceso
        else
        {
            $this->setError ( 6, __FUNCTION__ . " , Módulo :: " . $module, $this->className );
            return false;
        }
        return false;
    }
    
    /**
     * Encargada de verificar los permisos que tiene asignados el módulo
     * @param String	$module 	Modulo a verificar
     * @param Object	$mainObj	Objeto con las principales funciones del sistema
     */
    public function verifyAction($module, $mainObj)
    {
        
        $actionResult = false;
        $condition = array ("CVE_PERFIL" => $mainObj->cvePerfil, "CLASE" => $module );
        
        $fields = array ("INSERTAR", "ACTUALIZAR", "BORRAR" );
        
        $tabla = "SYS_DETALLE_PERFIL";
        $sqlResult = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, $fields, $condition );
        
        if ($sqlResult)
        {
            $sqlResult = $sqlResult [0];
            $sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
            
            foreach ( $sqlResult as $key => $value )
            {
                $flag = false;
                if ($value == 1)
                {
                    $flag = true;
                }
                $actionResult [$key] = $flag;
            }
        }
        
        return $actionResult;
    
    }
    
    /**
     * 
     * Función que verifica la validez de la sesión, tenemos
     * por default 15 min de duración, si excede este tiempo 
     * sin usar el sistema, lo desconectará automaticamente.
     */
 public function verifySession($mainObj, $tipo)
    {
     
    	if (isset ( $_SESSION ["autentified"] ))
    	{

    		//La ultima actividad guardada
    		$fechaGuardada = $_SESSION["activity"];
    		    		
    		//La fecha de la consulta
    		$fechaActual = date("Y-m-d H:i:s");
    		
    		$minutosTranscuridos = "";
    		
    		$fechaGuardadaAux = date_create($fechaGuardada);
    		$fechaActualAux = date_create($fechaActual);
    		$interval = date_diff($fechaGuardadaAux, $fechaActualAux);
    		
    		$hora=$interval->format('%H');
    		$min=$interval->format('%i');
    		$sec=$interval->format('%s');

    		//Si el intervalo en minutos es mayor al definido, forzo el logout
    		
    		
    		if($min >= 60|| intval($hora) >= 1 )
    		{    			

    			header ( "Location:?mod=logout&action=2" );
    		}
    		
    		//Si no, actualizo la fecha de ultima actividad
    		else 
    		{
    			
    			$navegador = md5 ( $_SERVER ['HTTP_USER_AGENT'] );
    			$remoteAddr = md5 ( $_SERVER ['REMOTE_ADDR'] );
    			
    			if (($navegador != $_SESSION ["fingerPrint"]) || ($remoteAddr != $_SESSION ["remoteAddr"]))
    			{
    				header ( "Location: ?mod=logout&action=2" );
    			}
    			
    			//error_log("Fecha actual $fechaActual");
    			// "Se está usando el mismo navegador y la misma ip, continuo con la sesion";    			
    			$_SESSION["activity"] = $fechaActual;
    			
    			$tabla = "SYS_USUARIOS";
    			
    			if($tipo == 1)
    			{    				
    					$tabla = "SITE_USUARIOS";
    			}
    			
    			
    			
    			//Cada que pasa y es valido, lo tomamos como una actividad y se guarda
    			$cveUsuario = $this->decryptVariable ( 2, "cveUsuario" );
    			//
    			$driver = $this->getSqlDriver($mainObj->connection);
    			if($driver == "sqlsrv")
    			{
    				$fechaActual = str_replace("-", "", $fechaActual);    				
    			}
    			
    			$datos = array("LAST_ACTIVITY" =>  $fechaActual);
    			$keyFields = array("CVE_USUARIO" => $cveUsuario);
    			$consulta =  $mainObj->sql->updateData($mainObj->connection, $tabla, $datos, $keyFields);
    			
    			
    			
    			/*$condition = array ("CVE_PERFIL" => $mainObj->cvePerfil, "CLASE" => $module );
    			
    			$fields = array ("INSERTAR", "ACTUALIZAR", "BORRAR" );
    			
    			$tabla = "SYS_DETALLE_PERFIL";
    			$sqlResult = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, $fields, $condition );
    			
    			if ($sqlResult)
    			{
    			    $sqlResult = $sqlResult [0];
    			}
    			*/
    			//10-0
    			//$userSatatus = $this->mainObj->system->getIdValue($this->mainObj,"SITE_USUARIOS", "CVE_USUARIO", $cveUsuario, "STATUS", false);
    			//if($userSatatus == )
    			$condition = array ("CVE_USUARIO" => $cveUsuario);    			
    			$fields = array ("STATUS");    			
    			$tabla = "SITE_USUARIOS";
    			$sqlResult = $mainObj->sql->executeQuery ( $mainObj->connection, $tabla, $fields, $condition );
    			
    			if ($sqlResult)
    			{
    			    $sqlResult = $sqlResult [0];
    			    $status = $sqlResult["STATUS"];
    			    if($status == 0 || $status == 10)
    			    {
    			        header ( "Location: ?mod=logout" );
    			        
    			    }
    			    
    			}
    			
    			
    		}
    	}
    	return true;
    }
    

/**
     * Función que encripta una variable, genera las llaves en base a 
     * autentified y a la cookie, genera un iv dinamico, si no existe
     * lo crea si no toma el que esta guardado
     * @param Integer   $tipoVariable       Tipo de la variable a encriptar 1.- normal, 2.- $_SESSION, 3.- $_COOKIE; 
     * @param String    $sessionVariable    Nombre de la variable a encriptar
     * @param String    $variable           Datos que se encriptarán
     * las 1 regresa un valor, las otras 2 las asigna a variables de php. Por default es 1
     */
    public static function encryptVariable($tipoVariable, $sessionVariable, $variable)
    {
        //Si no mandamos el tipo de variable por default pone que es una variable normal
        if (! $tipoVariable)
        {
            $tipoVariable = 1;
        }
        
        if (! isset ( $_SESSION ["autentified"] ))
        {
            return "";
        }
        
     


        if(!isset($_SESSION ["autentified"]) || !isset($_COOKIE ["sessionKey"]) )
        {
            //Se usa directo ya que como no tiene sesion, no puede usar los objetos
            if(SYSDEBUG)
            {
                error_log("No se puede encriptar la variable $sessionVariable");
            }
                        
          return null;
            
        }

        $cipher = 'AES-128-CBC';
        $cryptKey = $_SESSION ["autentified"] . $_COOKIE ["sessionKey"];

        $dato = null;
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($variable, $cipher, $cryptKey, OPENSSL_RAW_DATA, $iv);

            $dato =    base64_encode($iv.$ciphertext_raw);
        }


       // error_log("dato :: $dato");
         switch ($tipoVariable)
        {
            case 1 :
                return  $dato ;
                break;
            case 2 :
                $_SESSION [$sessionVariable] = $dato;
                break;
            case 3 :
                $_COOKIE [$sessionVariable] = $dato;
                break;
        }
        
        
    }


    /**
     * Función encargada de desencriptar variables, recoge las llaves de la 
     * sesión y la cookie, toma el iv y desencripta
     * @param String $sessionVariable Nombre de la variable a ser desencriptada
     */    
    public static function decryptVariable($tipoVariable, $sessionVariable)
    {
        
        if (! $tipoVariable)
        {
            $tipoVariable = 1;
        }
        
        $sesVar = null;
        switch ($tipoVariable)
        {
            case 1 :
                $sesVar = $sessionVariable;
                break;
            case 2 :
                $sesVar = $_SESSION [$sessionVariable];                
                break;
            case 3 :
                $sesVar = $_COOKIE [$sessionVariable];
                break;
        }

        if(!isset($_SESSION ["autentified"]) || !isset($_COOKIE ["sessionKey"]) )
        {
            //Se usa directo ya que como no tiene sesion, no puede usar los objetos
            if(SYSDEBUG)
            {
                error_log("No se puede desencriptar la variable $sessionVariable");
            }
                        
          return null;
            
        }


        $c = base64_decode($sesVar);
        
        $cipher = 'AES-128-CBC';
        $cryptKey = $_SESSION ["autentified"] . $_COOKIE ["sessionKey"];

        $dto = null;
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = substr($c, 0, $ivlen);
            $data = substr($c, $ivlen);
            $dato = openssl_decrypt($data, $cipher, $cryptKey, OPENSSL_RAW_DATA, $iv);
           
        }

        return $dato;
    }

    /**
     * Función que encripta una variable, genera las llaves en base a 
     * autentified y a la cookie, genera un iv dinamico, si no existe
     * lo crea si no toma el que esta guardado
     * @param Integer	$tipoVariable 		Tipo de la variable a encriptar 1.- normal, 2.- $_SESSION, 3.- $_COOKIE; 
     * @param String 	$sessionVariable 	Nombre de la variable a encriptar
     * @param String 	$variable 			Datos que se encriptarán
     * las 1 regresa un valor, las otras 2 las asigna a variables de php. Por default es 1
     */
   /* public function encryptVariable($tipoVariable, $sessionVariable, $variable)
    {
        //Si no mandamos el tipo de variable por default pone que es una variable normal
        if (! $tipoVariable)
        {
            $tipoVariable = 1;
        }
        
        if (! isset ( $_SESSION ["autentified"] ))
        {
            return "";
        }
        
        $llaveDoble = md5 ( $_SESSION ["autentified"] . $_COOKIE ["sessionKey"] );
        
        $cifrador = mcrypt_module_open ( MCRYPT_DES, "", MCRYPT_MODE_CBC, "" );
        
        $maxVectorSize = mcrypt_enc_get_iv_size ( $cifrador );
        
        if (isset ( $_SESSION ["iv"] ))
        {
            $vectorInicio = base64_decode ( $_SESSION ["iv"] );
        }
        else
        {
            $vectorInicio = mcrypt_create_iv ( $maxVectorSize, MCRYPT_RAND );
            $_SESSION ["iv"] = base64_encode ( $vectorInicio );
        }
        
        $maxKeySize = mcrypt_enc_get_key_size ( $cifrador );
        $llave = substr ( $llaveDoble, 0, $maxKeySize );
        mcrypt_generic_init ( $cifrador, $llave, $vectorInicio );
        $dato = mcrypt_generic ( $cifrador, $variable );
        
        mcrypt_generic_deinit ( $cifrador );
        mcrypt_module_close ( $cifrador );
        
        switch ($tipoVariable)
        {
            case 1 :
                return base64_encode ( $dato );
                break;
            case 2 :
                $_SESSION [$sessionVariable] = base64_encode ( $dato );
                break;
            case 3 :
                $_COOKIE [$sessionVariable] = base64_encode ( $dato );
                break;
        }
    }*/
    
    /**
     * 
     * Función encargada de desencriptar variables, recoge las llaves de la 
     * sesión y la cookie, toma el iv y desencripta
     * @param String $sessionVariable Nombre de la variable a ser desencriptada
     */
    
   /* public static function decryptVariable($tipoVariable, $sessionVariable)
    {
        
        if (! $tipoVariable)
        {
            $tipoVariable = 1;
        }
        
        $sesVar = null;
        switch ($tipoVariable)
        {
            case 1 :
                $sesVar = $sessionVariable;
                break;
            case 2 :
                $sesVar = $_SESSION [$sessionVariable];                
                break;
            case 3 :
                $sesVar = $_COOKIE [$sessionVariable];
                break;
        }
        
        $dato = base64_decode ( $sesVar );
        if(!isset($_SESSION ["autentified"]) || !isset($_COOKIE ["sessionKey"]) )
        {
        	//Se usa directo ya que como no tiene sesion, no uede usar los objetos
        	error_log("No existen llaves váldas para la sesion ".__FUNCTION__." :: ".__CLASS__);            
          return null;
            
        }
        $llaveDoble = md5 ( $_SESSION ["autentified"] . $_COOKIE ["sessionKey"] );
        
        
        //Abrimos cifrador, IV, llave e inicializamos cifrador de la
        //misma forma que en la parte de cifrado (ver allí comentarios)        
        $cifrador = mcrypt_module_open ( MCRYPT_DES, "", MCRYPT_MODE_CBC, "" );
        $maxVectorSize = mcrypt_enc_get_iv_size ( $cifrador );
        $vectorInicio = base64_decode ( $_SESSION ["iv"] );
        $maxKeySize = mcrypt_enc_get_key_size ( $cifrador );
        $llave = substr ( $llaveDoble, 0, $maxKeySize );
        
        mcrypt_generic_init ( $cifrador, $llave, $vectorInicio );
        
        if (! $dato || ! $cifrador)
        {
            error_log ( "No trae valores el campo :: $sessionVariable" );
            return "";
        }
        
        $dato = mdecrypt_generic ( $cifrador, $dato );
        
        //Quitamos los nulos de relleno de la derecha
        $dato = rtrim ( $dato, "\0" );
        //Finalizamos cifrador y lo cerramos
        mcrypt_generic_deinit ( $cifrador );
        mcrypt_module_close ( $cifrador );
        return $dato;
    
    }*/
}
?>