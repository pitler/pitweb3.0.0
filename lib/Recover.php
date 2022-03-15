<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
use Pitweb\Date as PwDate;
use Pitweb\Connection as PwConnection;
use Pitweb\Sql as PwSql;
use Pitweb\Security as PwSecurity;
use Pitweb\Correo as PwCorreo;


class Recover 
{

	
	/*
	function __construct()
	{
	}*/


	public static function getData()
	{	   
		
		
	  //error_log("Entro a recover");
	  //$data = $this->getTemplate("main");
	  $code = PwFunciones::getGVariable("code");
	  $chars = array("'", "#", "+" ,"-","*","<",">","'","\"", "\\", "/");
	  $code = str_replace( $chars , "" , $code);
	  
	  //$act = $this->getGVariable("act");
	  $mode = PwFunciones::getPVariable("mode");
	  
	  
	  if(!$mode)
	  {
	    if(!$code)
	    $mode = 1;
	    else
	    $mode = 3;
	  }
	  
	  $data= file_get_contents('template/recover.html', true);
	  $data = preg_replace("/__PAGETITLE__/", PAGETITLE, $data);
	  $data = preg_replace("/__TITLE__/", MAINTITLE, $data);
	  $data = preg_replace("/__YEAR__/", date("Y"), $data);      

	  $connection = PwConnection::getInstance()->connection;      
	  
	  switch ($mode) 
	  {
		case 1: 
			$content = self::getTemplate("envio");
			$data = preg_replace("/__CONTENT__/", $content, $data);
	      
	      break;	      
	    case 2:      
		  //$data = self::generaCodigo();
		  $data = self::validaCorreo($connection);
          break;	    
	    case 3:
		  $content = self::getCambio($connection);
		  //$content = self::getTemplate("envio");
		  //error_log("Envio content");
		  $data = preg_replace("/__CONTENT__/", $content, $data);
	      break;
	    case 4:
	      $data = self::doCambio($connection);
		  break;
		/*case 5:
		  
		  break;*/
	  }
	  
	  /*$data = preg_replace ( "/__LANGR__/", $this->mainObj->label ["LANGR"], $data );
	  $data = preg_replace ( "/__LLANG__/", $this->mainObj->label["LLANG"], $data );
	  $data = preg_replace("/__CONTENT__/", $content, $data);
	  $data = $this->getLangLabels ( $data );*/
	  
	  return $data;
		
	}
	
	
	/**
	 * Función para cambiar las etiquetas de idiomas que se encuentran en el código
	 * busca este formato para reemplazar #_XXXX_#
	 * @param String $content Toda la cadena html que regresa la página
	 * @return String $content La misma variable pero ya reemplazado
	 */
	private static function getLangLabels($content)
	{
		include_once ("lang/es.php");
		$matches = null;
		$ptn = "/#_[a-zA-Z0-9_]*_#?/";
		preg_match_all ( $ptn, $content, $matches, PREG_PATTERN_ORDER );
	
		if ($matches)
		{
			$matches = $matches [0];
			foreach ( $matches as $match )
			{
				$match = preg_replace ( array ("/#_/", "/_#/" ), "", $match );
				$label = $arrLabels [$match];
				$content = preg_replace ( "/#_" . $match . "_#/", $label, $content );
				if (! $label || $label = "")
				{
					PwFunciones::setLogError(7, "$match :: Idioma: $_SESSION[lang]");
					//$this->setError ( 7, "$match :: Idioma: $_SESSION[lang]", __CLASS__ );
				}
			}
		}
		return $content;
	}
	
