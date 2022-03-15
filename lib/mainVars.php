<?php
namespace Pitweb;
//include PWSYSLIB . 'Funciones.php';
use PwFunciones;

/**
 * 
 * Clase encargada de instanciar y crear los objetos necesarios para 
 * que estén disponibles dentro del sistema *
 * @author pcalzada
 * @uses Objeto que contiene las variables globales del sistema
 * 
 */
class MainVars 
{
	
	/**
	 * 
	 * Objeto conexión
	 * @var ObjConnection Objeto conexión
	 */	
	public $connection;
	
	/**
	 * Objeto para consultas Sql
	 * @var SqlObject Objeto para consultas Sql
	 */
	public $sql = null;
	
	/**
	 * Objeto para manejar la seguridad
	 * @var SecurityObject Objeto para manejar la seguridad
	 */
   public $security = null;
	
	/**
	 * Variable con el tipo de base de datos que vamos a usar
	 * @var Integer Variable con el tipo de base de datos que vamos a usar
	 */
	public $conId = null;
	
	/**
	 * Variable identificador del sistema cuando se usa en la misma base
	 * @var Integer Variable identificador del sistema cuando se usa en la misma base
	 */
	public $sysId = null;
	
	/**
	 * Variable que contiene la ruta raíz del sistema en ejecución
	 * @var String Variable que contiene la ruta raíz del sistema en ejecución
	 */
	public $rootPath = null;	
	
	/**
	 * Identifica la clave de perfil del usuario conectado
	 * @var Integer Identifica la clave de perfil del usuario conectado 
	 */
	public $cvePerfil = null;
	
	/**
	 * Identifica la clave del usuario conectado
	 * @var Integer Identifica la clave del usuario conectado 
	 */
	public $cveUsuario = null;
	
	/**
	 * Objeto con las funciones usadas por el sistema
	 * @var object Objeto con las funciones usadas por el sistema 
	 */
	public $system = null;
	
	/**
	 * Objeto con las funciones de fecha	 
	 * @var Object Objeto con las funciones de fecha
	 */
	public $date = null;
	
	/**
	 * Objeto para ejecutar funciones de formas
	 * @var Object Objeto para ejecutar funciones de formas
	 */
	public $form = null;
	
	/**
	 * Objeto para manejar el ftp	 
	 * @var Object Objeto para manejar el ftp
	 */
	public $ftp = null;
	
	/**
	 * Variable del lenguaje	 
	 * @var String $lang Variable que guarda el idioma
	 */
	public $lang = "";
	
	/**
	 * Objeto para manejar correo
	 * @var Object Objeto para manejar correo
	 */
	public $correo = null;
	
	
	/**
	 * Objeto para manejar correo
	 * @var Object Objeto para manejar correo
	 */
//	public $elastic = null;
	
	public function __construct($conId, $rootPath = "")
	{		
		
		
		 
		if(!isset($_SESSION["lang"]))
		{
			$_SESSION["lang"] = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);
		}
		
		
		$this->conId = $conId;
		//$this->sysId = $sysId;
		$this->rootPath = $rootPath;
		
		
		//Vamos por la conexión de la base de datos. 
		//La conexión se hace por medio de PDO
		//Por default va a oracle (Tipo 2) 
		//Las otras conexiones son mySql(1); javaBridge(3); sqlServer(4); Personalizado (5)
		//Puede pedir la conexión a otra base siempre y cuando se le envien los parámetros (usuario, password, cadena, charset)
		//Podemos tener varias conexiones al mismo tiempo
		$this->connection = $this->getPdoConnection($this->conId);
		
		//Cargamos el objeto de Sql para tenerlo accesible en cualquier momento
		//Trae por default el archivo para ejecutar con PDO pero se puede traer
		//otro archivo especifico para el manejador de la base
		$this->sql = $this->getSql();
		
		//Cargamos el objeto de Seguridad para tenerlo accesible en cualquier momento
		$this->security = $this->getSecurity();
		
		//Cargamos el objeto SISTEMA para tenerlo accesible en cualquier momento
		$this->system = $this->getSystem();		
		
		//Cargamos el objeto DATE para tenerlo accesible en cualquier momento
		$this->date = $this->getDate();
		
		//Cargamos el objeto FORM para tenerlo accesible en cualquier momento
		$this->form = $this->getForm();
		
		//Cargamos el objeto FILES para tenerlo accesible en cualquier momento
		$this->files = $this->getFiles();
		
		//Cargamos el archivo de idiomas correspondiente al lenguaje seleccionado
		$this->label = $this->getLang();
		
