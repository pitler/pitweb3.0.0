<?php


include_once SITEPATH."db/dbCredentials.php";
include_once PWSYSLIB.'funciones.php';



/**
 * Libreria encargada de las conexiones a la base de datos
 * Se usa PDO de php como driver principal 
 * Podemos conectarnos con el javaBridge
 * Podemos hacer conexiones directas a los drivers de base de datos
 * Los parametros de conexión se encuentran en el archivo dbCredentials.php en lib->db
 * Podemos mandar conexiones personalizadas llenando los parámetros opcionales
 * @author pitler
 */
class Connection extends funciones
{		
	
	/**
 	 * Nombre de la clase
 	 * @var String Nombre de la clase 
 	 */
 	private $className;
	
	
	function __construct()
	{	
		$this->className = "Connection";		 		
	}

	/**
	 * Función para obtener la conexión a la base de datos por medio de la librería PDO de PHP	 * 
	 * Por default nos conecta a una base de oracle(2) 
	 * 1 .- Para conexiones de mysql
	 * 2 .- Para conexiones a oracle
	 * 3 .- Para conexiones del javaBridge
	 * 4 .- Para conexiones a slqServer
	 * 5 .- Para conexiones personalizadas 
	 * @param int 		$database 			Tipo de manejador de la base de datos a usar. Si no se manda por default deja 2
	 * @param String 	$dbUserAux 			Nombre de usuario de la base. 
	 * @param String 	$dbPasswordAux 		Password de la base. 
	 * @param String 	$dbCadenaAux 		Cadena PDO de conexión de la base
	 * @param String 	$dbCharsetAux		Charset especificado para la base
	 *
	 * @return Regresa el objeto conexión o Null,  dependiendo si la conexión fue exitosa
	 */
	public function getPdoConnection($database = 2, $dbUsernameAux = false, $dbPasswordAux = false, $dbCadenaAux = false, $dbCharsetAux = false, $dbHostAux = false, $dbNameAux = false)
	{
		
		$dbPassword = "";
		$dbCharset = "";
		$dbCadena = "";
		$dbType = "";
		$connection = null;	
		$options = null;
		switch ($database)
		{			
			//Conexion a Mysql
			case 1 :
				$dbType = "pdo_mySql";				
				if(!extension_loaded("pdo_mysql"))
				{
					$this->setError(50, "pdo_mysql", $this->className);
					return null;
				}				
				$dbUsername = MYSQL_USERNAME;
				$dbPassword = MYSQL_PASS;
				$dbCadena = "mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DB;
				$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES  'UTF8'");
			break;
			
			//Conexion a Oracle
			case 2 :				 
				$dbType = "pdo_oracle";
				if(!extension_loaded("PDO_OCI"))
				{
					$this->setError(50, "PDO_OCI", $this->className);
					return null;
				}
				$dbUsername = ORA_USERNAME;
				$dbPassword = ORA_PASS;
				$dbCadena = "oci:dbname=".ORA_HOST;				
			break;
			
			//Conexión al javaBridge
			case 3 :
				$dbType = "javaBridge";				
				$connection = $this->getJavaBridgeConnection($dbUsernameAux, $dbPasswordAux);
			 	return $connection;
			break;
			
			//Conexión a sqlServer
			case 4 :
				$dbType = "pdo_sqlServer";
				if(!extension_loaded("pdo_sqlsrv"))
				{
					$this->setError(50, "pdo_sqlsrv", $this->className);
					return null;
				}
				
				$dbUsername = SQLSRV_USERNAME;
		        if($dbUsernameAux != false)
		        {
			        $dbUsername = $dbUsernameAux;
		        }
				$dbPassword = SQLSRV_PASS;
			    if($dbPasswordAux != false)
			    {			        
			         $dbPassword = $dbPasswordAux;
			    }
			    
			    $host = SQLSRV_HOST;
		        if($dbHostAux != false)
		        {
			        $host = $dbHostAux;
		        }
		        
		        $dbName = SQLSRV_DB;
		        if($dbNameAux != false)
		        {
			        $dbName = $dbNameAux;
		        }
				
				//$dbCadena = "sqlsrv:Server=".SQLSRV_HOST.";Database=".SQLSRV_DB;
				$dbCadena = "sqlsrv:Server=".$host.";Database=".$dbName;				
			break;
			
			//Conexión a una base diferente por medio del pdo
			case 5 :
				$dbType = "pdo_otro";
				$dbUsername = $dbUsernameAux;
				$dbPassword = $dbPasswordAux;
				$dbCadena = $dbCadenaAux;
				$dbCharset = $dbCharsetAux;
		    break;
		}
		
		try 
		{
			//echo "DbCadena $dbCadena";
			$connection = new PDO($dbCadena,$dbUsername,$dbPassword, $options);//, array(pdo::ATTR_PERSISTENT=>true));			
			$connection->setAttribute(PDO::ATTR_ERRMODE, pdo::ERRMODE_EXCEPTION); 			
		}
		catch (PDOException $e)
		{
			$this->setError(30, "($dbType) ".$e->getMessage(), $this->className);
			return null;
		}		
		
		return  $connection;
	}
	
	
/**
	 * Función para obtener la conexión a la base de datos por medio de las librerias nativas del manejador
	 * Por default nos conecta a una base de oracle(2) 
	 * 1 .- Para conexiones de mysql
	 * 2 .- Para conexiones a oracle
	 * 3 .- Para conexiones del javaBridge
	 * 4 .- Para conexiones a slqServer
	 * 5 .- Para conexiones personalizadas 
	 * @param int 		$database 			Tipo de manejador de la base de datos a usar. Si no se manda por default deja 2
	 * @param String 	$dbUserAux 			Nombre de usuario de la base. 
	 * @param String 	$dbPasswordAux 	Password de la base. 
	 * @param String 	$dbCadenaAux 		Cadena PDO de conexión de la base
	 * @param String 	$dbCharsetAux 			Charset especificado para la base
	 *
	 * @return Regresa el objeto conexion o Null,  dependiendo si la conexión fue exitosa
	 */
	public function getConnection($database= 2, $dbUsernameAux = false, $dbPasswordAux = false, $dbHostAux = false, $dbNameAux = false,  $dbCharsetAux = false)
	{
		
		$connection  = null;
		
		switch ($database)
		{
			
			//Conexión a Mysql			
			case 1 :				
				$connection = $this->getMySqlConnection($dbUsernameAux, $dbPasswordAux, $dbHostAux, $dbNameAux, $dbCharsetAux);				
				break;
			//Conexion a Oracle
			case 2 :
				$connection = $this->getOracleConnection($dbUsernameAux, $dbPasswordAux, $dbHostAux, $dbNameAux, $dbCharsetAux);			 	
			  break;			  
			//Conexion con el JavaBridge  
			case 3 :
				$connection = $this->getJavaBridgeConnection($dbUsernameAux, $dbPasswordAux);
			  break;
			//Conexion con SqlServer
			case 4 :
				$connection = $this->getSqlServerConnection($dbUsernameAux, $dbPasswordAux, $dbHostAux, $dbNameAux, $dbCharsetAux);
			break;			
			//Conexión que usará por default
			default :
				$connection = $this->getOracleConnection($dbUsernameAux, $dbPasswordAux, $dbHostAux, $dbNameAux, $dbCharsetAux);
				break;
		}
		return  $connection;
	}
	
	
/**
 * Funcion que se encarga de hacer la conexión a la base con el driver para mySql 
 * @return Regresa la conexión a la base
 */
	private function getMySqlConnection($userA = false, $passwordA = false, $hostA = false, $dbA = false, $charsetA = false)
	{		
		$connection = null;
				
		//Verificamos que la librería esté cargada
		if(!extension_loaded("mysql"))
		{
			$this->setError(50, "mysql", $this->className);
			return null;
		}
		
		//Validaciones por si queremos hacer una conexión con otros parametros 
		//que no son los definidos en el archivo de configuración		
		$user = MYSQL_USERNAME;
		if($userA != false)
		{
			$user = $userA;
		}
				
		$password = MYSQL_PASS;
		if($passwordA != false)
		{
			$password = $passwordA;
		}
		
		$host = MYSQL_HOST;
		if($hostA != false)
		{
			$host = $hostA;
		}
		
		$db = MYSQL_DB;
		if($dbA != false)
		{
			$db = $dbA;
		}
		
		//Hacemos la conexión
		try
		{
			$connection = mysql_connect($host,$user,$password);
			if(!$connection)
			{
				$this->setError(32, "mySql", $this->className);
				return null;
			}
		}
		catch(Exception $e)
		{
			$this->setError(30, "(mySql) ".$e->getMessage(), $this->className);
			return null;
		}
		
		//Una vez que tenemos la conexión, asignamos la base de datos		
		try
		{
			if(!mysql_select_db($db, $connection))
			{
				$this->setError(33, "mySql", $this->className);
				return null;				
			}
		}
		catch (Exception $e)
		{
			$this->setError(30, "(mySql)", $this->className);
			return null;
		}		
		return $connection;		
	}
	
/**
 * Función que se encarga de hacer la conexion a la base con el driver para Oracle (oci8)
 * @return Regresa el enlace a la base
 */
	private function getOracleConnection($userA = false, $passwordA = false, $hostA = false, $dbA = false, $charsetA = false)
	{
		$connection = null;
				
		//Verificamos que la librería esté cargada
		if(!extension_loaded("oci8"))
		{
			$this->setError(50, "oci8", $this->className);
			return null;
		}
		
		//Validaciones por si queremos hacer una conexión con otros parametros 
		//que no son los definidos en el archivo de configuración		
		$user = ORA_USERNAME;
		if($userA != false)
		{
			$user = $userA;
		}
				
		$password = ORA_PASS;
		if($passwordA != false)
		{
			$password = $passwordA;
		}
		
		$host = ORA_HOST;
		if($hostA != false)
		{
			$host = $hostA;
		}
		
		//Hacemos la conexión
		try
		{
			$connection = oci_pconnect($user,$password,$host);
			if(!$connection)
			{
				$this->setError(32, "oci8", $this->className);
				return null;
			}
		}
		catch(Exception $e)
		{
			$this->setError(30, "(oci8) ".$e->getMessage(), $this->className);
			return null;
		}
						
		return $connection;	
	}
	
/**
 * Función que se encarga de hacer la conexión a la base con el driver para sqlServer 
 * @return Regresa la conexión a la base
 */
	private function getSqlServerConnection($userA = false, $passwordA = false, $hostA = false, $dbA = false, $charsetA = false)
	{		
		$connection = null;
				
		//Verificamos que la librería esté cargada
		if(!extension_loaded("sqlsrv"))
		{
			$this->setError(50, "sqlsrv", $this->className);
			return null;
		}
		
		//Validaciones por si queremos hacer una conexión con otros parametros 
		//que no son los definidos en el archivo de configuración		
		$user = SQLSRV_USERNAME;
		if($userA != false)
		{
			$user = $userA;
		}
				
		$password = SQLSRV_PASS;
		if($passwordA != false)
		{
			$password = $passwordA;
		}
		
		$host = SQLSRV_HOST;
		if($hostA != false)
		{
			$host = $hostA;
		}
		
		$db = SQLSRV_DB;
		if($dbA != false)
		{
			$db = $dbA;
		}		
		//Hacemos la conexión
		try
		{
			$connection = mssql_connect($host, $user, $password);
			if(!$connection)
			{
				$this->setError(32, "sqlServer", $this->className);
				return null;
			}
		}
		catch(Exception $e)
		{
			$this->setError(30, "(sqlServer) ".$e->getMessage(), $this->className);
			return null;
		}
		
		//Una vez que tenemos la conexión, asignamos la base de datos		
		try
		{
			if(!mssql_select_db($db, $connection))
			{
				$this->setError(33, "sqlServer", $this->className);
				return null;				
			}
		}
		catch (Exception $e)
		{
			$this->setError(30, "(sqlServer)", $this->className);
			return null;
		}		
		return $connection;		
	}

