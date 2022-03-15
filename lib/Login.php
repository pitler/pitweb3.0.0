<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
use Pitweb\Date as PwDate;
use Pitweb\Connection as PwConnection;
use Pitweb\Sql as PwSql;
use Pitweb\Security as PwSecurity;


class Login
{        
    public static function getData($mode = 1)
    {

        $tempError = "";
               
        switch ($mode)
        {            
            case 1:
                $data = file_get_contents('template/login.html', true);
                $data = preg_replace("/__PAGETITLE__/", PAGETITLE, $data);
                $data = preg_replace("/__TITLE__/", MAINTITLE, $data);
                $data = preg_replace("/__YEAR__/", date("Y"), $data);
                break;                
            default:
                $data = self::doVerify();
                break;
        }        
        return $data;
    }
    
    
    /*	public function getSiteLogin($reqCaptcha, $loginMode = false, $errorNumber = false, $module = "")
     {
     
     if($loginMode == false)
     {
     $loginMode = $this->getPVariable("loginMode");
     }
     
     if(!$loginMode)
     {
     $loginMode = 1;
     }
     
     if(isset ( $_SESSION ["autentified"]) &&  $_SESSION ["autentified"]  != null)
     {
     
     $this->reloadPage("inicio");
     }
     
     //Presento pantalla de login
     if($loginMode == 1)
     {
     
     $data = $this->getTemplateLogin($reqCaptcha, $errorNumber, $module);
     return $data;
     
     }
     
     //Hago la validación
     if($loginMode == 2)
     {
     $data = $this->doVerify($reqCaptcha, true, $module);
     return $data;
     }
     }*/
    
    
    