		//Cargamos el archivo para correo
		$this->correo = $this->getCorreo();
		
		
		//Cargamos el archivo para correo
		//$this->elastic = $this->getElastic();
		
		
		
	}
	
	/**	
	 * Función que se encarga de crear el objeto conexión para la base de datos por medio de PDO
	 * @param Integer $manejador Tipo de manejador que usamos para la conexión. Por default usa la 2 para oracle
	 * @return Objeto de conexión de PDO
	 */
	public function getPdoConnection($manejador = 2, $dbUsername = false, $dbPassword = false, $dbCadena = false, $dbCharset = false, $dbHost = false , $dbName = false)
	{		
		$objConnection = $this->getClass("connection");
	
		$connection = $objConnection->getPdoConnection($manejador, $dbUsername, $dbPassword, $dbCadena, $dbCharset, $dbHost, $dbName);
		return $connection;
	}
	
	/**	
	 * Función que se encarga de crear el objeto conexión para la base de datos con un controlador nativo
	 * @param Integer $manejador Tipo de manejador que usamos para la conexión. Por default usa la 2 para oracle
	 * @return Objeto de conexión nativo
	 */	
	public function getConnection($manejador = false)
	{		
		$objConnection = $this->getClass("connection");
		$connection = $objConnection->getConnection($manejador);
		return $connection;
	}
	
	/** 
	 * Función que regresa el objeto que se encarga de manejar la seguridad en el sistema
	 * @return Objeto de seguridad
	 */
	public function getSecurity()
	{
		$objSecurity = $this->getClass("moduleSecurity");
		return $objSecurity;
	}
	
	/**
	 * Función que regresa el objeto para trabajar con SQL  
	 * Por default carga la conexión PDO con Oracle
	 * Si queremos una conexion extra, debemos especificar el id para que traiga el sql con el cual trabajar 
	 * si no va a tomar por default la que está definida, va a tomar PDO, javaBridge o algun otro driver si se define
	 * @var Integer $conId Identificador de conexión
	 */
	public function getSql($conIdA = false)
	{
		
		//Checamos la conexión por default para cargar el sql por default
		$tipo = "";
		if(get_class($this->connection))
		{
			$tipo = get_class($this->connection);
		}
		
		//Inicializamos con el controlador PDO		
		$conId = 5;

		//Esto es para identificar cuando viene por medio del bridge
		$tipoAux = $tipo;
		$str = strstr($tipoAux, 'com.sun.proxy');		
		if($str)
		{
			$tipo = 'com.sun.proxy';
		}
		
		//Si $tipo tiene algun valor, si es PDO carga 5, es del bridge carga 3, si no pone por default 5
		switch ($tipo)
		{
			case 'PDO' :
				$conId = 5;
				break;
			case 'com.sun.proxy';
			 $conId = 3;
			 break;
			default:
				$conId = 5;
			 	break;
		}
		
		//Si se define $conIdA entonces este es el valor que usamos
		//Por lo general solo se define cuando queremos un objeto $sql adicional
		if($conIdA != false)
		{
			$conId = $conIdA;
		}

		switch ($conId)
		{
			case 1:
				$sql = $this->getClass("db/mySql");
				break;
			case 2 :
				$sql = $this->getClass("db/sqlOracle");
				break;				
			case 3 :
				$sql = $this->getClass("db/sqlBridge");
				break;
			case 4 :
				$sql = $this->getClass("db/sqlMSSql");
				break;
			default :
				$sql = $this->getClass("db/sqlPdo");				
			  break;
		}
		return $sql;				
	}
	
	/** 
	 * Función que regresa el objeto SISTEMA
	 * Llama la librería de forma local al sitio donde se ejecute en la ruta /src/lib
	 * 
	 * @return Objeto SISTEMA
	 */
	public function getSystem()
	{   
	    $system = $this->getClass(LOCALFUNCTIONS,SITELIB);
		return $system;
	}
	
/** 
	 * Función que regresa el objeto Date
	 * @return Objeto Date
	 */
	public function getDate()
	{
		$date = $this->getClass("date");
		return $date;
	}
	
/** 
	 * Función que regresa el objeto Form
	 * @return Objeto Form
	 */
	public function getForm()
	{
		$form = $this->getClass("form");
		return $form;
	}
	
	/**
	 * Función que regresa el objeto recover
	 * @return Objeto Form
	 */
	public function getCorreo()
	{
		$correo = $this->getClass("correo");
		return $correo;
	}
	
/** 
	 * Función que regresa el objeto Form
	 * @return Objeto Form
	 */
	public function getFiles()
	{
		$files = $this->getClass("files");
		return $files;
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
	public function getLang()
	{
		$arrLangs = array("es", "en");
		//Idioma por default
		$defaultLang = DEFAULTLANG;
		$sessionLang = $_SESSION["lang"];	
		$this->lang = $sessionLang;

		//Checamos la variable por si se cambió el idioma
		$lang = $this->getGVariable("lang");
		
		/*if(!$lang)
		{
			$lang = $defaultLang;
			$this->lang = $lang;
		}*/
		//error_log("Voy por lenguaje");
		//Si si se cambio y es diferente de algo nulo
		if($lang && $lang != $sessionLang)
		{	
			$_SESSION["lang"] = $lang;
			$this->lang = $lang;
			if(file_exists("lang/$lang.php"))
			{
				//Cambiamos la variable de sesión
				$_SESSION["lang"] = $lang;
				$sessionLang = $lang;
				
				// Si está autenticado, también actualizamos la información
				// del usuario en la tabla con el nuevo idioma
				if(isset($_SESSION["autentified"]))
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
				}
			}	
			else
			{
				$this->setError(53);
			}		
		}	
		//error_log("Icluyo idiona $sessionLang");
		include_once ("lang/$sessionLang.php");
		return $arrLabels;
	
	}
	
	
	/*
	private function getElastic()
	{
		
		
		require PWASSETS.'vendor/autoload.php';
		
	$client = Elasticsearch\ClientBuilder::create()->build();
	//$this->getVardumpLog($client);
	
		
		
		$connectionPool = '\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool';
		
		$client->setConnectionPool($connectionPool);
		
		return $client;
		
	}*/
}
?>