	/**
	 * Método que realiza el camvio de contraseña
	 * 
	 * @return mixed|string
	 */
	private static function doCambio($connection)
	{

		

	 $code = PwFunciones::getPVariable("code");
	 
	 $chars = array("'", "#", "+" ,"-","*","<",">","'","\"", "\\", "/");
	 $code = str_replace( $chars , "" , $code);
	 $status = "";
	
	
	 if(!$code)
      { 
		$msg = PwFunciones::getErrorMessage(303);
		$result = json_encode(array("status"=>"false","value"=>$msg));
		return $result;       
      }
      else
      {
        $password = PwFunciones::getPVariable("password");
        $cpassword = PwFunciones::getPVariable("cpassword");
		
		
        if($password != $cpassword)
        {
			$msg = PwFunciones::getErrorMessage(304);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
			//$content = $this->getError(304, $code);
          	//return $content;
        }
        
      if(strlen($password) < 8)
      	{ 	
			$msg = PwFunciones::getErrorMessage(305);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
        	/*$content = $this->getError(305, $code);
          	return $content;*/
      	}
      	
        //Verifica que tenga al menos una letra      	
		$patron = "/[a-zA-Z]/";
		$strMatch = preg_match($patron, $password, $coincidencias);
		if($strMatch === 0 || $strMatch === false)
		{
			/*$content = $this->getError(306, $code);
			return $content;*/
			$msg = PwFunciones::getErrorMessage(306);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
		}
		
		
		
		//Verifica que tenga  una letra minuscula
		$patron = "/[a-z]/";
		$strMatch = preg_match($patron, $password, $coincidencias);
		if($strMatch === 0 || $strMatch === false)
		{
			/*$content = $this->getError(314, $code);
			return $content;*/
			$msg = PwFunciones::getErrorMessage(314);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
		}
		
		//Verifica que tenga  una letra mayuscula
      	$patron = "/[A-Z]/";
		$strMatch = preg_match($patron, $password, $coincidencias);
		if($strMatch === 0 || $strMatch === false)
		{
			/*$content = $this->getError(307, $code);
			return $content;*/
			$msg = PwFunciones::getErrorMessage(307);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
		}
		
		//Verifica que tenga un numero
        $patron = "/\d/";
		$strMatch = preg_match($patron, $password, $coincidencias);
		if($strMatch === 0 || $strMatch === false)
		{
			/*$content = $this->getError(308, $code);
			return $content;*/
			$msg = PwFunciones::getErrorMessage(308);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
		}
		
		//Verifica cualquier letra, numero y caracter especial en la lista
		preg_match_all('/[^A-Za-z0-9(){}&$!¡¿?.:%_|°¬@\/=´¨+*~^,;]/', $password, $noCoincide);			
		$results = "";
		$strError = "";
		$noCoincide = $noCoincide[0];
		$results = implode(",", $noCoincide);
		
		
		if($results != "")
		{
			  $msg = PwFunciones::getErrorMessage(309);
			  $result = json_encode(array("status"=>"false","value"=>$msg." :: ".$results ));
			  return $result;
		}
		
        $consulta = "SELECT CVE_USUARIO, STATUS 
                     FROM FC_SYS_USUARIOS
                     WHERE CODE = ? ";
         
        $ps = PwSql::setSimpleQuery ( $connection, $consulta );
        $params = array($code);
        $sqlResultsAux = PwSql::executeSimpleQuery ( $ps, $params, $consulta, null, false, true );

		
    	if($sqlResultsAux)
    	{
			$dataResultsItem = $sqlResultsAux[0];

			$cveUser = $dataResultsItem["CVE_USUARIO"];
    	
    	  //Revisa que el password no haya sido usado    	  
    	  $llave = hash("sha256", $password);
    	  $condition = array("CVE_USUARIO" => $cveUser, "LLAVE" => $llave);
    	  $fields = array("LLAVE");
		  $sqlData = PwSql::executeQuery($connection, "FC_HISTORICO_LLAVES", $fields, $condition);
		  //PwFunciones::getVardumpLog($sqlData);
    	  if(is_array($sqlData) && sizeof($sqlData) >= 1)
    	  {
    	   
			  
			  $msg = PwFunciones::getErrorMessage(58);
			  $result = json_encode(array("status"=>"false","value"=>$msg));
			  //PwSql->insertaBitacora($connection, $cveUser, $this->className, "Recover", $msg);
			  return $result;

		  }
    	
    	 
		  $hoy = date("Ymd");
		   //Si es Oracle
		   if(DBASE == 2)
		   {
			   $hoy = date("d/m/Y");			   
		   }



    	  //Se actualiza la información de la base de datos
    	  $keyFields = array("CVE_USUARIO" => $cveUser);
    	  
		  $datos = array("LLAVE" => $llave, "CODE" => "", "FECHA_CAMBIO" => $hoy);
		  //$datos = array("LLAVE" => $llave,  "FECHA_CAMBIO" => $hoy);
    	  if($dataResultsItem["STATUS"] == 4)
    	  {
    	  	$datos["STATUS"] = 1; 
    	  }

    	  $consulta =PwSql::updateData($connection, "FC_SYS_USUARIOS", $datos, $keyFields);
    	  
    	  //error_log("Consulta == $consulta");
    	 // $consulta = 1;
    	  //si es un password válido y se pudo actualizar, lo ponemos en el histórico
    	  if($consulta)
    	  {
    	  	
				//Llaves
				$fields = "CVE_USUARIO, FECHA, LLAVE";
				$datos = "?,?,?";
				$fechaLlave = date("d/m/Y");
				$values = array($cveUser, $fechaLlave,$llave);
				$tabla = "FC_HISTORICO_LLAVES";
				$result = PwSql::insertData($connection, $tabla, $fields, $datos, $values);
			
		  }
    	  
    	  
    	  if($consulta != 1)//Si existe error al realizar el cambio
    	  {
    	  	/*$msg = $this->getErrorMessage(312);
	  	    $this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "Recover", $msg);
    	    $content = $this->getError(312, '');
			return $content;*/
			
			$msg = PwFunciones::getErrorMessage(312);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			//PwSql::insertaBitacora($connection, $cveUser, "FC_SYS_USUARIOS", "Recover", $msg);
			return $result;
    	   }
    	   else
    	   {
    	   		
				/*$msg = "Final bien";//PwFunciones::getErrorMessage(312);
				//$result = json_encode(array("status"=>"false","value"=>$msg." :: ".$results ));
				$result = json_encode(array("status"=>"false","value"=>$msg, "resultMode" => 1));
				//PwSql::insertaBitacora($connection, $cveUser, "FC_SYS_USUARIOS", "Recover", $msg);
				return $result;*/

				$msgAux = PwFunciones::getErrorMessage(319);
				$msg = self::getTemplate("successRecover");
				$msg = preg_replace("/__MSG__/", $msgAux, $msg);
				$msg = self::getLangLabels($msg);

				$result = json_encode(array("status"=>"true","value"=>$msg, "resultMode" => 1));
				return $result;
								


    	    }	      	  
    	}
    	else
    	{
    	  /*$content = $this->getError(303, $code);
		  return $content; */  

		    $msg = PwFunciones::getErrorMessage(303);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			//PwSql::insertaBitacora($connection, $cveUser, "FC_SYS_USUARIOS", "Recover", $msg);
			return $result;
    	}
     }
  
	} 
	
