<?php
namespace Pitweb;

use Pitweb\Sql as PwSql;
use Pitweb\Files as PwFiles;


/**
 * 
 * Clase encargada de las funciones más comunes dentro del sistema
 *
 * @author pcalzada
 * @uses Objeto para las funciones mas usadas dentro del sistema
 * 
 */
class Funciones
{
    
    
    /**
     *
     * Función que manda el var dump de las variables que queramos al log, crece dinámicamente
     * VERIFIED
     */
    public static function getVardumpLog()
    {
        
        $data = "";
        //Tomamos el numero de argumentos que pasamos
        $numArgs = func_num_args () - 1;
        
        //Asignamos los argumentos a una variable
        $argList = func_get_args ();
        
        ob_start ();
        foreach ( $argList as $item )
        {            
            var_dump ( $item );
        }
        
        $data = ob_get_clean ();        
        error_log ( $data );
    }
    
    
    /**
     * 
     * Clase para el reporte de errores en el log de php
     * @param Integer   $errorNum   Número de error para reportar     
     * @param String    $extra      String con lo que queramos mandar junto con el error
     * @param Integer   $tipo		Manda el tipo de error Default error, 2.- Notificación
     */
    //PwUsed
    public static function setLogError($errorNum, $extra = "")
    {
       
        $errorMessage = self::getErrorMessage ( $errorNum );
        $strError = "PWE_($errorNum) :: $errorMessage => $extra";
       

        if(SYSDEBUG == true )
        {
            error_log ( $strError );
        }
        
    }

    /**
     * Lista de errores conocidos en el sistema
     * @param Integer $number Número de error para mostrar
     */
    //PwUsed
    public static function getErrorMessage($errorNumber)
    {
        $errorMsg = "";
        $defaultLang = DEFAULTLANG;

        if(isset($_SESSION["lang"]))
        {
            $defaultLang = $_SESSION["lang"];
        }
        
        if (file_exists ( SITELANGPATH."err-$defaultLang.php" ))
        {
            include_once (SITELANGPATH."err-$defaultLang.php");
            $errorMsg = getError ( $errorNumber );
            return $errorMsg;
        }
        else
        {
            if(SYSDEBUG == true)
            {
                error_log("No existe el diccionario de errores err-".$defaultLang.".php");
            }
        }
    }
    
   
  
    
    /**
     * 
     * Trae una variable enviada por GET, por default hace un strip_tags() para evitar que ingresen codigo html o php
     * 
     * @param String	$varName	  	  Nombre de la variable a buscar
     * @param Boolean	$basicfilter  	  Aplica el filtro basico para reemplazar comillas simples y el signo de #. Por default viene activado
     * @param Boolean	$stripfilter	  Aplica el filtro para evitar el ingreso de tags html y php. Por default está activado
     * @param Array		$personalFilter	  Array con los caracteres no permitidos, debe enviar $personalReplace
     * @param Array		$personalReplace  Array con los caracteres que se van a reemplazar, si no se envia se reemplaza por default con espacio en blanco 
     * 
     */
    public static function getGVariable($varName, $basicfilter = true, $stripfilter = true, $personalFilter = false, $personalReplace = false)
    {
        /**
         * Ejemplos de uso, se envia la variable mode=<div>aaxxaaaaa'</div> por GET
         * $personalFilter = array("x", "'");
         * $personalReplace = array("b", "");
         * $variable = $this->funciones->getGVariable("mode", false, true, $personalFilter, $personalReplace);
         * Regresa:: aabbaaaaa
         */
        
        
        $var = null;
        if (isset ( $_GET [$varName] ))
        {
            $var = $_GET [$varName];
            
            //Quito las etiquetas html y php
            if ($stripfilter == true)
            {
                $var = strip_tags ( $var );
            }
            
            //Se aplica si tiene activado el filtro básico
            if ($basicfilter == true)
            {
                $chars = array ("'", "#" );
                $replace = array ("''", "" );
                $var = str_replace ( $chars, $replace, $var );
            }
            
            // Se activa si se activa el filtro personalizado			
            if ($personalFilter)
            {
                if (is_array ( $personalFilter ))
                {
                    $chars = $personalFilter;
                    $replace = array ("" );
                    
                    if ($personalReplace != null && is_array ( $personalReplace ))
                    {
                        $replace = $personalReplace;
                    }
                    
                    $var = str_replace ( $chars, $replace, $var );
                }
                else
                {
                    self::setLogError(11, $varName);
                }
            }
        }
        /*else
        {   
                self::setLogError(10, $varName);            
        }*/
        
        return $var;
    }
    