	/**
	 * 
	 * Funcion que se encarga de hacer la conexión 
	 * por medio de javaBridge
	 * @return Regresa el enlace al javaBridge
	 */
	private function getJavaBridgeConnection($jbUrlAux = false, $jbJNDIAux = false)
	{
		
		$conn = false;
		
		//Ponemos las contantes predefinidas
		$jbUrl = JAVABRIDGE_URL;
		$jbJNDI = JAVABRIDGE_JNDI;
		
		//Si se mandan nuevos parametros, estos sustituyen a las contantes
		if($jbUrlAux != false)
		{			
			$jbUrl = $jbUrlAux;
		}
		
		if($jbJNDIAux != false)
		{			
			$jbJNDI = $jbJNDIAux;
		}

		$config = array(
			"java.naming.factory.initial" => "weblogic.jndi.WLInitialContextFactory",
      "java.naming.provider.url" => $jbUrl
    );
    try
    {    
   
    //Inicializamos la clase de Java
    $context = new Java("javax.naming.InitialContext", $config);
 

    //Si falla el context avisamos, de todas formas nos manda un fatalError
    if(!$context)
    {
    	$this->setError(31, "(JavaBridge) Error al traer el context", $this->className);
    }
    
    //Buscamos por medio del JNDI    	
    	$conn = $context->lookup($jbJNDI);
    	
    }    
    catch(Exception $e)
    {
			$this->setError(31, "(JavaBridge) ".$e->getMessage(), $this->className);
			return null;
    }

    return $conn;      	
	}	
}
?>