    /**
     * Verifica si el usuario está autorizado para entrar al sistema
     * Regenera la sesión para que no sea la misma con la que entramos a la página
     * Hace validaciones de captcha y luego con la base de datos
     * Si la informacion es correcta genera la sesión y cifra los datos
     */
    public static function doVerify()
    {
        

        error_log("Verify 2");
      //  die();
        $tiempoInicio =  microtime(true);  
        
        $connection = PwConnection::getInstance()->connection;        

        $login = PwFunciones::getPVariable("login");
        $password = PwFunciones::getPVariable("password");
        $result = array("login" => "true", "password" => "true", "success" => "false");
        

        /****COMENTAMOS PARA PRUEBAS****/
        /*if($site == 1)
         {
         $recaptchaResponse  = $this->getPVariable ( "g-recaptcha-response" );
         $captcha = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6LetiCkTAAAAAFaRtYlGfeXOx9UjG4Vb9B22NyQC&response='.$recaptchaResponse.'&remoteip='.$_SERVER['REMOTE_ADDR']),TRUE);
         }
         else
         {
         $captcha['success']= true;
         }
         
         if($reqCaptcha)
         {
         $captcha = $this->getPVariable("captcha");
         }
         
         if($captcha['success']=== true){
         //Captcha correcto
         $reqCaptcha = false;
         
         } else
         {
         //
         
         $_SESSION ["error"] = 24;
         $this->setError ( 24, null, $this->className, 2 );
         $msg = $this->getErrorMessage(24);
         //$this->mainObj->sql->insertaBitacora($this->mainObj->connection, $login, "login", "", "Inicio de sesión", "Error: $msg", "CVFSYSW");
         //return $this->getLogin ( 1,$reqCaptcha);
         return $this->getSiteLogin($reqCaptcha, 1, 24, $module);
         }*/
        
        /**** FIN COMENTAMOS PARA PRUEBAS****/
        
        //Regeneramos la sesión  y borramos la anterior
        //session_regenerate_id(true);

        //$login = null;
        //$password = null;
        
        //No tiene ni login ni password        
        if(!$login && !$password)
        {
            PwFunciones::setLogError(21);
            $msg = PwFunciones::getErrorMessage(21);
            $result = json_encode(array("status"=>"false","value"=>$msg));
            return $result;
        }

        //No tiene login
        else if(!$login && $password)
        {
            PwFunciones::setLogError(22);
            $msg = PwFunciones::getErrorMessage(22);
            $result = json_encode(array("status"=>"false","value"=>$msg));
            return $result;
        }
        
        //No tiene password
        else if($login && !$password)
        {
            PwFunciones::setLogError(23);
            $msg = PwFunciones::getErrorMessage(23);
            $result = json_encode(array("status"=>"false","value"=>$msg));
            return $result;
        }
        
        error_log("Llego");
        $fields = "";
        if(SITEMODE == 'site')
        {
            $tabla = "SITE_USUARIOS";
           // $fields = array ("CVE_USUARIO", "NOM_USUARIO", "CVE_CLIENTE", "LLAVE", "STATUS", "LAST_LOGIN", "LANG", "VIGENCIA", "FECHA_CAMBIO", "INACTIVIDAD", "CODE", "INTENTOS", "LAST_ACTIVITY");
        }
        if(SITEMODE == 'admin')
        {
            $tabla = "FC_SYS_USUARIOS";
            //$fields = array ("CVE_USUARIO", "NOM_USUARIO", "CVE_PERFIL", "LLAVE", "STATUS", "LAST_LOGIN", "LANG", "VIGENCIA", "FECHA_CAMBIO", "INACTIVIDAD", "CODE", "INTENTOS", "LAST_ACTIVITY" );
        }


        $fields = array ("CVE_USUARIO", "NOM_USUARIO", "CVE_PERFIL", "LLAVE", "STATUS", "LAST_LOGIN", "LANG", "VIGENCIA", "FECHA_CAMBIO", "INACTIVIDAD", "CODE", "INTENTOS", "LAST_ACTIVITY" );
        if(DBASE == 2)
        {
            $fields = array ("CVE_USUARIO", "NOM_USUARIO", "CVE_PERFIL", "LLAVE", "STATUS", "LAST_LOGIN", "LANG", "VIGENCIA", "FECHA_CAMBIO", "INACTIVIDAD", "CODE", "INTENTOS", "to_char(LAST_ACTIVITY, 'DD-MM-YYYY HH24:MI:SS') as LAST_ACTIVITY " );
        }            



        $condition = array("CVE_USUARIO" => $login);
        $userInfo = PwSql::executeQuery($connection, "FC_SYS_USUARIOS", $fields, $condition);
        PwFunciones::getVardumpLog($userInfo);
      // die();
        if($userInfo)
        {
            $userInfo = $userInfo[0];
            //si es un usuario nuevo, lo mandamos a recuperar su password
            //TODO
            if ($userInfo ["STATUS"] == 4)
            {
                //$site=true;
                $codigo = $userInfo["CODE"];
                /*if($site == true)
                {*/
                    if(trim($userInfo["LLAVE"]) == hash("sha256", trim($password)))
                    {
                       // error_log("Mando a reload con ::"."recover&code=$codigo&t=nw");
                       // PwFunciones::reloadPage("recover&code=$codigo&t=nw");
                       $reload= "/?mod=recover&code=$codigo&t=nw";
                       $result = json_encode(array("status"=>"true","action"=>"newRecover","value" => $reload));
                       return $result;
                       // error_log("Paso");
                    }
                    else
                    {                        
                        error_log("Entro 2");
                        PwFunciones::setLogError(29);
                        $msg = PwFunciones::getErrorMessage(29);
                        return json_encode($result);
                    }
                //}
                /*else
                {
                    echo header ( "Location:recover.php?code=$codigo" );
                }*/
                return false;
            }
            
            //Si ya existe una sesion activa
            //$userInfo["STATUS"] = 3;
            if($userInfo["STATUS"] == 3)
            {
                
                $fechaGuardada = date_create($userInfo["LAST_ACTIVITY"]);

               
               // PwFunciones::getVardumpLog($fechaGuardada);

                $fechaActual = date_create(date("Y-m-d H:i:s"));
                //PwFunciones::getVardumpLog($fechaActual);

                $interval = date_diff($fechaGuardada, $fechaActual);
                $min=$interval->format('%i');
                $hora=$interval->format('%H');
                $dias=$interval->format('%d');
                
                 error_log("$min :: $hora :: $dias");
                //Si el tiempo de inactividad es mayor a 20 min, lo dejo pasar de nuevo               
               // $min = 21;
                if($min >= 20 || intval($hora) >= 1 || intval($dias) >= 1)
                {
                    $datos = array("STATUS" => 1);
                    $keyFields = array("CVE_USUARIO" => $userInfo["CVE_USUARIO"]);
                    $consulta =  PwSql::updateData($connection, $tabla, $datos, $keyFields);
                    PwFunciones::setLogError(160);
                    $msg = PwFunciones::getErrorMessage(160);
                    $result = json_encode(array("status"=>"false","value"=>$msg));
                    return $result;
                }
                
                //Si esta ocupada y no ha pasado el tiempo, marco error
                else
                {
                    PwFunciones::setLogError(27);
                    $msg = PwFunciones::getErrorMessage(27);
                    $result = json_encode(array("status"=>"false","value"=>$msg));
                    return $result;
                }
            }
            
            //si está bloqueado
            if ($userInfo ["STATUS"] == 5)
            {
                PwFunciones::setLogError(154);
                $msg = PwFunciones::getErrorMessage(154);
                $result = json_encode(array("status"=>"false","value"=>$msg));
                return $result;
            }
            
            //Bloqueado por inactividad
            if ($userInfo ["STATUS"] == 8)
            {


                PwFunciones::setLogError(157);
                $msg = PwFunciones::getErrorMessage(157);
                $result = json_encode(array("status"=>"false","value"=>$msg));
                return $result;
            }
            

            
            $vigencia = intval($userInfo ["VIGENCIA"]);

            //Si es mySql/SqlServer
            $hoy = date("Ymd");
            $fechaCambio = $userInfo ["FECHA_CAMBIO"];
            
            //Si es Oracle
            if(DBASE == 2)
            {
                $hoy = date("d-m-Y");
                $fechaCambio = str_replace("/", "-",  $userInfo ["FECHA_CAMBIO"]);
            }


            



            $diferencia = PwDate::diferenciaDias($hoy, $fechaCambio);
            //error_log("Diferencia :: $diferencia:: $hoy :: $fechaCambio");
            //La contraseña ha caducado
            if($diferencia >= $vigencia)
            {
                PwFunciones::setLogError(317);
                $msg = PwFunciones::getErrorMessage(317);
                $result = json_encode(array("status"=>"false","value"=>$msg));
                return $result;
            }
            
            $lastLogin = self::lastLogin($userInfo, $hoy);
            
            //Bloqueo por inactividd de 45 dias
            if($lastLogin == false )
            {
                self::blockUser($connection, $userInfo, 8);
                PwFunciones::setLogError(157);
                $msg = PwFunciones::getErrorMessage(157);
                $result = json_encode(array("status"=>"false","value"=>$msg));
                return $result;
            }

            //si está desactivado
            if($userInfo["STATUS"] == 0)
            {
                PwFunciones::setLogError(28);
                $msg = PwFunciones::getErrorMessage(28);
                $result = json_encode(array("status"=>"false","value"=>$msg));
                return $result;
            }
            
            if(trim($userInfo["LLAVE"]) == hash("sha256", trim($password)))
            {

                
                self::createSession($connection, $userInfo, $password);
                //$result = json_encode(array("status"=>"false","value"=>"false"));
                $result = json_encode(array("status" => "true", "value" => "true"));
                return $result;
            }
            
            //Password Incorrecto
            else
            {
                $tabla = "";
                if(SITEMODE == "site")
                {
                    $tabla = "SITE_USUARIOS";
                }
                if(SITEMODE == 'admin')
                {
                    $tabla = "FC_SYS_USUARIOS";
                }
                
                PwFunciones::setLogError(29);
                
                
                $msg = PwFunciones::getErrorMessage(29);
                $result = array("status" => "false", "value" => $msg);
                $result = json_encode($result);
                
                
                $intentos = $userInfo["INTENTOS"] + 1;
                $datos = array("INTENTOS" => $intentos);
                $keyFields = array("CVE_USUARIO" => $userInfo["CVE_USUARIO"]);
                
                PwSql::updateData($connection, $tabla, $datos, $keyFields);
                
                //Bloqueamos el usuario
                if($intentos >= 3)
                {
                    $msg = PwFunciones::getErrorMessage(154);                    
                    PwFunciones::setLogError(154);
                    $result = json_encode(array("status"=>"false","value"=>$msg));                    
                    self::blockUser($connection, $userInfo, 5);
                }
                return $result;
            }
        }
        //Login incorrecto
        else
        {
            
            PwFunciones::setLogError(26);
            $msg = PwFunciones::getErrorMessage(26);
            $result = json_encode(array("status"=>"false","value"=>$msg));
            return $result;
        }
    }
    
    
    /**
     * Una vez que las credenciales son autenticadas, creamos la sesión
     * Creamos una sessionKey
     * Encriptamos la información que ira en la cookie
     * Quitamos el captha
     * Guardamos la fecha deconexión del usuario
     * @param Array	 	$userInfo	Datos del usuario
     * @param Array	 	$password	Password del usuario
     * @param Boolean $isLdap		Si es conexión por LDAP
     * @param Object	$ldapConn	Objeto conexión LDAP
     */
    public static function createSession($connection,$userInfo, $password)
    {
        
        //$this->mainObj->security->createSessionKey();

        error_log("CReo la sesion");
        PwSecurity::createSessionKey();
        
        PwSecurity::encryptVariable(2, "cveUsuario", $userInfo["CVE_USUARIO"]);
        PwSecurity::encryptVariable(2, "cvePerfil", $userInfo["CVE_PERFIL"]);
        PwSecurity::encryptVariable(2, "nombre", $userInfo["NOM_USUARIO"]);
       // PwFunciones::getVardumpLog($userInfo);

        $dateFormat = "Y-n-j G:i:s";
        $activityFormat = "Y-m-d H:i:s";
        $fechaActual = date("Y-m-d H:i:s");

        //Si es oracle
        if(DBASE == 2)
        {
           // $dateFormat = "Y-j-n G:i:s";
           // $activityFormat = "d-m-Y H:i:s";
            $fechaActual = date("d-m-Y H:i:s");
        }

        
        PwSecurity::encryptVariable(2, "lastAccess", date($dateFormat));

        
        //Guardamos la ultima actividad
        $_SESSION["activity"] = date($activityFormat);
        

        //Fingerprint del navegador y su ip
        $_SESSION["fingerPrint"] = hash("sha256",$_SERVER['HTTP_USER_AGENT']);
        $_SESSION["remoteAddr"] = hash("sha256",$_SERVER['REMOTE_ADDR']);
        //	unset($_SESSION["captcha"]);
        
        if(SITEMODE == "site")
        {
            $tabla = "SITE_USUARIOS";
            
        }
        if(SITEMODE == "admin")
        {
            $tabla = "FC_SYS_USUARIOS";
            
        }
        
        //Guardamos el ultimo inicio de sesion
       
        $datos = array("LAST_LOGIN" =>  $fechaActual, "STATUS" => 3, "INTENTOS" => 0);

        if(DBASE == 2)
        {
            $datos["LAST_LOGIN"] = "to_date(?, 'DD-MM-YYYY HH24:MI:SS')//$fechaActual";
        }


        $keyFields = array("CVE_USUARIO" => $userInfo["CVE_USUARIO"]);
       
        $consulta =  PwSql::updateData($connection, $tabla, $datos, $keyFields);
       // PwFunciones::getVardumpLog($_SESSION);

        //PwFunciones::getVardumpLog($_SESSION);
        
    }
    