    /**
     * 
     * Trae una variable enviada por POST, por default hace un strip_tags() para evitar que ingresen codigo html o php
     * 
     * @param String	$varName	  	  Nombre de la variable a buscar
     * @param Boolean	$basicfilter  	  Aplica el filtro basico para reemplazar comillas simples y el signo de #. Por default viene activado
     * @param Boolean	$stripfilter	  Aplica el filtro para evitar el ingreso de tags html y php. Por default está activado
     * @param Array		$personalFilter	  Array con los caracteres no permitidos, debe enviar $personalReplace
     * @param Array		$personalReplace  Array con los caracteres que se van a reemplazar, si no se envia se reemplaza por default con espacio en blanco 
     * 
     */

    public static function getPVariable($varName, $basicfilter = true, $stripfilter = true, $personalFilter = null, $personalReplace = null)
    {
        /**
         * Ejemplos de uso, se envia la variable mode=<div>aaxxaaaaa'</div> por POST
         * $personalFilter = array("x", "'");
         * $personalReplace = array("b", "");
         * $variable = $this->funciones->getGVariable("mode", false, true, $personalFilter, $personalReplace);
         * Regresa:: aabbaaaaa
         */
        
        $var = null;
        if (isset ( $_POST [$varName] ))
        {
            $var = $_POST [$varName];

            //Quito las etiquetas html y php
            if ($stripfilter == true)
            {
                $var = strip_tags ( $var );
            }
            
            //Se aplica si tiene activado el filtro básico
            if ($basicfilter == true)
            {
                $chars = array ("'", "#" );
                $replace = array ("''", "" );
                $var = str_replace ( $chars, $replace, $var );
            }
            
            if ($personalFilter)
            {
                if (is_array ( $personalFilter ))
                {
                    $chars = $personalFilter;
                    $replace = array ("" );
                    
                    if ($personalReplace != null && is_array ( $personalReplace ))
                    {
                        $replace = $personalReplace;
                    }
                    
                    $var = str_replace ( $chars, $replace, $var );
                } //Descomentar para hacer el debug
                else
                {
                    self::setLogError(13, $varName);
                }
            }
        }
        /*else
        {
            self::setLogError(12, $varName);
        }*/
        return $var;
    }

    /**
     * 
     * Trae una variable enviada por GET, por default hace un strip_tags() para evitar que ingresen codigo html o php
     * 
     * @param String    $varName          Nombre de la variable a buscar
     * @param Boolean   $basicfilter      Aplica el filtro basico para reemplazar comillas simples y el signo de #. Por default viene activado
     * @param Boolean   $stripfilter      Aplica el filtro para evitar el ingreso de tags html y php. Por default está activado
     * @param Array     $personalFilter   Array con los caracteres no permitidos, debe enviar $personalReplace
     * @param Array     $personalReplace  Array con los caracteres que se van a reemplazar, si no se envia se reemplaza por default con espacio en blanco 
     * 
     */
    public static function getVariable($variable, $basicfilter = true, $stripfilter = true, $personalFilter = false, $personalReplace = false)
    {
        /**
         * Ejemplos de uso, se envia la variable mode=<div>aaxxaaaaa'</div> por GET
         * $personalFilter = array("x", "'");
         * $personalReplace = array("b", "");
         * $variable = $this->funciones->getGVariable("mode", false, true, $personalFilter, $personalReplace);
         * Regresa:: aabbaaaaa
         */

        $var = $variable;
            
        self::getVardumpLog($var);
        //Quito las etiquetas html y php
        if ($stripfilter == true)
        {
            $var = strip_tags ( $var );
        }
            
        //Se aplica si tiene activado el filtro básico
        if ($basicfilter == true)
        {
            $chars = array ("'", "#" );
            $replace = array ("''", "" );
            $var = str_replace ( $chars, $replace, $var );
        }
            
        // Se activa si se activa el filtro personalizado           
        if ($personalFilter)
        {
            if (is_array ( $personalFilter ))
            {
                $chars = $personalFilter;
                $replace = array ("" );
                    
                if ($personalReplace != null && is_array ( $personalReplace ))
                {
                    $replace = $personalReplace;
                }
                    
                $var = str_replace ( $chars, $replace, $var );
            }
            else
            {
                self::setLogError(11, $varName);
            }
        }
        return $var;
    }
    

