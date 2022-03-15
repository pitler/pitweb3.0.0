<?php
namespace Pitweb;
use Pitweb\Connection as PwConnection;
use Pitweb\Sql as PwSql;
use Pitweb\Funciones as PwFunciones;

/**
 * Clase encargada de la seguridad del sistema
 * Crea y verifica sesiones
 * Verifica acceso a los módulos
 * Verifica las acciones que se pueden hacer por módulo
 * Encripta y desencripta variables
 * @author pitler
 *
 */
class Security 
{
    

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

        $cipher = 'aes-128-cbc';
        //$cipher = 'AES-128-CBC';
        
        $cryptKey = $_SESSION ["autentified"] . $_COOKIE ["sessionKey"];

        $dato = null;
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($variable, $cipher, $cryptKey, OPENSSL_RAW_DATA, $iv);

            $dato =    base64_encode($iv.$ciphertext_raw);
            
        }


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
        
        $cipher = 'aes-128-cbc';
        //'AES-128-CBC';
        $cryptKey = $_SESSION ["autentified"] . $_COOKIE ["sessionKey"];

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
     * Genera las llaves usadas para la sesión
     * Estas llaves son unicas por sesión
     * Una parte se guarda en la cookie y la otra en la sesión
     */
    
    public static function createSessionKey()
    {
        //Comprobamos si la sesión ya fue iniciada
        $existeSesion = isset ( $_SESSION ["autentified"] );


        
        //Si no existe la sesion
        if (! $existeSesion)
        {

            //Generamos una llave unica         
            //$llaveBase = md5 ( uniqid ( rand (), true ) );
            $llaveBase = hash ("sha256", uniqid ( rand (), true ) );

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
     * Encargada de verificar los permisos que tiene asignados cada clase
     * Los permisos de la clase con los mismos para un controlador
     * @param String    $module     Clase a verificar
     * @param String    $cvePerfil  Perfil del usuario conectado
     * @param Object    $connection Conexión a la base, debe de ser la principal
     */
    public static function validateAccess($module, $cvePerfil)
    {
        $connection = PwConnection::getInstance()->connection;
        
        $result = array("VISUALIZAR" => false, "INSERTAR" => false, "ACTUALIZAR" => false, "BORRAR" => false);

        if(!$module || !$cvePerfil || !$connection)
        {
            PwFunciones::setLogError(61, $module);
            return $result;
        }
        
        $condition = array ("CVE_PERFIL" => $cvePerfil, "CLASE" => $module );
        $fields = array ("VISUALIZAR","INSERTAR", "ACTUALIZAR", "BORRAR" );        
        $tabla = "FC_SYS_DETALLE_PERFIL";
        $sqlResult = PwSql::executeQuery ($connection, $tabla, $fields, $condition );

        
        if ($sqlResult)
        {
            $sqlResult = $sqlResult [0];
            
            foreach ( $sqlResult as $key => $value )
            {
                if(is_string($key))
                {
                    $result[$key] = $value;
                }
            }
        }
        else
        {
            PwFunciones::setLogError(62, $module);
        }
        
        return $result;
    
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
  /*  public static function verifyModuleAccess($module, $user = false)
    {
        
        $connection = PwConnection::getInstance()->connection;
        //Verificamos que exista el módulo
        
            $tabla = "SYS_DETALLE_PERFIL";
            $condition = array ("CVE_PERFIL" => self::decryptVariable ( 2, "cvePerfil" ), "CLASE" => $module);
            $fields = array ("VISUALIZAR" );
            $sqlResult = PwSql::executeQuery ( $connection, $tabla, false, $condition );
            
            //$sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
            
            //Si tenemos resultados
            if ($sqlResult)
            {
                
                $sqlResult = $sqlResult [0];
              //  $sqlResult = $this->getArrayObject ( $mainObj->conId, $sqlResult );
                
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
                PwFunciones::setLogError ( 4, __CLASS__ . '::' . __FUNCTION__ . " , Modulo :: " . MODULENAME . ".php",__CLASS__, 2 );
                return false;
            }
     //   }
        
        //Si no encontramos la clase, avisamos y regresamos false evitando el acceso
      
        return false;
    }*/
    
    /**
     * 
     * Varifica la seguridad de las clases a mostrar	 
     * @param Object $mainObj 	Objeto principal del sistema
     * @param String $name 		El nombre del modulo
     * @return Regresa 1 si es exitoso, 2 Si no se tiene permiso, 3 modulo desactivado,  4 No se ha declarado el modulo, 5 no existe el modulo en el servidor
     */
  /*  public function verifyModelAccess($mainObj, $module)
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
    */
   

   
    
    /**
     * 
     * Función que verifica la validez de la sesión, tenemos
     * por default 15 min de duración, si excede este tiempo 
     * sin usar el sistema, lo desconectará automaticamente.
     */
 public static function verifySession($tipo= false)
    {

        $connection = PwConnection::getInstance()->connection;
     
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
            $logout = rawurlencode(self::encryptVariable(1, "", "Logout"));

    		//Si el intervalo en minutos es mayor al definido, forzo el logout
    		if($min >= 60|| intval($hora) >= 1 )
    		{    			
    			header ( "Location:?mod=$logout" );
    		}
    		
    		//Si no, actualizo la fecha de ultima actividad
    		else 
    		{
    			
    			
    		  $navegador = hash ("sha256", $_SERVER ['HTTP_USER_AGENT'] );
    		  $remoteAddr = hash ("sha256", $_SERVER ['REMOTE_ADDR'] );
    			
    			
    			
    			if (($navegador != $_SESSION ["fingerPrint"]) || ($remoteAddr != $_SESSION ["remoteAddr"]))
    			{
    				header ( "Location: ?mod=$logout" );
    			}
    			

    			// "Se está usando el mismo navegador y la misma ip, continuo con la sesion";    			
    			$_SESSION["activity"] = $fechaActual;
    			
    			$tabla = "FC_SYS_USUARIOS";
    			/*
    			if($tipo == 1)
    			{    				
    					$tabla = "SITE_USUARIOS";
    			}*/
    			
    			
    			
                //Cada que pasa y es valido, lo tomamos como una actividad y se guarda
                
    			$cveUsuario = self::decryptVariable ( 2, "cveUsuario" );
    			//
    			$driver = PwFunciones::getSqlDriver($connection);
                
    			if($driver == "sqlsrv")
    			{
    				$fechaActual = str_replace("-", "", $fechaActual);    				
    			}

    			
    			$datos = array("LAST_ACTIVITY" =>  $fechaActual);

                if(DBASE == 2)
                {
                    $fechaActual = date("d-m-Y H:i:s");
                    $datos["LAST_ACTIVITY"] = "to_date(?, 'DD-MM-YYYY HH24:MI:SS')//$fechaActual";
                }

    			$keyFields = array("CVE_USUARIO" => $cveUsuario);
    			$consulta =  PwSql::updateData($connection, $tabla, $datos, $keyFields);
    			
    			
    	       //Para el sitio
    		/*if($tipo == 1)
    		{
    		    $condition = array ("CVE_USUARIO" => $cveUsuario);
    		    $fields = array ("STATUS");
    		    $tabla = "SITE_USUARIOS";
    		    $sqlResult = PwSql::executeQuery ( $connection, $tabla, $fields, $condition );
    		  
    		    if ($sqlResult)
    		    {
    		      $sqlResult = $sqlResult [0];
      		    $status = $sqlResult["STATUS"];
      		    if($status == 0 || $status == 10)
      		    {
    	   	      header ( "Location: ?mod=logout" );    		      
    		     }    		    
    		  }
    		}*/
    			
    			
    		}
    	}
    	return true;
    }
    
   
    
    
}