	/**
	 * Cambia el password de ldap
	 * @param string $login
	 * @param string $newPasswd
	 * @return number
	 */
/*private function changeLdapPasswd($login, $newPasswd)
	{
	  //Datos ldap
      $ldaphost = LDAPHOST;
      $ldapport = LDAPPORT;
      $username = LDAPSTRING;
      $username = preg_replace ( "/__LOGIN__/", $login, $username );
      
      //Datos ldap Admin
      $admUsername = 'cn=cvfldapadm,ou=Administradores,dc=covaf,dc=com';
      $admPassword = 'AdmPADL';

      ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
      //Se realiza la conexion por ldap
      $ad = ldap_connect($ldaphost, $ldapport);
	  
      if(!$ad)
      { 
      	$msg = $this->getErrorMessage(70);
      	$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, $this->className, "", "Cambio pass LDAP", "Error: $msg", "PII");
		$this->setError ( 70, null, $this->className, 2 );
      	return 0;  
      }

      //  Especifico la versión del protocolo LDAP
      $protocol = ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);	   
      if(!$protocol)
      {
      	$msg = $this->getErrorMessage(71);
      	$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, $this->className, "", "Cambio pass LDAP", "Error: $msg", "PII");
      	$this->setError ( 71, null, $this->className, 2 );
        return 0;
      }
	
      $bd = ldap_bind($ad, $admUsername, $admPassword);
      if($bd)
      {
      	
    	$newPassword = $newPasswd;
    	$userdata["userPassword"] = $newPassword;
    
   		$result = ldap_mod_replace($ad, $username , $userdata);
   	    
		if ($result)
		{
		  $this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, $this->className, "", "Cambio pass LDAP", "Exitoso", "PII");
		  return 1;
		} 
		else 
		{
		  $msg = $this->getErrorMessage(312);
		  $this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, $this->className, "", "Cambio pass LDAP", "Error: $msg", "PII");		 
		  $this->setError (312, null, $this->className, 2 );
		  return 0;
		}
	  }
	  else
	  {
	  	$msg = $this->getErrorMessage(72);
	  	$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, $this->className, "", "Cambio pass LDAP", "Error: $msg", "PII");
	  	$this->setError( 72, null, $this->className, 2 );
	  	return 0;
	  }
		 
	}*/
	
