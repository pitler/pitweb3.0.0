<?php
namespace Pitweb\PwFunciones;


/**
 * 
 * Clase encargada de las funciones más comunes dentro del sistema
 *
 * @author pcalzada
 * @uses Objeto para las funciones mas usadas dentro del sistema
 * 
 */
class PwFunciones
{
    
   // private static $instance = null;
    
    private function __construct()
    {
    
       // echo "1";
    }
    
    /**
     * Metodo que se encarga de instanciar la libreria para el sitio en ejecución
     *
     * @param  String 	$className 	Nombre de la clase a instanciar, es el único parámetro necesario
     * @param  Object 	$obj1 		Por lo general es la conexión a la base para usarse cuando se necesite
     * @param  Object 	$obj2 		Por lo general la conexión a la base si se necesita
     * @param  Object 	$obj3 		Por lo general el objeto sql que se va a usar
     * @param  Integer 	$obj4		Por lo general es el identificador de la conexión 
     * @return Regresa El objeto de la clase instanciado
     */
    //PwUsed
    public static function getClass()
    {
        
        //Tomamos el numero de argumentos que pasamos
        $numArgs = func_num_args () - 1;
        
        //Asignamos los argumentos a una variable
        $argList = func_get_args ();
        
        //Se asigna el primer argumento como nombre de la clase y luego se borra
        $className = $argList [0];
        $classNameAux = $className;
        
        // Ruta por defualt donde busca las clases
        $classPath = PWSYSLIB;
        
        //Revisamos si el segundo parámetro es una ruta y esta existe
        //Si si, cambiamos la ruta donde busca los archivos a cargar
        //Sirve para cargar las librerías de forma local (admin, sitio, pitweb)
        if(isset($argList[1]) && is_string($argList[1]))
        {
            if(is_dir($argList[1]))
            {
                $classPath = $argList[1];
            }
        }
        
        //Se checa si el archivo está en alguna subcarpeta (Solo acepta 1 nivel abajo)
        //Si si está, se toma el nombre del último elemento del array 
        
        if (! stripos ( $classNameAux, "/" ) === false)
        {
            $classNameAux = explode ( "/", $classNameAux );
            $classNameAux = array_pop ( $classNameAux );
        }
        
        unset ( $argList [0] );
        $obj = null;
        
        //Si no existe el nombre de la clase o si es vacio
        if (! $classNameAux || trim ( $classNameAux ) == "")
        {
            self::setLogError(1, __CLASS__, $classNameAux);
            return null;
        }
        
        //Si no existe fisicamente la clase solicitada
        if (! file_exists ( $classPath . $className . ".php" ))
        {    
            self::setLogError(2, __CLASS__, $className);            
            return null;
        }
        
        //Se incluye la clase con la que vamos a trabajar
        //error_log("Aqui llego fin :: $className :: ".include_once ($classPath . $className . ".php"));
        include_once ($classPath . $className . ".php");
        
        //Instanciamos sin importar el número de parámetros
        if ($numArgs > 0)
        {
            //Creamos el objeto por medio de una ReflectionClass	  	
            try
            {
                $class = new ReflectionClass ( $classNameAux );
                $obj = $class->newInstanceArgs ( $argList );
            }
            catch ( Exception $e )
            {
                self::setLogError(3, __CLASS__, $classNameAux);
            }
        } //Si solo se intancia con el nombre de la clase
        else
        {
        	$obj = new $classNameAux();  
        }
        
        return $obj;
    }
    
    
    
    
    
