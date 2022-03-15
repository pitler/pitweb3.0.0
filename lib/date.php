<?php

namespace Pitweb;
use Pitweb\Funciones as PwFunciones;


/**
 * 
 * Clase encargada de realizar tareas que involucren fechas
 * @author pcalzada
 *
 */
class Date 
{

	function __construct()
	{

	}
	
	
	public static function getDateFormat($date, $format)
	{
		$newDate = "";

		//Si no mandaos la fecha, o es nula, ponemos hoy	
		if(!$date)
		{
			$date = date("d-m-Y");
		}
		//$dateAux = $date;
		
		$date = date_create($date);
		switch($format)
		{
			
			//Para DATE
			// Fecha normal d-m-Y/Oracle
			case 1: 			
			$newDate =  date_format($date, 'd-m-Y');
			break;
			//Fecha mySql Y-m-d
			case 2: 
			$newDate =  date_format($date, 'Y-m-d');			
			break;
			//Fecha sqlServer Ymd
			case 3: 
			$newDate =  date_format($date, 'Ymd');			
			break;


			//Para DATETIME
			// Normal / Oracle
			case 10: 
				$newDate =  date_format($date, 'd-m-Y H:i:s');
			break;
			//Para mySql
			case 11: 
				$newDate =  date_format($date, 'Y-m-d H:i:s');
			break;
			//Para SqlServer
			case 12: 
				$newDate =  date_format($date, 'Ymd H:i:s');
			break;

			default :				
					$newDate =  date_format($date, 'd-m-Y H:i:s');
			break;

		}

		return $newDate;


	}


	/**
	*	Fnción para traer el tipo de formato que se usa en la base de acuerdo a lo declarado en el modelo
	* 	Recibe el formato declarado o si no tiene, el de default del sistema
	*	Regresa la clave para buscar en la función getDateFormat()
	*/
	public static function getDateType($format)
	{
		$dateVal = 10;

		switch($format)
		{
			case  "d-m-Y H:i:s" :
				$dateVal = 10;
				//Mysql
				if(DBASE == 1)
				{
					$dateVal = 11;
				}
				//Oracle
				if(DBASE == 2)
				{
					$dateVal = 10;
				}
				//SqlSrv
				if(DBASE == 3)
				{
					$dateVal = 12;
				}
			break;

			case  "d-m-Y" : 
				$dateVal = 10;
				//Mysql
				if(DBASE == 1)
				{
					$dateVal = 2;
				}
				//Oracle
				if(DBASE == 2)
				{
					$dateVal = 2;
				}
				//SqlSrv
				if(DBASE == 3)
				{
					$dateVal =3;
				}
			break;

			default  :
				$dateVal = 10;
				//Mysql
				if(DBASE == 1)
				{
					$dateVal = 2;
				}
				//Oracle
				if(DBASE == 2)
				{
					$dateVal = 2;
				}
				//SqlSrv
				if(DBASE == 3)
				{
					$dateVal =3;
				}
			break;
		}

		return $dateVal;
	


	}
	/*public static function getDatePickerFormat($format)
	{

		switch($format)
		{
			
			// Fecha normal d-m-Y/Oracle
			case 1: 			
			$newDate =  'dd-mm-yy';
			break;
			//Fecha mySql Y-m-d
			case 2: 
			$newDate =  'Y-m-d';			
			break;
			//Fecha sqlServer Ymd
			case 3: 
			$newDate =  'Ymd';			
			break;

			case 10: 
			$newDate =  'dd-md-yy H:i:s';
			break;
			//Para mySql
			case 11: 
			$newDate =  'Y-m-d H.i:s';
			break;
			//Para SqlServer
			case 10: 
			$newDate =  'Ymd H.i:s';
			break;
			default: 			
			$newDate =  'dd-mm-yy';
			break;

		}

		return $newDate;


	}*/

	/*
	*Función que regresa la fecha ne formato de mysql
	*
	*/
	function getMysqlDate($fecha)
	{
		if($fecha)
		{
			$newDate = explode("/", $fecha);
			if(sizeof($newDate) <= 1)
			$newDate = explode("-", $fecha);
			if($newDate)
			{
				$normalDate = $newDate[2]."-".$newDate[1]."-".$newDate[0];
				return $normalDate;
			}
			else return "0000-00-00";
		}
		else return "0000-00-00";
	}


	
	
