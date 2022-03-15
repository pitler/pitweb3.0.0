<?php

namespace Pitweb;
use Pitweb\Funciones as PwFunciones;


/**
 * Clase encargada de el envio de correo
 * en automático
 * @author pcalzada
 *
 */
class Correo
{

    /*function __construct()
    {
        
    }*/

	/**
	 * Función genérica para enviar correos electrónicos por medio de PHPMailes, sin un servidor SMTP
	 * @param String 	$para 			Dirección del destinatario del correo
	 * @param String 	$subject		Asunto del correo
	 * @param Int	    $tipo			Tipo de template que se usará para enviar el mensaje
	 * @param String    $usuario		Usuario del sistema
	 * @param String	$cadena			Cadena que podemos mandar en el correo.
	 * @param String	$body			Cuerpo del mensaje preformateado desde lo mandamos a llamar (HTML)
	 * @param String	$altbody		String que aparece al poner el mouse
	 * @param String	$mailFROM		Dirección de correo desde donde se envía el correo
	 * @param String	$mailNameCompany	Dirección de correo de la compañia
	 */
    public static function enviaCorreo($para, $tipo, $subject,  $usuario = false, $cadena = false, $body = false, $altbody = false, $mailFROM = false,  $mailNameCompany = false, $AddCC=false, $addFiles=false)
    {
    
    	if(!$altbody)
    	{
    		$altbody = "Notificaciones ";
    	}
    	 
    	if(!$mailFROM)
    	{
    		$mailFROM = "centinel@dominio.com";
    	}
    	 
    	if(!$mailNameCompany)
    	{
    		$mailNameCompany = "Nombre";
    	}
    
    	switch ($tipo) {
    		//Para usuarios de la pagina
    		case 1:
    			$body = file_get_contents('template/recoverTemplate.html', true);
    			$body = preg_replace("/__USUARIO__/", $usuario, $body);    	   
    			$body = preg_replace("/__LINK__/", SITERECOVER."&code=$cadena", $body);
    			break;

    		//Para usuarios del admin
    		case 2:
    			$body = file_get_contents('template/recoverTemplate.html', true);
    		  $body = preg_replace("/__USUARIO__/", $usuario, $body);
    			$body = preg_replace("/__LINK__/",ADMINRECOVER."&code=$cadena", $body);    			
    			break;
    			
    		//Para contacto, quejas y/o comentarios
    		case 3:
    			$body = utf8_decode($body);
    			break;
    	  //Para cunando se da de alta un registro
    		case 4:
    		  $body = file_get_contents('template/templateCorreoCliente.html', true);    			    
    		  $body = preg_replace("/__USER__/", $usuario, $body);
    		  $body = preg_replace("/__LINK__/", DIVULGADORASITE, $body);
    			break;
    			
    		case 5:
    		  $body = file_get_contents('template/templatePasswordCliente.html', true);    			    
    		  $body = preg_replace("/__PASSWORD__/", $cadena, $body);
    		  $body = preg_replace("/__LINK__/", DIVULGADORASITE, $body);
    		  break;
    	
    		case 7:
    		  $body = file_get_contents('template/templateUpdatePasswordCliente.html', true);
    		  $body = preg_replace("/__PASSWORD__/", $cadena, $body);
    		  $body = preg_replace("/__LINK__/", DIVULGADORASITE, $body);
    		  break;
    		case 8:
    		    $body = file_get_contents('template/templateAvisoEventoRelevante.html', true);
    		    $body = preg_replace("/__MENSAJE__/", $cadena, $body);
    		    break;    		
    						 
    		default:
    			$body = "Default";//self::->getTemplate("default");
    			break;
    	}
    	if(!$AddCC)
    	{
    	    $copia = $AddCC;
    	}
    	
    	
    	$result = false;
    	 
    	if(self::SendMAIL($para,utf8_decode($subject),$body,$altbody,$mailFROM,$mailNameCompany, $AddCC, $addFiles)){
    		$result = true;
    	}
    
    	return $result;
    	 
    }
	
    
  /*  private static function SendMAIL($para,$subject,$body,$altbody,$mailFROM,$mailNameCompany, $AddCC = false, $addFiles=false)
    {

       // error_log("Ruta mailer :: ".PWASSETS .'vendor/phpMailer/PHPMailerAutoload.php');
       require_once (PWASSETS .'vendor/phpMailer/PHPMailerAutoload.php');
        $mail = new \PHPMailer();
        
    
    	$mail->IsSMTP();
    	$mail->Host = "ssl://smtp.gmail.com";
    	$mail->Port = 465;
    	$mail->SMTPAuth = true;
    	$mail->Username = "";
    	$mail->Password = "";
    	
    	$mail->SMTPOptions = array(
    	  'ssl' => array(
    	    'verify_peer' => false,
    	    'verify_peer_name' => false,
    	    'allow_self_signed' => true
    	  )
    	);
    
    	
    
        $mail->CharSet = "UTF-8"; 
    	$mail -> Subject = $subject . " ".SITEVERSION;
    	
    	$mail->Body = $body;
    	$mail->AltBody = $altbody;
    	
    	$mail->From = $mailFROM;
    	$mail->FromName = $mailNameCompany;
    	
    	if($addFiles){
    	    if(is_array($addFiles)){
    	        foreach ($addFiles as $file){
    	            
    	            $file = str_replace('/','\\', $file);
    	            $nameExploded = explode('\\',$file);
    	            $name = $nameExploded[count($nameExploded)-1];
    	            if (!$mail->AddAttachment($file,$name)) {
    	                error_log('no se pudo cargar el archivo: ' . $file);
    	            }
    	        }
    	    }else{
    	        $file = str_replace('/','\\', $file);
    	        $nameExploded = explode('\\',$file);
    	        $name = $nameExploded[count($nameExploded)-1];
    	        if (!$mail->AddAttachment($file,$name)) {
    	            error_log('no se pudo cargar el archivo: ' . $file);
    	        }
    	    }
    	}
    	
    	if(is_array($para)){
    	    foreach ($para as $correo){
    	        //Agregar correo
    	        $mail -> AddAddress($correo);
    	        //Enviar correo
    	        $exito = $mail->Send();
    	       // error_log( $mail->ErrorInfo);
    	        //Limpiar correo
    	        $mail->clearAddresses();
    	    }
    	}else{
    	    $mail -> AddAddress($para);
    	    $exito = $mail->Send();
    	   // error_log( $mail->ErrorInfo);
    	}    	

    	//Con copia para:
    	if($AddCC)
    	{
    		$mail->AddCC($AddCC);
    	}    	
    	
    	return $exito;
    
    }*/


    public static function sendMail($mailBody, $to, $name, $subject, $AddCC = false )
    {


        error_log("Envio correo :: . " .is_file(PWASSETS.'plugins/phpMailer/class.phpmailer2.php'));
        
        require_once PWASSETS.'plugins/phpMailer/class.phpmailer2.php';
        $mail           = new \PHPMailer();
        $mail->From     = "info@marcobphotography.com";
        $mail->FromName = "=?ISO-8859-1?B?" . base64_encode(utf8_decode("Marco Bautista")) . "=?=";
        $mail->Subject  = "=?ISO-8859-1?B?" . base64_encode(utf8_decode($subject)) . "=?=";
        $mail->Body = $mailBody;
        $mail->IsHTML(true);
        $mail->AddAddress($to, $name);

        if($AddCC)
    	{
    		$mail->AddCC($AddCC);
    	}    	
    	
        
        // enviar mensaje
        $mail->CharSet = 'UTF-8';
        $mail->Send();


    }
}
?>