    /**
     *
     * Función que manda el var dump de las variables que queramos al log, crece dinámicamente
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
     * @param Integer $errorNum Número de error para reportar
     * @param String	$class		Clase de donde se genera el error
     * @param String    $extra      String con lo que queramos mandar junto con el error
     * @param Integer $tipo			Manda el tipo de error Default error, 2.- Notificación
     */
    //PwUsed
    public static function setLogError($errorNum, $clase = "", $extra = "")
    {
       
        $errorMessage = self::getErrorMessage ( $errorNum );
        $strError = "PW :: $errorNum ($clase) :: $errorMessage => $extra";
        if(SYSDEBUG == true)
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
     * Lista de errores conocidos en el sistema
     * @param Integer $number Número de error para mostrar
     */
    /*	public function getLabel($label)
	{	
		
		$text = "";
		$defaultLang = "es";
		$lang = $_SESSION["lang"];
		
		if (file_exists("lang/$lang.php"))
		{
			include_once ("lang/$lang.php");
			
		}
		else
		{
			include_once ("lang/$defaultLang.php");
		}
		
		$text = getLbl($label);
		return $text;
		
		
		
	}*/
    
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
    //PwUsed
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
                    self::setLogError(11, "",$varName);
                }
            }
        }
        else
        {   
            self::setLogError(10, "", $varName);
        }
        
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
    //PwUsed
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
        //    $this->getVardumpLog($var);
            
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
                    self::setLogError(13, "", $varName);
                }
            }
        }
        else
        {
            self::setLogError(12, "", $varName);
        }
        return $var;
    }
    
    
    /**
     * Lista de errores conocidos en el sistema
     * @param Integer $number Número de error para mostrar
     */
    public function getLabel($labelNumber)
    {
        $errorMsg = "";
        $defaultLang = "es";
        $lang = $_SESSION ["lang"];
        
        if (file_exists ( SITELANGPATH."label-$lang.php" ))
        {
            include_once (SITELANGPATH."label-$lang.php");
        }
        else
        {
            include_once (SITELANGPATH."label-$defaultLang.php");
        }
        
        $errorMsg = getError ( $errorNumber );
        return $errorMsg;
        
    }
    
    /**
     * Función que valida que existan los repositorios del PitWeb
     * Si se manda la bandera para desactivar los borra, si no los activa en automático
     *
     * @param String	$repositoryFlag		Bandera para activar o desactivar
     */
    public function validaRepositorios($repositoryFlag)
    {
        
        /*error_log("Dir : " .PWSREPOSITORY);
         error_log("Dir 2 : ".PWSYSUTILS);
         error_log("repository");*/
        
        if($repositoryFlag == "stop")
        {
            if( is_dir("repository"))
            {
                unlink("repository");
            }
            if( is_dir("pwutils"))
            {
                unlink("pwutils");
            }
        }
        else
        {
            $target = readlink("repository");
            //error_log("Tar : $target");
            //if($target != str_replace("/", "\\",PWSREPOSITORY))
            if($target != PWSREPOSITORY)
            {
                if( is_dir("repository"))
                {
                    unlink("repository");
                }
                if(is_dir("repository"))
                {
                    unlink("pwutils");
                }
                
                if(!is_dir(PITWEB."repository/".SITEID))
                {
                    $this->mainObj->files->createPath(array(PITWEB."repository/",SITEID));
                }
                
                symlink(PWSREPOSITORY,"repository");
                symlink(PWSYSUTILS,"pwutils");
                
            }
            if(!is_link("repository"))
            {
                if(!is_dir(PITWEB."repository/".SITEID))
                {
                    $this->mainObj->files->createPath(array(PITWEB."repository/",SITEID));
                }
                symlink(PWSREPOSITORY,"repository");
                symlink(PWSYSUTILS,"pwutils");
            }
        }
    }
    
    
    /**
     * 
     * Función que valida la entrada de datos al sistema por medio de expresiones regulares
     * @param Integer 	$id 			Id de la validación
     * @param String 	$variable		Variable a verificar
     */
    public static function validateData($id, $variable)
    {
        
        $result = false;
        switch ($id)
        {
            case 1 :
                $result = preg_match ( '/^[a-zA-Z0-9]*$/', $variable );
                break;
        }
        return $result;
    }
    
    /**
     * Convierte un objeto stdObj a aun array para poder ser leido en los ciclos	
     * Solo convierte los objetos que viene del javaBridge
     * si se usa el javaBridge, debe de usarse en cada query antes de empezar el ciclo de lectura
     * @param Integer	$conId	Identificador de conexión
     * @param Array		$array	Array a convertir
     * @return Regresa el objeto convertido en array;	
     */
    public function getArrayObject($conId, $array)
    {
        
        if ($conId == 3)
        {
            $array = new ArrayObject ( $array );
        }
        return $array;
    }
    
    public function objectToArray($d)
    {
        if (is_object ( $d ))
        {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars ( $d );
        }
        
        if (is_array ( $d ))
        {
            /*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
            return array_map ( __FUNCTION__, $d );
        }
        else
        {
            // Return array
            return $d;
        }
    }
    
    /**
     * Función que recarga la página
     * @param String $extra Nombre del mólulo al que queremos ir
     */
    public function reloadPage($extra = false)
    {
        echo header ( "Location:?mod=$extra" );
    }
    
    /**	  
     * Función que regresa el html para mostrar un error
     * @param integer	$error	Numero de error
     * @param String	$class	Clase que genera el error
     */
    public function getErrorTemplate($error, $class)
    {
        $data = $this->getTemplate ( "error" );
        $msg = $this->getErrorMessage ( $error );
        $data = preg_replace ( "/__MENSAJE__/", $msg, $data );
        $data = preg_replace ( "/__CLASE__/", $class, $data );
        
        return $data;
    }
    
    /**	  
     * Función que regresa el html para mostrar un mensaje de éxito
     * @param integer	$error	Numero de error
     * @param String	$class	Clase que genera el mensaje
     */
    public function getSuccessTemplate($error, $class)
    {
        $data = $this->getTemplate ( "success" );
        $msg = $this->getErrorMessage ( $error );
        $data = preg_replace ( "/__MENSAJE__/", $msg, $data );
        $data = preg_replace ( "/__CLASE__/", $class, $data );
        
        return $data;
    }
    
    /**	  
     * Manda un mensaje de error en pantalla
     * @param String 	$title		Titulo de la ventana
     * @param Integer $message	Numero del mensaje a enviar
     * @param Integer $icon			Id del icono a mostrar
     */
    public function getDialog($title = false, $message = false, $icon = false)
    {
        
        $data = $this->getTemplate ( "dialog" );
        
        if (! $title)
        {
            $title = "Información";
        }
        
        if (! $icon)
        {
            $icon = 1;
        }
        $iconText = $this->getIcon ( $icon );
        
        if (! $message)
        {
            $message = 100;
        }
        
        $data = preg_replace ( "/__TITLE__/", $title, $data );
        $data = preg_replace ( "/__ICONCLASS__/", $iconText, $data );
        
        $message = $this->getErrorMessage ( $message );
        $data = preg_replace ( "/__MESSAGE__/", $message, $data );
        
        return $data;
    }
    
    /**	  
     * Trae el icono a usar en el mensaje
     * @param String $number	Número del icono a usar
     */
    public function getIcon($number)
    {
        //Iconos de JQuery para mostrar
        $iconos = array (1 => "ui-icon-info", 2 => "ui-icon-alert", 3 => "ui-icon-key" );
        
        return $iconos [$number];
    }
    
    public function createPathArray($path, $delimiter = "/")
    {
        $arrList = explode ( $delimiter, $path );
        
        $pathArray = array ();
        foreach ( $arrList as $arrItem )
        {
            $pathArray [] = $arrItem . $delimiter;
        }
        return $pathArray;
    }
    
    /**	  
     * Función que regresa el html para mostrar un filtro con el boton de exportar a excel     
     * @param String	$class	Clase que genera el mensaje
     * @param String 	$extra	Codigo extra por si queremos agregar algo en el cuadro de la derecha
     * 
     */
    public function getExcelFilter($class, $extra = "")
    {
        $data = $this->getTemplate ( "excelFilter" );
        $data = preg_replace ( "/__CLASSNAME__/", $class, $data );
        $data = preg_replace ( "/__EXTRA__/", $extra, $data );
        return $data;
    }
    
/**	  
     * Función que regresa el html para mostrar un filtro con el boton de exportar a excel     
     * @param String	$class	Clase que genera el mensaje
     * @param String 	$extra	Codigo extra por si queremos agregar algo en el cuadro de la derecha
     * 
     */
    public function getDefaultFilter($extra = "")
    {
        $data = $this->getTemplate ( "getFilterDefault" );
        //$data = preg_replace ( "/__CLASSNAME__/", $class, $data );
        $data = preg_replace ( "/__EXTRA__/", $extra, $data );
        return $data;
    }
    
    
    
    
    
    public function getVardumpLogQuery($params)
    {
        
        $data = "";        

        foreach ( $params as $item )
        {
            if(is_int($item))
            {
                $data .= $item. ",";
            }
            if(is_string($item))
            {
                $data .= "'$item',";
            } 
        }        
        error_log ( $data );
    }
    
    
    
    
    
    
    /**
     * 
     * Función que regresa el codigo para usar el datePicker en el campo especificado
     * 
     * @param String $name Nombre del campo, si viene vacio toma por default fecha
     */
    public function getCustomDateTemp($name = false)
    {
        
        $fecha = $this->getTemplate( "customDate" );
        $fecha = preg_replace("/__FECHANAME__/", $name ? $name :  "fecha", $fecha);
        return $fecha;
         
    }
    
    public function validaString($variable, $default = '')
    {   
        
        if(trim($variable) == "")
        {
            return "null";
        }
        
        return $variable;
    }
    
    public function validaInteger($variable, $default = 0)
    {
        if(trim($variable) == "")
        {
            return "0";
        }
        
        return intval($variable);
    }
    
    
    
    
    
	public function validaDouble($variable, $default = 0)
    {
        if(trim($variable) == "")
        {
            return "0";
        }
        
        return doubleval($variable);
    }
    
    /**
     * 
     * Función que genera una clave con la longitud solicitada
     * @param Integer $longitud Tamaño de la cadena
     */
      
    public function generaClave($longitud)    
    { 
        
        $alpha = "abcdefghijklmnopqrstuvwxyz";
        $alphaUpper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $numeric = "0123456789";
        $special = ".+=@#*%<>";
        $chars = $alpha.$alphaUpper.$numeric.$special; 
        
        $len = strlen($chars);
        $pw = '';
 
        for ($i=0;$i<$longitud;$i++)
        {
            $pw .= substr($chars, rand(0, $len-1), 1);
        }
        $pw = str_shuffle($pw);
        return $pw;
		}

		/**
		 * Genera un código para recuperar contraseña
		 * @param integer $longitud
		 * @return string
		 */
		public function generaPassCode($longitud){
			$cadena="[^A-Z0-9]";
			return substr(str_replace ($cadena, "", md5(rand())) .
					str_replace ($cadena, "", md5(rand())) .
					str_replace ($cadena, "", md5(rand())),
					0, $longitud);
		}


    public function justifyGrid()
    {
        $data = $this->getTemplate("justifyGrid");
        return $data;
    }
    
    /**
     * Función que convierte los bits en formato de byte, megas, gigas , etc
     * @param int $size	Tamaño real
     * @param number $precision	Precision del resultado, por default 2
     * @return string
     */
    public function getFileSize($size, $precision = 2) {
    	$units = array('Byte','Kb','Mb','Gb','Tb','Pb','Eb','Zb','Yb');
    	$step = 1024;
    	$i = 0;
    	while (($size / $step) > 0.9) {
    		$size = $size / $step;
    		$i++;
    	}
    	return round($size, $precision)." ".$units[$i];
    }
    
    
    
    /**
     * Regresa el valor de una variable de sesión encriptada
     * @param Object $mainObj
     * @param String $variable
     * @return string
     */
    public function getSessionVariable($mainObj, $variable)
    {
    
    	$result = "";
    
    	if($variable)
    	{
    		$result =  $mainObj->security->decryptVariable(2, $variable);
    	}
    	return $result;    
    }
    
    
    public function getPagination($module, $page, $totalPages)
    {
    	$data = file_get_contents('template/pagination.html', true);
    	$paginaItem = file_get_contents('template/paginationItem.html', true);
    	$pages = "";
    	$linkf = "?mod=$module&page=$totalPages";
    	$linki = "?mod=$module&page=1";
    	for($i = ($page-2); $i<= $totalPages; $i++)
    	{
    	if($i <= 0 || $i > ($page +2))
    	{
    		continue;
    	}
    	$link = "?mod=$module&page=$i";
    	 
    
    	$paginaItemAux = $paginaItem;
    	$paginaItemAux = preg_replace("/__CLASS__/", $page == $i ? " active " : "", $paginaItemAux);
    			$paginaItemAux = preg_replace("/__LINK__/", $link, $paginaItemAux);
    					$paginaItemAux = preg_replace("/__NUMBER__/", $i, $paginaItemAux);
    	$pages .=$paginaItemAux;
    }
    
    $data = preg_replace("/__LINKB__/", $linki, $data);
    $data = preg_replace("/__LINKF__/", $linkf, $data);
    
    $data = preg_replace("/__PAGINATIONITEMS__/", $pages, $data);
    	return $data;
    }
    
    
    /**
     * FUNCIÓN QUE REGRESA LOS ITEMS DE UN SQLRESULT ASIGNADOA A UN TEMPLATE
     * @param Array		$sqlResults		Array con los resultados de la consulta
     * @param String	$tempItem		Contiene el template a usar
     * @param Array		$tempParams		Array con los valores a sustituir VALORTEMP => VALORBASE
     * @return String 	$resultData		Regresa el String generado o vacio
     */
    public function getBasicTemplate($sqlResults, $tempItem, $tempParams)
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
    
    
    
    public function getFolderLevel($filePath)
    {
    	$pathLevel = explode("/", $filePath);
    	$nivel = sizeof($pathLevel)-1;
    	return $nivel;
    }
    
    public function getFolderLevelMandatos($filePath)
    {
    	$pathLevel = explode("/", $filePath);
    	$nivel = sizeof($pathLevel)-1;
    	return $nivel;
    }
    
    public function getSqlDriver($connection)
    {
    	$result = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
    	return $result;
    }
    
   /* public function getUft8EncodePath($path)
    {
    
    	$result = "";
    	$pathArray = null;
    	$separator = "";
    	if (strncasecmp(PHP_OS, 'WIN', 3) == 0) 
    	{
    		$path = str_replace(array("\\", "/"), "\\", $path);
    		$pathArray = explode("\\", $path);
    		$separator = "\\";
    	} 
    	else 
    	{
    		$path = str_replace(array("\\", "/"), "/", $path);
    		$pathArray = explode("/", $path);
    		$separator = "/";
    	}
    	
    	if(is_array($pathArray))
    	{
    		$size = sizeof($pathArray);
    		$cont = 0;
    		foreach ($pathArray as $item)
    		{
    			if( mb_detect_encoding($item) === "UTF-8")
    			{
    				$item = utf8_encode($item);
    				//$result .= $item.$separator;
    			}
    			$result .= $item;
    			if($cont < $size-1)
    			{
    				$result .= $separator;
    			}
    			
    			$cont ++;
    		}
    	}
    	else
    	{
    		$this->setError(57, __FUNCTION__, __CLASS__);
    	}
    	return $result;    	
    	
    }*/
    
      
    
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
        
        $template ["dialog"] = <<< TEMP

	<script>
	$(function() {
		$( "#dialog:ui-dialog" ).dialog( "destroy" );
	
		$( "#dialog-message" ).dialog({
			modal: true,
			show: "blind",
			hide: "explode",
			buttons: {
				Ok: function() {
					$( this ).dialog( "close" );
				}
			}
		});	
	});
	</script>     
     
 	<div id="dialog-message" title="__TITLE__">
	<p style="text-align:center;">
		<span class="ui-icon __ICONCLASS__" style="float:left; margin:0 7px 50px 0;"></span>
		__MESSAGE__
	</p>	
</div>
     
TEMP;
        
        $template ["excelFilter"] = <<<TEMP
	<div class="row" >
	<form method="post" name="filtro" action="?mod=__CLASSNAME__">
	 <div class="col-md-6" >
    	 <div class="box box-warning">
        		<form role="form">
                <div class="box-footer" id = "formContent">
                	<!--<button type="submit" class="btn btn-primary" name = "mode"  value = "6">Genera excel</button>-->
                	<button type="submit" class="btn btn-primary" name = "mode"  value = "6" style="margin-right: 5px;"><i class="fa fa-download"></i> #_LEXCEL_#</button>
                	
                </div>
            </form>
    	 </div>
	 </form>
	 </div><!--/.col (Col left) -->
	  <div class="col-md-6">
	  __EXTRA__
	 </div><!--/.col (col Rigth) -->
</div>   <!-- /.row -->	
TEMP;

        
        
    $template ["getFilterDefault"] = <<<TEMP
<div class="row" >
	<div class="col-md-6" >
		<div class="box box-warning">			
            	<div class="box-footer" id = "formContent">
            	</div>
    	 </div>	 
	</div><!--/.col (Col left) -->
	<div class="col-md-6">
	__EXTRA__
	</div><!--/.col (col Rigth) -->
</div>   <!-- /.row -->	
TEMP;
        
        
         $template ["customDate"] = <<< TEMP
<script type="text/javascript">
$(function() {
	$("#__FECHANAME__").datepicker({dateFormat: "dd/mm/yy",changeMonth: true, changeYear: true},$.datepicker.regional['es']);	
});
</script>
TEMP;

         
         $template["justifyGrid"]= <<< CELLATTR
function (rowid, value, rawObject, colModel, arraydata) {
    return "style='white-space: normal; padding:5px;text-align:justify' ";
}
CELLATTR;


        
        return $template [$name];
    }

}


?>