	/**
	 * Método que recibe el código generado por el sistema
	 * devuelve el formulario para el cambio de contraseña
	 * @return mixed
	 */
	private static function getCambio($connection)
	{
	   $code = PwFunciones::getGVariable("code");
	   $chars = array("'", "#", "+" ,"-","*","<",">","'","\"", "\\", "/");
	   $code = str_replace( $chars , "" , $code);
       
	  if(!$code)
      { 
		
		$msg = PwFunciones::getErrorMessage(303);								
		$result = self::getTemplate("error");
		$result = preg_replace("/__MENSAJE__/", $msg, $result);
		$result = self::getLangLabels($result);	
		return $result;
      }
      else
      {
      	$consulta = "SELECT *
                     FROM FC_SYS_USUARIOS
                     WHERE CODE = ? ";
      	
      	$ps = PwSql::setSimpleQuery ( $connection, $consulta );
      	$params = array($code);
      	$sqlResultsAux = PwSql::executeSimpleQuery ( $ps, $params, $consulta, null, false, true );

    	if($sqlResultsAux)
    	{
			$dataResultsItem = $sqlResultsAux[0];
    		
    		$user = $dataResultsItem["NOM_USUARIO"]; 
    		$cveUser = $dataResultsItem["CVE_USUARIO"];
			$code = $dataResultsItem["CODE"];   		
			
			$content = self::getTemplate("form");
			$content = preg_replace("/__USUARIO__/", $user, $content);
			$content = preg_replace("/__CODE__/", $code, $content);
			$content = self::getLangLabels($content);	
    	}
    	else
    	{   
			//$msg = $this->getErrorMessage(303);
			$msg  = PwFunciones::getErrorMessage(303);
			$content = self::getTemplate("error");
			$content = preg_replace("/__MENSAJE__/", $msg, $content);
			$content = self::getLangLabels($content);	
    		//$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "", "Cambio pass", "Error: $msg", "PII");
    		//$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "Recover", $msg);
			  //$content = $this->getError(303, "");
    	}
     }
     
     return $content;
	  
	  
	} 

	/**
	 * Genera el código y envia por correo para la recuperacion de password
	 */
  	private static function generaCodigo($connection, $correo)
  	{
		$correoValido = filter_var($correo, FILTER_VALIDATE_EMAIL);
	
		error_log("Genera recover");
		if($correoValido == false)
		{
			//return $this->getError(301, "");
			$msg = PwFunciones::getErrorMessage(301);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
    	}
    
    	if($correo)
    	{
      
      		$data = "";
      		$consulta = "SELECT *
                     FROM FC_SYS_USUARIOS
                     WHERE CORREO = ? ";
      
      		$ps = PwSql::setSimpleQuery ($connection, $consulta );
      		$params = array($correo);
      		$sqlResultsAux = PwSql::executeSimpleQuery ( $ps, $params, $consulta);			  
      		if($sqlResultsAux)
      		{
				if(count($sqlResultsAux) == 1)
				{
					$dataResultsItem = $sqlResultsAux[0];      					
					$user = $dataResultsItem["NOM_USUARIO"];
					$cveUser = $dataResultsItem["CVE_USUARIO"];
					$cadena = PwFunciones::generaCode(20).'';
					$keyFields = array("CVE_USUARIO" => $cveUser);
					$datos = array("CODE" => $cadena);
						
					$consulta = PwSql::updateData($connection, "FC_SYS_USUARIOS", $datos, $keyFields);
					
        			if($consulta == true)
        			{          
      	  				$send = PwCorreo::enviaCorreo($correo, 2, "Solicitud cambio de contraseña", $user, $cadena);
						
      	  				if(!$send)
      	  				{
							//$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "Recover", $msg);											;
							$msg = PwFunciones::getErrorMessage(302);							
							$result = json_encode(array("status"=>"false","value"=>$msg));
							return $result;
						}
						else
						{

							//$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "Recover", "Envio de código para cambio de contraseña del usuario $cveUser");
							$msg = self::getTemplate("success");
							$msg = self::getLangLabels($msg);

							$result = json_encode(array("status"=>"true","value"=>$msg, "resultMode" => 1));
							return $result;
								
						}
					}
					else
					{//Error al generar codigo
						
						//$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $cveUser, $this->className, "Recover", $msg);
						
						$msg = PwFunciones::getErrorMessage(311);							
						$result = json_encode(array("status"=>"false","value"=>$msg));
						return $result;

								
						//	return $this->getError(311, "");
					}
				}
				else
				{//Si existen mas correos
					$msg = PwFunciones::getErrorMessage(313);							
					$result = json_encode(array("status"=>"false","value"=>$msg));
					return $result;
       			}
      		}
      		else
      		{        
				$msg = PwFunciones::getErrorMessage(310);							
				$result = json_encode(array("status"=>"false","value"=>$msg));
				return $result;
      		}    
    	}
  	}
  
