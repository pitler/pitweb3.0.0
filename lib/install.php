<?php

//Cargamos las librerias de funciones y configuración
include_once 'src/lib/funciones.php';
include_once 'src/lib/mainVars.php';
include_once ("src/lib/config.php");

class install extends funciones
{
    
    /**
     * Objeto para las funciones generales
     * @var ObjFunciones Objeto para las funciones generales
     */
    private $mainObj;
    
    /**
     * Nombre de la clase
     * @var String Nombre de la clase 
     */
    private $className;
    
  
    function __construct()
    {
        
        $this->mainObj = new mainVars ( DBASE, SYSID, "" );
        $this->className = "install";
    
    }
    
    public function getPageData()
    {   
        
        $content = $this->getTemplate("main");
        $trueIcon = $this->getTemplate("acceptIcon");
        $falseIcon = $this->getTemplate("cancelIcon");
        
        /*
         *
         * QUERY PARA LA TABLA DE FL_MODULOS         
        $fields = "cve_modulo, clase, nombre_clase, desc_clase, cve_menu, padre, status, orden, cve_sistema, icon ";    
        $datos = "?,?,?,?,?,?,?,?,?,?";
        $tabla = "fl_modulos";   
    
        $values = array(
			array(1, "modulos", "Módulos", "Módulos", 2, 0, 1, 1, 2, null),
            array(2, "perfiles", "Perfiles", "Perfiles",2,0,1,2,2,null),	
            array(3, "detallePerfil", "Detalle Perfil", "Detalle Perfil",2,0,1,3,2,null),
            array(4, "mainData", "Inicio", "Inicio",0,0,1,4,2,null),
            array(5, "logout", "Cerrar sesión", "Cerrar sesión",0,0,1,5,2,null),
            array(6, "usuarios", "Usuarios", "Usuarios",2,0,1,6,2,null),
            array(7, "menu", "Menu", "Menu",2,0,1,7,2,null),
            array(8, "fondosUsuario", "Fondos usuario", "Fondos usuario",2,0,1,8,2,null),	
            array(9, "modUsuarios", "Modelo para usuarios ajax", "Modelo para usuarios",0,0,1,9,2,null)
        );
        */
        
        /*
         * 
         * QUERY PARA LA TABLA DE FL_MENU         
        $fields = "CVE_MENU,DESC_MENU,STATUS,LOGO_MENU,ORDEN,CVE_SISTEMA,LABEL";    
        $datos = "?,?,?,?,?,?,?";
        $tabla = "fl_menu";   
    
        $values = array(            
            array(1,"Sistema", 1, "fa-laptop", 1, 2, "_MENU_SISTEMA_"),	
            array(2,"Catálogos", 1, "fa-book", 2, 2, "_MENU_CATALOGOS_"),
            array(3,"Consultas", 1, "fa-search", 3, 2, "_MENU_CONSULTAS_")
        );
        */
        
        /**
         * 
        * QUERY PARA LA TABLA DE FL_FONDOS_USUARIO        
        $fields = "CVE_FONDO,CVE_USUARIO,TIPO_PERMISO,CONS_REG,FECHA_INICIO,FECHA_FIN,FECHA_APLICACION,CVE_SISTEMA,CVE_OPERADORA";
        
        $datos = "?,?,?,?,?,?,?,?,?";
        $tabla = "fl_fondos_usuario";          
        $values = array(            
            array(0, "pitler", "G", "1", "02/10/2013", "31/10/2015", "01/10/2013", 2, 0)
        ); 
    	*/
        
              
      /*  $fields = "CVE_USUARIO,NOM_USUARIO,CVE_PERFIL,STATUS,LLAVE,CODE,CORREO,VALIDACION_SISTEMA,CVE_SISTEMA,LAST_LOGIN,LANG,IMAGEN";
        
        $datos = "?,?,?,?,?,?,?,?,?,?,?,?";
        $tabla = "fl_usuarios";
        $values = array(            
            array("pitler", "Pedro Calzada", "ADMIN", 1, "7242d6c91121f8e2e87803855c028e55", null, "pedro.calzada@covaf.com", 1, 2, "23/10/2013", "es", "avatar2.jpg")

            
        );   */
    	
        
         /**
         * 
         * QUERY PARA LA TABLA DE FL_PERFILES      
        $fields = "CVE_PERFIL, DESC_PERFIL, STATUS, CVE_SISTEMA";
        
        $datos = "?,?,?,?";
        $tabla = "fl_perfiles";
        $values = array(            
            array("ADMIN", "Administrador general", 1, 2)
        );   
    	*/
        
        
        /*$fields = "CVE_PERFIL,CLASE,VISUALIZAR,INSERTAR,ACTUALIZAR,BORRAR,CVE_SISTEMA";
        
        $datos = "?,?,?,?,?,?,?";
        $tabla = "fl_detalle_perfil";
        
        $values = array(            
            array("ADMIN", "fondosUsuario", 1,1,1,1,2),
			array("ADMIN", "mainData",	1,1,1,1,2),
            array("ADMIN", "logout", 1,1,1,1,2),
			array("ADMIN", "modulos", 1,1,1,1,2),
            array("ADMIN", "menu",1,1,1,1,2),
			array("ADMIN", "perfiles",1,1,1,1,2),
            array("ADMIN", "detallePerfil",1,1,1,1,2),
			array("ADMIN", "usuarios",1,1,1,1,2)
        );*/
        

        foreach ($values as $val)
        {
            $result = $this->mainObj->sql->insertData($this->mainObj->connection, $tabla, $fields, $datos, $val, $this->className);
            echo "Insercion : $result <br>";
        }   
                
        
        $content = preg_replace("/__CONEXION__/", is_object($this->mainObj->connection) ? $trueIcon : $falseIcon , $content);
        $content = preg_replace("/__PAGETITLE__/", PAGETITLE, $content);
        $content = preg_replace("/__CONTENT__/", $data, $content);
        
        return $content;
    }
    