	//Regresa el día habil anterior las veces que se solicite
	//Formato de la fecha para sql Ymd
	//Usa la funcion DBO.DIAS_HABILES_MDT(Fecha, 1) de sql
	public function getHabilAnterior($mainObj, $fecha, $numero = 1)
	{
	    
	    $fechaFin = "";
	    $consulta = "SELECT CONVERT(varchar(10),DBO.DIAS_HABILES_MDT(?,0), 112) as FECHA";
	    
	    $ps = $mainObj->sql->setSimpleQuery($mainObj->connection, $consulta);
	    $fechaAux = $fecha;
	    //error_log($ps);
	    for($i = 0; $i<$numero; $i++)
	    {
	        $params = array($fechaAux);
	        $sqlResult = $mainObj->sql->executeSimpleQuery($ps, $params, $consulta);
	        if($sqlResult)
	        {
	            $sqlItem = $sqlResult[0];
	            $fechaFin = $sqlItem["FECHA"];
	            $fechaAux = $fechaFin;
	        }
	    }
	    
	    return $fechaFin;
	    
	}
	
	public function getHabilSiguiente($mainObj, $fecha, $numero = 1)
	{
	    
	    $fechaFin = "";
	    $consulta = "SELECT CONVERT(varchar(10),DBO.DIAS_HABILES_MDT(?,1), 112) as FECHA";
	    
	    $ps = $mainObj->sql->setSimpleQuery($mainObj->connection, $consulta);
	    $fechaAux = $fecha;
	    //error_log($ps);
	    for($i = 0; $i<$numero; $i++)
	    {
	        $params = array($fechaAux);
	        $sqlResult = $mainObj->sql->executeSimpleQuery($ps, $params, $consulta);
	        if($sqlResult)
	        {
	            $sqlItem = $sqlResult[0];
	            $fechaFin = $sqlItem["FECHA"];
	            $fechaAux = $fechaFin;
	        }
	    }
	    
	   // error_log($fechaFin);
	    return $fechaFin;
	    
	}
	
	
	/**	  
	 * Regresa el código para insertar un datePicker
	 * @param String $name	Nombre del template a cargar
	 */
	public function getDatePicker($name)
	{		
		return $this->getTemplate($name);		
	}
	
	/**
	 * Regresa si la fecha dada es un dia habil en la tabla de mandatos
	 * @param object	$mainObj	Objeto principal del sistema
	 * @param Date $fechaProceso fecha a verificar
	 */
 	public function esDiaHabilMandatos($mainObj, $fechaProceso)
 	{
 		$resultado = true;
 		
 		if($this->esFinDeSemana($fechaProceso))//$this->esFinDeSemana($fechaProceso))
 		{
 			$resultado = false;
 		}
 		else
 		{   
 			$resultado = $this->esDiaHabilEntreSemanaMandatos($mainObj, $fechaProceso);//$this->esDiaHabilEntreSemanaMandatos($mainObj, $fechaProceso);
 		}

 		return $resultado ;
	}
	
	
   /**
   *
   * Nos dice si la fecha dada es un dia habil entre semana
   * @param object	$mainObj				Objeto principal del sistema   
   * @param Date 		$fechaProceso 	Fecha a verificar
   */
	public function esDiaHabilEntreSemanaMandatos($mainObj, $fechaProceso) 
	{
		
		$resultado = true;
		
		/*$query = "SELECT count(fecha_dia_festivo) as es_inhabil FROM calendarios_valuacion
		WHERE  trunc(fecha_dia_festivo) = trunc(to_date('$fechaProceso', 'dd-mm-yyyy'))
		AND CVE_CALENDARIO = 3 " ;
		
		$result = $sql->getPersonalData($connection, $query);*/
		
		
		
		/*$consulta = "SELECT COUNT(FECHA_DIA_FESTIVO) as ES_INHABIL 
		FROM CALENDARIOS_VALUACION
		WHERE  
		FECHA_DIA_FESTIVO = TO_DATE(?, 'DD-MM-YYYY')
		AND CVE_CALENDARIO = 3 ";*/
		
		
		
		$ps = $mainObj->sql->setSimpleQuery($mainObj->connection, $consulta);
		$params = array($fechaProceso);
		
		$sqlResult = $mainObj->sql->executeSimpleQuery($ps, $params, $consulta);
		
		if($sqlResult)
		{
		    
			$sqlResult = $sqlResult[0];
			$sqlResult = $this->getArrayObject($mainObj->conId, $sqlResult);			
			$esHabil = $sqlResult["ES_INHABIL"];
			
			if($esHabil == 1)
			{
				$resultado = false;
			}
		}		
		return $resultado;
	}
    