  /**
   * Genera un código para recuperar contraseña
   * @param integer $longitud
   * @return string
   */
	private static function generaClaveMail($longitud)
  	{
	   
       $cadena="[^A-Z0-9]"; 
       return substr(str_replace ($cadena, "", md5(rand())) . 
       str_replace ($cadena, "", md5(rand())) . 
       str_replace ($cadena, "", md5(rand())), 
       0, $longitud); 
	} 







//Ejemplo de utilización para una clave de 10 caracteres:

/**
 * Coloca los mensajes de error encontrados
 * @param unknown_type $number
 * @param unknown_type $code
 * @param unknown_type $extra
 * @return mixed
 */
private function getError($number, $code, $extra = "")
	{
		$content = $this->getTemplate("error");
    	$mensaje = $this->getErrorMessage($number);
    	$content = preg_replace("/__MENSAJE__/", $mensaje.$extra, $content);    
    	if($code)
    	{
    		$code = "?code=$code";
    	}	  
    	$content = preg_replace("/__CODE__/", $code, $content);
    	
    	return $content;
	}


	private static function validaCorreo($connection)
    {
        $result = json_encode(array("status"=>"true","value"=>""));
        $correo = PwFunciones::getPVariable("correo");
        $keyFields = array("CORREO" => $correo);
        $valid = PwFunciones::validaInsert($connection, $keyFields, "FC_SYS_USUARIOS");
		
		//Si no existe el correo registrado		
        if($valid == true)
        {
			$msg = PwFunciones::getErrorMessage(310);
			$result = json_encode(array("status"=>"false","value"=>$msg));
			return $result;
			
		}
		
		$result = self::generaCodigo($connection, $correo);
		//PwFunciones::getVardumpLog($result);
		
		return $result;
		
		
    }
    