     /**
     * 
     * Función que valida la entrada de datos al sistema por medio de expresiones regulares
     * @param Integer   $id             Id de la validación
     * @param String    $variable       Variable a verificar
     */
    public static function validateData($variable, $id = 1)
    {
        
        $result = false;
        switch ($id)
        {
            //Valida que contenga solo numeros y letras
            case 1 :
                $result = preg_match ( '/^[a-zA-Z0-9]*$/', $variable );
                break;
        }
        return $result;
    }




     /**
     * Función que valida que existan los repositorios del PitWeb
     * Si se manda la bandera para desactivar los borra, si no los activa en automático
     * VERIFIED
     * 
     * @param String	$repositoryFlag		Bandera para activar o desactivar
     */
     public static function validaRepositorios($repositoryFlag)
     {
     
        
         //Eliminamos los links    	
         if($repositoryFlag == "stop")
         {
            
            unlink("repository");            
            unlink("pw");            
             
         }
         else
         {
            
             if(!is_link("repository"))
             {
            
                 if(!is_dir(PITWEB."repository/".SITEID))
                 {
                     PwFiles::createPath(array(PITWEB."repository/",SITEID));
                 }
                 symlink(PWSREPOSITORY,"repository");
             }                          
             
             if(!is_link("pw"))
             {
                 symlink(PITWEB,"pw");
             }
         }
     }
    


 /**
     * 
     * Funcion que formatea el resultado para los select $key=>$value
     * @param Integer   $id             Id de la validación
     * @param String    $variable       Variable a verificar
     */
    public static function getArrayFromSql($valArray, $id, $field)
    {
        $arrResult = null;

     
        
        foreach ($valArray as $valItem) 
        {
            $arrResult[$valItem[$id]] = $valItem[$field];            
        }

      //  self::getVardumpLog($arrResult);
        return $arrResult;


    }


   

     /**
     * FUNCIÓN QUE REGRESA LOS ITEMS DE UN SQLRESULT ASIGNADOA A UN TEMPLATE
     * VERIFIED
     * @param Array     $sqlResults     Array con los resultados de la consulta
     * @param String    $tempItem       Contiene el template a usar
     * @param Array     $tempParams     Array con los valores a sustituir VALORTEMP => VALORBASE
     * @return String   $resultData     Regresa el String generado o vacio
     */
    public static function getBasicTemplate($sqlResults, $tempItem, $tempParams)
    {
        $resultData = "";
    
        foreach ($sqlResults as $sqlItem)
        {
            $tempAux = $tempItem;
            foreach($tempParams as $key => $item)
            {
                $tempAux = preg_replace("/__".$key."__/", $sqlItem[$item], $tempAux);
            }
            $resultData .= $tempAux;
        }
        return $resultData;
    }