    /**
     * Función para cambiar las etiquetas de idiomas que se encuentran en el código
     * busca este formato para reemplazar #_XXXX_#
     * @param String $content Toda la cadena html que regresa la página
     * @return String $content La misma variable pero ya reemplazado
     */
    private function getLangLabels($content)
    {
        $matches = null;
        $ptn = "/#_[a-zA-Z0-9_]*_#?/";
        preg_match_all ( $ptn, $content, $matches, PREG_PATTERN_ORDER );
        
        if ($matches)
        {
            $matches = $matches [0];
            foreach ( $matches as $match )
            {
                $match = preg_replace ( array ("/#_/", "/_#/" ), "", $match );
                $label = $this->mainObj->label [$match];
                $content = preg_replace ( "/#_" . $match . "_#/", $label, $content );
                if (! $label || $label = "")
                {
                    $this->setError ( 7, "$match :: Idioma: $_SESSION[lang]", __CLASS__ );
                }
            }
        }
        return $content;
    }
    
  
 
    
    /**
     * Función encargada de contener el código html usado en la clase
     * @param  String $name Nombre del elemento html a buscar
     */
    private function getTemplate($name)
    {
        
        $template ["main"] = <<< TEMP
	    <!DOCTYPE html>
<html>
    <head>
        <title> .:: __PAGETITLE__ ::. </title>
        
        <meta charset="UTF-8">
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>    		<!-- Meta data -->
    	<meta http-equiv="PRAGMA" content="NO-CACHE" />
    	<meta http-equiv="Expires" content="-1" />
    	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
    	<meta name="robots" content="noindex, nofollow,noarchive,noydir" />
  	
    	<meta name="creator" content="PitLeR" />		
    	<meta name="language" content="es" />		
    	<meta name="identifier-url" content="" />
    	<meta name="robots" content="index, nofollow" />
    		
    	
    	<!--REVISAR COMPATIBILIDAD -->
    	<!--------------------------->
    	<!--<script src="js/jquery-2.0.2.min.js" type="text/javascript"></script>
    	<script src="http://code.jquery.com/jquery-migrate-1.2.1.js"></script>-->
    	<script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    	<!--------------------------->
    	<!--REVISAR COMPATIBILIDAD -->
    		
    		
        <!-- Bootstrap -->
        <script src="js/bootstrap.min.js" type="text/javascript"></script>
        
        <!--Carga elementos nuevos para explorer < 8 -->
        <!--[if lte IE 8]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        
        
        <!-- bootstrap 3.0.2 -->
        <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />        
        <!-- font Awesome -->
        <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <!-- Ionicons -->
        <link href="css/ionicons.min.css" rel="stylesheet" type="text/css" />                  
        <!-- Personal Theme style -->
        <link href="css/mainStyle.css" rel="stylesheet" type="text/css" />

        
        
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>
    
		<section class="content defaultModule">
		
		<row>
		Conexion a la base :: __CONEXION__ 
		</row>
    		__CONTENT__
    	</section>    
    </body>
</html>
	    
TEMP;
        
    
        
        $template ["fileUpload"] = <<< TEMP
<link href="src/utils/fileUpload/uploadfile.css" rel="stylesheet">
<script src="src/utils/fileUpload/jquery.uploadfile.min.js"></script>
TEMP;
        
        $template ["notify"] = <<< TEMP
<script src="src/utils/notify/notify.min.js"></script>
TEMP;
        
        $template ["ligthBox"] = <<< TEMP
<script src="src/utils/lightbox/js/lightbox.min.js"></script>
<link href="src/utils/lightbox/css/lightbox.css" rel="stylesheet" />
TEMP;
        
        $template ["inputMask"] = <<< TEMP

<script src="js/plugins/input-mask/jquery.inputmask.js" type="text/javascript"></script>
<script src="js/plugins/input-mask/jquery.inputmask.date.extensions.js" type="text/javascript"></script>
<script src="js/plugins/input-mask/jquery.inputmask.extensions.js" type="text/javascript"></script>
TEMP;
        
        $template ["dataTables"] = <<< TEMP
<script src="js/plugins/datatables/jquery.dataTables.js" type="text/javascript"></script>
<script src="js/plugins/datatables/dataTables.bootstrap.js" type="text/javascript"></script>
TEMP;
        
        $template ["moduleStyle"] = <<< TEMP
<link href="css/covaf/__STYLENAME__Style.css" rel="stylesheet" type="text/css" />
TEMP;
        
        $template ["jsModule"] = <<< TEMP
<script src="src/js/__JSFILE__" type="text/javascript"></script>
TEMP;
        
      
        $template ["fecha"] = <<< TEMP
<script type="text/javascript">
$(function() {
	$("#fecha").datepicker({dateFormat: "dd/mm/yy",changeMonth: true, changeYear: true},$.datepicker.regional['es']);	
});
</script>
TEMP;

        
        $template ["acceptIcon"] = <<< TEMP
<img src = "imagenes/icons/accept.png">
TEMP;

       $template ["cancelIcon"] = <<< TEMP
<img src = "imagenes/icons/cancel.png">
TEMP;
        
     
        
     
        
        return $template [$name];
    
    }
}
?>