	public static function getTemplate($name)
	{
	  $template["envio"] = <<< TEMPLATE
	  <!-- Form -->
	  <form class="g-py-15" id="recoverForm">
		  <div class="mb-4">
			  <!-- Text Input with Left Appended Icon -->
			  <div class="form-group  g-mb-20">
				  <div class="input-group g-brd-primary--focus">
					  <div class="input-group-addon d-flex align-items-center g-color-gray-light-v1 rounded-0">
						  <i class="fa fa-envelope"></i>
					  </div>
					  <input class="form-control form-control-md rounded-0" type="email" id="correo" name="correo" placeholder="Escribe tu correo electrónico">
				  </div>
				  <small class="form-control-feedback" id="correoError"></small>
			  </div>

		  </div>

		  <div class="mb-5">
			  <button class="btn btn-block u-btn-indigo g-py-13 " id="recoverBtn">Enviar</button>
		  </div>
	  </form>
	  <!-- End Form -->
TEMPLATE;

	     $template["success"] = <<< TEMP
	 
		 <!-- Border Alert -->
		 <div class="alert fade show" role="alert">
		   
		   <div class="media">			 
			 <div class="media-body">
			   <div class="d-flex justify-content-between">
				 <p class="m-0"><strong>#_LENVIOCORREO_# <a href = "/" class = "llink">#_LBACK_#</a></strong></p>				 
			   </div>
			   
			 </div>
		   </div>
		 </div>
		 <!-- End Border Alert -->

		
		
TEMP;

$template["successRecover"] = <<< TEMP
	 
<!-- Border Alert -->
<div class="alert fade show" role="alert">
  
  <div class="media">			 
	<div class="media-body">
	  <div class="d-flex justify-content-between">
		<p class="m-0"><strong>__MSG__ <a href = "/" class = "llink">#_LBACK_#</a></strong></p>				 
	  </div>
	  
	</div>
  </div>
</div>
<!-- End Border Alert -->



TEMP;

	  //No se utiliza
	   $template["errorMsg"] = <<< TEMP
	  <div id = "rmsg">
		<p>
		<table  align = "center">
    		<tr>
    			<td class = "filterSpace">&nbsp;</td>
    			<td class = "filterItem"  align = "center">
    				Error al cambiar la contraseña.<br>
    				__MENSAJE__<br><a href= "recover.php" class = "llink">Regresar</a>
    			</td>				
    		</tr>
		</table>
		</p>
	</div>
TEMP;

	   $template["enviado"] = <<< TEMP
	 <form id="loginForm" method="POST" action = "">
		<table  align = "center">
    		<tr>
    			<td class = "filterSpace">&nbsp;</td>
    			<td class = "filterItem"  align = "center">
    				#_LENVIOCORREO_#
    			</td>				
    		</tr>
		</table>
    </form>
	   
TEMP;
 
	   $template["form"] = <<< TEMP


	 
	 <!-- Form -->
	 <form class="g-py-15" id="loginForm">
		 <div class="mb-4">
			 <!-- Text Input with Left Appended Icon -->
			 <div class="form-group  g-mb-20">
				 <div class="input-group g-brd-primary--focus">
					 <div class="input-group-addon d-flex align-items-center g-color-gray-light-v1 rounded-0">
						 <i class="icon-lock"></i>
					 </div>
					 <input class="form-control form-control-md rounded-0" type="password" id="password" name="password" placeholder="#_LESCRIBEPASS_#">
				 </div>
				 <small class="form-control-feedback" id="loginError"></small>
			 </div>
		 </div>

		 <div class="mb-4">
			 <!-- Text Input with Left Appended Icon -->
			 <div class="form-group g-mb-20">
				 <div class="input-group g-brd-primary--focus">
					 <div class="input-group-addon d-flex align-items-center g-color-gray-light-v1 rounded-0">
						 <i class="icon-lock"></i>
					 </div>
					 <input class="form-control form-control-md rounded-0" type="password" id="cpassword" name="cpassword" placeholder="#_LCONFIRMAPASS_#">
				 </div>
				 <small class="form-control-feedback" id="cpasswordError"></small>
			 </div>
			 <!-- End Text Input with Left Appended Icon -->
		 </div>
		 <div id="difPass" style="display:none; color:red; float:right"><b>#_LERRORPASS_#</b></div>
			<input type="hidden" name="mode" value = "4">
			<input type="hidden" name="code" id = "code" value = "__CODE__">

		 <div class="mb-5">
			 <input type="button" class="btn btn-block u-btn-indigo g-py-13 " id="changeBtn" value="Cambiar contraseña">
		 </div>
	 </form>
	 <!-- End Form -->

	<div id="pswd_info">
		<h5></h5>
		
            <h1 class="h5 g-color-black g-font-weight-100 ">#_LREQUERIMIENTOSPASS_#</h1>
		</header>
		<ul>			
	   		<li id="small" class="invalid">#_LALMENOS_# <strong>#_LMINUSCULA_#</strong></li>
			<li id="capital" class="invalid">#_LALMENOS_# <strong>#_LMAYUSCULA_#</strong></li>
			<li id="number" class="invalid">#_LALMENOS_# <strong>#_LNUMERO_#</strong></li>
			<li id="caracter" class="invalid">#_LCARCATERESPECIAL_# <strong> (){}&$!¡¿?.:%_|°¬@/=´¨+*~^,; </strong></li>
			<li id="length" class="invalid">#_LALMENOS_# <strong>#_LCARACTERES_#</strong></li>
		</ul>
	</div>
	   
	<!--<div id="pswd_infoAux">
		<h4>#_LPASSCORRECTO_#</h4>
	</div>  -->
	
TEMP;

	   


$template["error"] = <<< TEMP
	  
	
	<table  align = "center">
    	<tr>
    		<td class = "filterSpace">&nbsp;</td>
    		<td class = "filterItem"  align = "center">
    			__MENSAJE__ <a href= "?mod=recover" class = "llink"> #_LBACK_# </a>
    		</td>				
    	</tr>
	</table>
	
TEMP;
	  
	    $template["pregError"] = <<< TEMP
	    <br>Los siguientes caracteres no son v&aacute;lidos :  <ul>  __RESULTS__ </ul>	


TEMP;

		return $template[$name];

	}
}
?>