    /**
     * Bloquea al usuario por fallo de password
     * @param Connection    $connection    Conexión viva
     * @param Array         $userInfo      Información del usuario
     * @param Integer       $status        Status a poner, por default 5
     */
    private static function blockUser($connection, $userInfo, $status = 5 )
    {
         
        $tabla = "";
        if(SITEMODE == "site")
        {
            $tabla = "SITE_USUARIOS";
        }
        if(SITEMODE == "admin")
        {
            $tabla = "FC_SYS_USUARIOS";
        }
        
        $datos = array("STATUS" => $status, "INTENTOS" => 0);
        $keyFields = array("CVE_USUARIO" => $userInfo["CVE_USUARIO"]);
        
        $consulta =  PwSql::updateData($connection, $tabla, $datos, $keyFields);

        
    }
    
    
    /**
     * Función que verifica que el usuario no tenga mas de 45 dias sin conectarse
     * Si tiene mas que eso lo bloquea
     * @param Array $userInfo	Array con los datos del usuario
     * @return boolean
     */
    public static function lastLogin($userInfo, $hoy)
    {
        
        //$connection = PwConnection::getInstance()->connection;        
        $dias = 0;
        
        $result = true;

        $lastLogin = $userInfo["LAST_LOGIN"];

        //Si es Oracle    
        if(DBASE == 2)
        {        
            $lastLogin = str_replace("/", "-",  $userInfo ["LAST_LOGIN"]);
        }



        $dias = $userInfo["INACTIVIDAD"];
        //$lastLogin = "20160515";
        if($lastLogin == null || $lastLogin == "")
        {
            $lastLogin = $hoy;
        }
        
        $diferencia = PwDate::diferenciaDias($hoy, $lastLogin);
        
        if(intval($diferencia) >= intval($dias))
        {
            $result = false;
        }
        return $result;
    }
    
    function getTemplate($name)
    {
        
        $template["login"] = <<<TEMPLATE
TEMPLATE;
        
        return $template[$name];
        
    }
}