     /**      
     * Función que nos regresa el número máximo  de un campo + 1 para talbas del sistema
     * Sirve para llevar control de consecutivos
     * El campo al que haga referencia debe de ser un campo numérico
     * @param Object    $mainObj    Objeto principal del sistema    
     * @param String    $tabla      Tabla a la que se hace la consulta
     * @param String    $field      Campo a revisar
     * @param Integer $inicio       Número del cual empezaría el consecutivo si no regresa nada la consulta (No existe el campo)
     */
    public static function getConsecutivo($connection, $tabla, $field, $inicio = 0)
    {
        //Por default es 1
        $consecutivo=$inicio;

        $consulta = "SELECT MAX($field) AS $field FROM $tabla ";
        
        $params = null;
        $ps = PwSql::setSimpleQuery ( $connection, $consulta );
        
        $sqlResults = PwSql::executeSimpleQuery ( $ps, $params, $consulta );
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

    /**      
     * Función que nos regresa el número máximo  de un campo + 1 para talbas del sistema
     * Sirve para llevar control de consecutivos
     * El campo al que haga referencia debe de ser un campo numérico
     * @param Object    $connection Conexión a la base
     * @param String    $tabla      Tabla a la que se hace la consulta
     * @param String    $field      Campo a revisar
     * @param Integer $inicio       Número del cual empezaría el consecutivo si no regresa nada la consulta (No existe el campo)
     */
     public static function getImageConsecutivo($connection, $tabla, $field, $ruta, $seccion, $inicio = 0)
     {
         //Por default es 1
         $consecutivo=$inicio;
 
         $consulta = "SELECT MAX($field) AS $field FROM $tabla 
         WHERE RUTA = ? AND ID_SECCION = ?";
         
         $params = array($ruta, $seccion);
         $ps = PwSql::setSimpleQuery ( $connection, $consulta );
         
         $sqlResults =  PwSql::executeSimpleQuery ( $ps, $params, $consulta );
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
    


    /*
    * @param String $extra Nombre del mólulo al que queremos ir
     */
    public static function reloadPage($extra = false)
    {
        echo header ( "Location:/?mod=$extra" );
    }

    /*
    * @param String $extra Nombre del mólulo al que queremos ir
     */
    public static function reloadPageN($extra = false)
    {
        echo header ( "Location:/$extra" );
    }


    /**
     * Funcion que trae el driver usad por la conexion
     * @param  
     * @return [type]
     */
   public static  function getSqlDriver($connection)
    {
        $result = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return $result;
    }
    
    /**
	 * Función que valida que no existan llaves repetidas al hacer insert
     * True si no existe, false si si
	 * @param Object	$mainObj		Objeto principal
	 * @param Array		$condition		Array con las condiciones para el query en formato CAMPO=>valor
	 * @param Array		$params			Array con los parámetros
	 * @param String	$tabla			Nombre de la tabla en donde buscamos
	 * @param String	$clase			Nombre de la clase que intenta insertar
	 */
	public static function validaInsert($connection, $condition, $tabla, $clase = null , $debug = false)
	{
	    
	    $result = false;
        $sqlResults = PwSql::executeQuery ( $connection, $tabla, null, $condition, false, false, false, $debug);	
        //self::getVardumpLog($sqlResults)  ;
	    if (sizeof ( $sqlResults ) == 0)
	    {
	        $result = true;
        }
	    else
        {   if($debug == true)
            {
                self::setLogError(52);
            }
	        
	    }
	    
	    return $result;
    }
    
    /**
     * Genera cadenas con cntenido aleatorio del tamao que indiquen
     */
    public static function generaCode($longitud)
  	{
	   
       $cadena="[^A-Z0-9]"; 
       return substr(str_replace ($cadena, "", md5(rand())) . 
       str_replace ($cadena, "", md5(rand())) . 
       str_replace ($cadena, "", md5(rand())), 
       0, $longitud); 
	} 
    

    /**
	 * Trae un registro de una tabla  con el valor $key=> $value
	 * @param String $key		Valor llave de la tabla
	 * @param String $value	Campo con el texto a regresar
	 */
	public static function getIdValue($connection,$table, $idField, $idValue, $field)
	{
		
		$fields = array($field);
		$condition = array($idField => $idValue);
		
		$sqlResults = PwSql::executeQuery($connection, $table, $fields,  $condition);
		
		if($sqlResults)
		{
			$sqlItem = $sqlResults[0];
			$value = $sqlItem[$field];
			return $value;
		}
		else
		{            
            self::setLogError(42);
		}
		
		return false;
    }

    /**
	 * 
	 * Trae uno o mas parametros del sistema	 * 
	 * @param Object	$mainObj		Objeto principal del sistema	 
	 * @param Array		$fields			Array con los cmapos a regresar
	 * @param	Array		$condition	Array con las condiciones
	 */
	 public static function getSiteParams($connection, $condition = null, $fields = null)
	 {
		 $data = null;
		 $sqlResults = PwSql::executeQuery($connection, "SITE_PARAMS", $fields,  $condition);				
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
            self::setLogError(42);
		 }
		 
		 return $data;
	 }
    

    
	/**
	 *
	 * Valida si la página necesita una sesión activa o no
	 * @param Object	$mainObj		Objeto principal del sistema
	 * @param String	$nombre			Nombre de la página a revisar
	 * @param	Array		$condition	Array con las condiciones
	 */
	public static function requiereLogin($connection, $nombre)
	{
		$result = false;
		$fields = array("REQUIERE_LOGIN");
        $condition = array("CLASE" => $nombre);
        
		$sqlResults = PwSql::executeQuery($connection, "SITE_MENU", $fields,  $condition);
		if($sqlResults)
		{
			$sqlItem = $sqlResults[0];
			$requiereLogin = $sqlItem["REQUIERE_LOGIN"];
			if($requiereLogin == 1)
			{
				$result = true;
			}
		}
		return $result;
    }
    
    public static function eliminaAcentos($cadena){
        
        //error_log("Elimino acentos para $cadena");
        $no_permitidas= array (" ", "á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
        $permitidas= array ("", "a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
        $texto = str_replace($no_permitidas, $permitidas ,$cadena);
        
        return $texto;
        
        
    }



    /**	  
	 * Función que se encarga de cargar los idiomas para el sistema
	 * Por default toma el idioma del navegador, y lo guarda en la variable de sesion "lang"
	 * Si se cambia el idioma, esa variable se sustituye.
	 * Si el usuario se loguea, toma el idioma guardado en la base en la tabla FL_USUARIOS y lo pone en la sesión
	 * Si esta logueado y cambia de idioma, se actualiza en la tabla el nuevo idioma y en la sesión
	 * Por default el idioma es el diccionario en ESPAÑOL en la carpeta /lang/es.php
	 * @return unknown
	 */
	public static function getLangLabels()
	{
		$arrLangs = array("ES", "EN");
		//Idioma por default
		$defaultLang = DEFAULTLANG;
		$sessionLang = $_SESSION["lang"];	
		//$langAux = $sessionLang;

		//Checamos la variable por si se cambió el idioma
		$lang = self::getGVariable("lang");
		
		
		//error_log("Voy por lenguaje");
		//Si si se cambio y es diferente de algo nulo
		if($lang && $lang != $sessionLang)
		{	
			$_SESSION["lang"] = $lang;
		//	$langAux = $lang;
			if(file_exists("lang/$lang.php"))
			{
				//Cambiamos la variable de sesión
				$_SESSION["lang"] = $lang;
				$sessionLang = $lang;
				
				// Si está autenticado, también actualizamos la información
				// del usuario en la tabla con el nuevo idioma
				/*if(isset($_SESSION["autentified"]))
				{
					$datos = array("LANG" =>$sessionLang);
					$keyFields = array("CVE_USUARIO" => $this->security->decryptVariable(2, "cveUsuario"));
					$tabla = "";
					
					switch (SITEMODE)
					{
						case "site" :
								$tabla = "SITE_USUARIOS";
								break;
						case "admin" : 
							$tabla = "SYS_USUARIOS";
							break;
						default :
							$tabla = "";
							break;
					
					}
					$this->sql->updateData($this->connection, $tabla, $datos, $keyFields);
				}*/
			}	
			else
			{
				self::getErrorMessage(53);
			}		
		}	
        //error_log("Icluyo idiona $sessionLang");
        $sessionLang = strtolower($sessionLang);

		include_once ("lang/$sessionLang.php");
		return $arrLabels;
	
    }
    
    public static function getLang()
    {

        $lang = "";
        if(isset($_SESSION["lang"]) &&  $_SESSION["lang"] != "ES")
		{
            $lang = "_".$_SESSION["lang"];     
        }  
            
        return $lang;
    }
  
    
    private function getTemplate($name)
    {
        
        $template ["error"] = <<< TEMP
	<div id = "rmsg">
		<p>
		<table  align = "center">
    		<tr>
    			<td class = "filterSpace">&nbsp;</td>
    			<td class = "filterItem"  align = "center">
    				__MENSAJE__<a href= "?mod=__CLASE__" class = "llink"> Regresar</a>
    			</td>				
    		</tr>
		</table>
		</p>
	</div>		
TEMP;
        
        $template ["success"] = <<< TEMP
	  <div id = "rmsg">
		<p>
		<table  align = "center">
    		<tr>
    			<td class = "filterSpace">&nbsp;</td>
    			<td class = "filterItem"  align = "center">
    				__MENSAJE__<a href= "?mod=__CLASE__" class = "llink"> Regresar</a>
    			</td>				
    		</tr>
		</table>
		</p>
	</div>	

TEMP;
        
  

}
}


?>