	/**
  * Nos dice si el dia dado es fin de semana
  * @param Date $fechaProceso Fecha a verificar
  */
	public function esFinDeSemana($fechaProceso)
	{
		return (date('N', strtotime($fechaProceso)) >= 6);
	}	
	
 	/**
   * Suma los dias indicados a la fecha dada
   * @param Date $fecha Fecha inicial
   * @param Integer $dias Numero de dias a sumar o restar
   */
	public function sumaDias($fecha,$dias)
	{
		$nuevaFecha = date("d-m-Y", strtotime("$fecha + $dias day"));
		return $nuevaFecha;
	}
	
	
	/**  
	 * Resta dias a la fecha dada
   * @param Date $fecha Fecha inicial
   * @param Integer $dias Numero de dias a sumar o restar
   * @return date $nuevaFecha Fecha con 1 dia menos
   */
  public function restaDias($fecha,$dias)
  {
  	$nuevaFecha = date("d-m-Y", strtotime("$fecha - $dias day"));
  	return $nuevaFecha;
  }
	
	
	
	/**	  
	 * Regresa el nombre dle mes correspondiente al número dado
	 * @param Integer $month	Número de mes a buscar
	 * @param Integer	$format	Fomato como se regresa el nombre, 1.- MAYUSCULAS, 	2.- PrimeraMayuscula, 3.- minusculas
	 * @param Integer $lang 	Idioma del mes que regresa
	 */
	public function getNameMonth($month, $format = 1, $lang = 1)
	{
		$month = $month*1;
		$mesArray = array(
				//Español
				1 => array(1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL", 5 => "MAYO",6 => "JUNIO",
						7 => "JULIO", 8 => "AGOSTO", 9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"),
				//Ingles
				2 => array(1 => "JANUARY", 2 => "FEBRUARY", 3 => "MARCH", 4 => "APRIL", 5 => "MAY",6 => "JUNE",
										7 => "JULY", 8 => "AUGUST", 9 => "SEPTEMBER", 10 => "OCTOBER", 11 => "NOVEMBER", 12 => "DECEMBER")
				);
		
		$mes = $mesArray[$lang];
		
	    switch ($format){
	    	//Todas en mayusculas
	        case 1:
	            $result = $mes[$month];
	            break;
	      //La primera en mayusculas
	        case 2 :
	            $result =  ucfirst($mes[$month]);
	            break;
	      //Todas en minúsculas
	        case 3 :
	            $result =  strtolower($mes[$month]);
	            break;
	            	          
	        default:
	            $result = $mes[$month];
	            break;    
	    }
		
		
		return $result;		
	}
	
	
	/**
	 * Regresa la fecha Habil anterior a la dada de mandatos
	 * @param Object	$mainObj 			Objeto principal del sistema
	 * @param Date 		$fechaProceso Fecha a revisar
	 */
	public function leeHabilAnteriorMandatos($mainObj, $fechaProceso)
  {
  	
  	$resultado = null;
  	$fechaAnterior = $this->restaDias($fechaProceso, 1);//$this->restaDias($fechaProceso, 1);
  	
  	//echo "Fecha anterior = $fechaAnterior <br>";
  	if($this->esDiaHabilMandatos($mainObj, $fechaAnterior))//$this->esDiaHabilMandatos($mainObj, $fechaAnterior))
  	{
  		//echo "Es día habil, regreso $fechaAnterior";
  		return $fechaAnterior;
  	}
  	else
  	{
  		return $mainObj->date->leeHabilAnteriorMandatos($mainObj, $fechaAnterior);//$this->leeHabilAnteriorMandatos($mainObj, $fechaAnterior);
  	}
  }  
  
  /**
   * 
   * Función que regresa una fecha dada en formato de Sql
   * @param String	$fecha		Fecha a cambiar
   * @param String $default		Fecha por default
   */
    public function getSqlDate($fecha, $default = "null", $delimiter = "-")
	{
	    $result = date ( 'Y-m-d' );
		if($fecha)
		{
			$newDate = explode("/", $fecha);
			if(sizeof($newDate) <= 1)
			$newDate = explode("-", $fecha);
			if($newDate)
			{
				$result = $newDate[2].$delimiter.$newDate[1].$delimiter.$newDate[0];
			}
		}
		return $result;
	}
	
   /**
   * 
   * Función que regresa una fecha dada en formato smalldatetime de Sql 
   * @param String	$fecha		Fecha a cambiar
   * @param String $default		Fecha por default
   */
    public function getSqlSmallDate($fecha, $default = "null", $delimiter = "-")
	{
	    $result = date ( 'Y-m-d H:m:s' );
		if($fecha)
		{
			$newDate = explode(" ", $fecha);
			
			if($newDate){
				$newDate2 = explode("/", $newDate[0]);
				$newDate3 = "";
				if(is_array($newDate) && sizeof($newDate) > 1)
				{
					$newDate3 = $newDate[1];
				}
				
				if(sizeof($newDate2) <= 1)
					$newDate2 = explode("-", $newDate[0]);
					
				//$this->getVardumpLog($newDate2);
				if(is_array ($newDate2)){
					//$result = $newDate2[2].$delimiter.$newDate2[1].$delimiter.$newDate2[0]." ".$newDate3;
					$result = $newDate2[0].$delimiter.$newDate2[1].$delimiter.$newDate2[2]." ".$newDate3;
					//error_log("uno:".$result); 
				}
				
			}
			
		}
		return $result;
	}
	

	/**
	 * Funcion que regresa la diferencia en dias de 2 fechas, 1-2
	 * al parecer no importa el formato de la fecha mientras sean iguales
	 * @param String $fecha1 	Fecha 1
	 * @param String $fecha2	Fecha 2
	 * @return int	Diferencia en numero
	 */
	public static function diferenciaDias($fecha1, $fecha2)
	{
		$datetime1 = new \DateTime($fecha1);
		$datetime2 = new \DateTime($fecha2);
		$interval = $datetime1->diff($datetime2);
		$result = $interval->format('%a ');
		return $result;
	}
	
	
	public function getActualMonth()
	{
		$mes = "";
		$mes = date("m");		
		$mes = $this->getNameMonth($mes);	
		
		return $mes;
	}
	
	public function getActualYear()
	{
		$year = "";
		$year = date("Y");
		
		return $year;
	}
	
	/**
	 * Funcion que regresa un array con el año y los meses correspondientes hacia atras de una fecha dada
	 * @param Date $hoy Fecha a comparar
	 * @param Integer $tiempo	Tiempo en meses a restar
	 * @return Array con los meses validos agrupados por año
	 */
	public function getPastMonthYearArray($hoy, $tiempo, $idioma = 1)
	{
		//$hoy = date("d-m-Y");
		//$hoy = date("d-m-Y", strtotime("01-02-2017"));
	
		$mes = $this->getNameMonth(intval( date('m', strtotime($hoy))),1,$idioma);
		$año = date('Y', strtotime($hoy));
		//error_log("1 :: $mes :: $año");
		$now   = new DateTime($hoy);
		
		$validMonths = array();
		$validMonths[$año][] = $mes;
		for($i = 1; $i<$tiempo ; $i++)
		{
		
			//$now->modify( '-1 month' );
			$now->modify( 'last day of previous month' );
			$hoy = $now->format("d-m-Y");
			$mes = $this->getNameMonth(intval( date('m', strtotime($hoy))),1,$idioma);
			$año = date('Y', strtotime($hoy));
			if(isset($validMonths[$año]))
			{
				array_push($validMonths[$año],$mes);
			}
			else
			{
				$validMonths[$año][] = $mes;
			}
			$now   = new DateTime($hoy);
		}
		
		return $validMonths;
	}

	/**
	 * Resta los meses que se le indiqun a la fecha dada
	 * @param Date $hoy Fecha inicial
	 * @param Integer		 $tiempo	Numero de meses a restar
	 * @return String Resultado con la nueva fecha
	 */
	public function restaMeses($hoy, $tiempo)
	{
		$now   = new DateTime($hoy);
		$now->modify( "-$tiempo month" );
		$hoy = $now->format("d-m-Y");
		return $hoy;
	}
	
	
	public function esMenor($fechaInicio,$fechaFin, $char = "-")
	{
	  $res = false;
	  $fechaInicio = explode($char, $fechaInicio);
	  $fechaFin = explode($char, $fechaFin);
	  $diferencia= mktime(0,0,0,$fechaInicio[1],$fechaInicio[0],$fechaInicio[2]) - mktime(0,0,0,$fechaFin[1],$fechaFin[0],$fechaFin[2]);
	  $diferencia = $diferencia /86400;
	  // echo $diferencia;
	  if($diferencia <0)
	  {
	    $res = true;
	  }
	  return $res;
	}
	
	public function getUltimoDiaHabilMesAnterior($mainObj, $yearAux = false, $mesAux = false)
	{
	  
	  
	  $mes = date("m");
	  $year = date("Y");
	  
	  if($yearAux)
	  {
	    $year = $yearAux;
	  }
	  if($mesAux)
	  {
	    $mes= $mesAux;
	  }
	  
	  $diaFinal = $year."-".$mes."-01";
	  $diaFinal = $this->getHabilAnterior($mainObj, $diaFinal);
	  
	  return $diaFinal;
	  
	  
	}
	
	

	
	
/********************************************************************************/
	public function generateMkDate($fecha= false)
	{
			$newDate = explode("/", $fecha);
			if($fecha)
			{
				$newDate = explode("/", $fecha);
				if($newDate)
				{
					$dia = $newDate[0];
					$mes = $newDate[1];
					$año = $newDate[2];
					//$mkDate = time($fecha);
					$mkDate = mktime(0,0,0, $mes,$dia, $año);
					return $mkDate;
				}
			}

			else
				return $this->generateDate(date("d/m/Y", time()));
			return "";
		}



		//Falta regresar los tipos de formato que se pueden usar

	function getDate($fecha, $formato = false)
		{
			if($fecha)
			return date("d/m/Y", $fecha);
			else
			return "Error al generar la fecha";
		}


	function getNormalDate($fecha, $format = false, $reciveFormat = false)
	{

		if($fecha)
		{
			$newDate = explode("-", $fecha);
			if(sizeof($newDate) <= 1)
			$newDate = explode("/", $fecha);
			if($newDate)
			{
			    if($reciveFormat == "d-m-Y H:m:s")
			    {
			        //error_log($newDate[2]);
			         $normalDate = substr($newDate[2],0,2)."/".$newDate[1]."/".$newDate[0];
			    }
			    else
			    {			    
				    $normalDate = $newDate[2]."/".$newDate[1]."/".$newDate[0];
			    }
				return $normalDate;
			}
			else return "00/00/0000";
		}
		else return "00/00/0000";
	}



	/*function getDateFormat($date)
	{
	$fecha = explode('-', $date);
		if(sizeof($fecha) <= 1)
			$fecha = explode("/", $date);
		$mes = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo",6 => "Junio",
		7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
		return "$fecha[0] de ". $mes[$fecha[1]*1] ." de $fecha[2]";
	}*/
	
	//Ontiene la fecha respecto al idioma seleccionado
	/*function getDateFormat($date, $lang = false)
	{
		$fechaCompleta = "";
		$fecha = explode('-', $date);
		if(sizeof($fecha) <= 1)
			$fecha = explode("/", $date);

		switch($lang){
			case "en"://October, 14th 2016
				$mes = array(1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May",6 => "June",
						7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December");
				$fechaCompleta = $mes[$fecha[1]*1].", $fecha[0] $fecha[2]";
				
				if($fecha[0] == "01" || $fecha[0] == "21" || $fecha[0] == "31"){
					$sufijo = "st";
				}elseif($fecha[0] == "02" || $fecha[0] == "22"){
					$sufijo = "nd";
				}elseif($fecha[0] == "03" || $fecha[0] == "23"){
					$sufijo = "rd";
				}else{
					$sufijo = "th";
				}

				$fechaCompleta = $mes[$fecha[1]*1] . ", $fecha[0]$sufijo $fecha[2]";
			break;
			
			default: 
				$mes = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo",6 => "Junio",
						7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
				$fechaCompleta = "$fecha[0] de ". $mes[$fecha[1]*1] ." de $fecha[2]";
			break;
			
		}
		
		return $fechaCompleta;
	}*/

    function getMonFormat($date)
	{
	$fecha = explode('-', $date);
		if(sizeof($fecha) <= 1)
			$fecha = explode("/", $date);
		$mes = array("ENE" => 1, "FEB" => 2, "MAR" => 3,"APR"=> 4, "MAY"=> 5, "JUN" => 6,
		"JUL" => 7, "AUG"=> 8, "SEP"=>9,"OCT"=>10, "NOV"=>11, "DIC=>12");
		return "$fecha[0]-". $mes[$fecha[1]] ."-$fecha[2]";
	}


	function getMysqlDatetime($fecha){
	    $nuevaFecha = date("Y-m-d H:i:s", strtotime($fecha));
	    return $nuevaFecha;
	}
	
	
	

	public function getFechaUltimoProceso($connection, $sql, $fondo, $fecha, $conId)
	{

		//$query ="SELECT MAX(FECHA_PERIODO) as MAXFECHA FROM PERIODOS_FONDOS WHERE CVE_FONDO=$fondo AND TRUNC(FECHA_PERIODO)< to_date('$fecha',  'dd-mm-yyyy')";
		$query ="SELECT TO_CHAR(MAX(FECHA_PERIODO), 'dd/mm/YY') as MAXFECHA FROM PERIODOS_FONDOS WHERE CVE_FONDO=$fondo AND FECHA_PERIODO < to_date('$fecha',  'dd-mm-yyyy')";
		//echo $query;
		$result = $sql->getPersonalData($connection, $query);
		//var_dump($result);

		if($result)
		{
            $fecha = $result["MAXFECHA"];

		   if( $fecha == null )
		   {
            $fecha = $this->leeHabilAnterior($connection, $sql,  $fecha, $conId);
		   }
        }
		else
		{
		  //No existe fecha ultimo proceso para el fondo
		  return false;
		}

		return $fecha;

	}

	/**
	 *
	 * Regresa la fecha Habil anterior a la dada
	 * @param Conn $connection Conexion a la base de datos
	 * @param Sql $sql Objeto para ejecutar sentencias sql
	 * @param Date $fechaProceso Fecha a revisar
	 */
    public function leeHabilAnterior($connection, $sql, $fechaProceso, $conId)
    {

       $resultado = null;
       $fechaAnterior = $this->restaDias($fechaProceso, 1);
       if($this->esDiaHabil($connection, $sql, $fechaAnterior, $conId))
       {
         return $fechaAnterior;
       }
       else
       {
         return $this->leeHabilAnterior($connection, $sql, $fechaAnterior, $conId);
       }

     }

/**
	 *
	 * Regresa la fecha Habil siguiente a la dada
	 * @param Conn $connection Conexion a la base de datos
	 * @param Sql $sql Objeto para ejecutar sentencias sql
	 * @param Date $fechaProceso Fecha a revisar
	 */
     
    public function leeHabilSiguiente($connection, $sql, $fechaProceso)
    {

       $resultado = null;
       $fechaSiguiente = $this->sumaDias($fechaProceso, 1);
       if($this->esDiaHabil($connection, $sql, $fechaSiguiente))
       {
         return $fechaSiguiente;
       }
       else
       {
         return $this->leeHabilSiguiente($connection, $sql, $fechaSiguiente);
       }

     }


	/**
	 *
	 * Regresa si la fecha dada es un dia habil
	 * @param Conn $connection Conexion a la base de datos
	 * @param Sql $sql Objeto para ejecutar consultas sql
	 * @param Date $fechaProceso fecha a verificar
	 */
 	public function esDiaHabil($funciones, $connection, $sql, $fechaProceso, $conId)
 	{
 		
 	
 	  $resultado = true;

 	//  echo "Fin de semana $fechaProceso :: ".$this->esFinDeSemana($fechaProceso)."<br>";
 			//echo "Dia habil entre semana 1 <br>";
      if($this->esFinDeSemana($fechaProceso))
      {
      	
            $resultado = false;
      }
      else
      {
      	//echo "Dia habil entre semana 2<br>";
        $resultado = $this->esDiaHabilEntreSemana($funciones, $connection, $sql, $fechaProceso, $conId);
      }

      
      return $resultado ;
    }

    

    /**
     *
     * Nos dice si la fecha dada es un dia habil entre semana
     * @param Conn $connection Conexion a la base de datos
     * @param Sql $sql Objeto para ejecutar consultas sql
     * @param Date $fechaProceso fecha a verificar
     */
	public function esDiaHabilEntreSemana($funciones, $connection, $sql, $fechaProceso, $conId)
	{
		
		$resultado = true;

    $query = "SELECT count(fecha_dia_festivo) as ES_INHABIL FROM dias_festivos
    				WHERE  fecha_dia_festivo = to_date('$fechaProceso', 'dd-mm-yyyy')
    				AND CVE_PAIS IN ('1','MX' ) " ;

    $result = $sql->getPersonalData($connection, $query);
    //var_dump($result);
    if($result)
    {
    	//$result = new ArrayObject($result);
    	$result = $funciones->getArrayObject ( $conId, $result );
    	
    	
    	if(isset($result["ES_INHABIL"]) && $result["ES_INHABIL"] == 1)
    	{
    		$resultado = false;
    	}
    }    
    return $resultado;
     }

   

	
     /*
      * Diferencia de fechas
      *
      */
  /*  public function esMenor($fechaInicio,$fechaFin)
    {
       $res = false;
       $fechaInicio = explode("-", $fechaInicio);
       $fechaFin = explode("-", $fechaFin);
       $diferencia= mktime(0,0,0,$fechaInicio[1],$fechaInicio[0],$fechaInicio[2]) - mktime(0,0,0,$fechaFin[1],$fechaFin[0],$fechaFin[2]);
       $diferencia = $diferencia /86400;
      // echo $diferencia;
       if($diferencia <0)
       {
         $res = true;
       }
      return $res;
     }*/


    public function esMenorIgual($fechaInicio,$fechaFin)
    {
       $res = false;
       $fechaInicio = explode("-", $fechaInicio);
       $fechaFin = explode("-", $fechaFin);
       $diferencia= mktime(0,0,0,$fechaInicio[1],$fechaInicio[0],$fechaInicio[2]) - mktime(0,0,0,$fechaFin[1],$fechaFin[0],$fechaFin[2]);
       $diferencia = $diferencia /86400;
        // echo $diferencia;
       if($diferencia <= 0)
       {
         $res = true;
       }
      return $res;
     }



     public function getTimeZone()
     {
           //America/Mexico_City
          echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
     }

	function getMeses()
	{
		$mes = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo",6 => "Junio",
		7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
		return $mes;
	}


	function getToday()
	{
		$fecha = explode('-', date("d-m-Y"));
		$mes = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo",6 => "Junio",
		7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
		return "$fecha[0] de ". $mes[$fecha[1]*1] ." de $fecha[2]";
	}
	

	
/**
	 * 
	 * Función que regresa el codigo necesario para pintar una ventanita DatePicker
	 * para el jqGrid
	 * @param String $name Nombre del template que contiene el código
	 */
	private function getTemplate($name)
	{
		
	$template["datePicker"] =    
"js:function(el)
{
	setTimeout
	(
		function()
		{
			if(jQuery.ui)
			{
				if(jQuery.ui.datepicker)
				{
					jQuery(el).datepicker({'dateFormat':'dd-mm-yy', changeMonth: true, changeYear: true});
					jQuery('.ui-datepicker').css({'font-size':'85%'});
				}
			}
		},200
	);
}";		
	
	$template["datePickerYYYYMMDD"] =    
"js:function(el)
{
	setTimeout
	(
		function()
		{
			if(jQuery.ui)
			{
				if(jQuery.ui.datepicker)
				{
					jQuery(el).datepicker({'disabled':false,'dateFormat':'yymmdd', changeMonth: true, changeYear: true});
					jQuery('.ui-datepicker').css({'font-size':'85%'});
				}
			}
		},200
	);
}";		
	
		
		 $template["dateTemp"] =    
"js:function(el)
{
	setTimeout
	(
		function()
		{
			if(jQuery.ui)
			{
				if(jQuery.ui.datepicker)
				{
					jQuery(el).datepicker({'disabled':false,'dateFormat':'dd-mm-yy', changeMonth: true, changeYear: true});
					jQuery('.ui-datepicker').css({'font-size':'100%'});
				}
			}
		},200
	);
}";
		 
		 
		
	return $template[$name];	
		
	
	}

}


?>