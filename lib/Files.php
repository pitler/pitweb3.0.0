<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
use Pitweb\Security as PwSecurity;
use Pitweb\Sql as PwSql;
use Pitweb\Connection as PwConnection;


/**
 * Clase encargada de funciones relacionadas con arhivos
 * - Creacion y verificación de directorios
 * - Copiar y pegar archivos  
 * - Traer thumbs
 * - Redimensiona imagenes
 * @author pcalzada
 */

class Files 
{
    
    function __construct()
    {
    
    }
    
    /**	  
     * * PW3
     * Función que regresa el String de una ruta cuando se le manda un array
     * Cada elemento del array es una carpeta en la ruta
     * Si no existe la carpeta que lee, la crea y le da permisos
     * 
     * @param Array $pathArray	Array con la ruta a generar
     * 
     * @return String	Regresa el String con la ruta
     */
     //Rvisar como se crean las turas si los directorios no eisten desde la raiz
    public static function createPath($pathArray)
    {
        $dirAux = "";
        $showDir = "";
        $cont = 0;
        //PwFunciones::getVardumpLog($pathArray);
        foreach ( $pathArray as $dir )
        {
            
            $dirAux .= $dir;
            
            //error_log("Ruta :: $dirAux");
            if (! is_dir ( $dirAux ))
            {
                mkdir ( $dirAux, 0777 );
            
            }
            $cont ++;
        }
        
        if (! is_dir ( $dirAux ))
        {
            //$this->setError ( 131, $dirAux . " :: " . __FUNCTION__, __CLASS__, 2 );    
            PwFunciones::setLogError(131);        
            $dirAux = false;
        }
        
        return $dirAux;
    }
    
    /**    
     * Recibe un array con los archivos subidos y los acomoda para su uso
     * @param Array	$arr Array con los archivos subidos
     */
    public function fixFiles($arr)
    {
        foreach ( $arr as $key => $all )
        {
            foreach ( $all as $i => $val )
            {
                $new [$i] [$key] = $val;
            }
        }
        return $new;
    }
    
    /**   
     * PW3
     * Función para regresar una lista con imágenes de una carpeta en especifico 
     * @param String	$ruta				Ruta en donde leeremos las imágenes
     * @param Boolean	$fileName			Aparece o no el nombre del archivo
     * @param Boolean	$radioCheck		    Si viene 1, ponemos la opcion de radio, 2 ponemos checkBox  debajo de la imagen
     * @param Boolean	$deleteIcon		    Aparece o no el icono de borrar
     * @param String	$selected			Elemento que estará preseleccionado
     * @param Integer	$thumbW				Ancho del thumbnail para la imagen
     * @param Integer $thumbH				Altura del thubnail para la imagen
     * 
     */
    public static function getImageList($ruta)
    {
        
        
        $data = "";
      

        //Pasamos los parametros
        $params = rawurldecode(PwFunciones::getPVariable("params"));    

        //A la ruta relativa, le agregamos la ruta física
        $pwRuta = PWSREPOSITORY.$ruta;
        $thumbW = 250;
        $thumbH = 250;
        
        
        //Creamos los thumbs
        $pathArray = array ($pwRuta, "thumbs/",$thumbW."x".$thumbH."/" );
        $pwRutaThumb = self::createPath ( $pathArray );
        $rutaThumb = "repository/".$ruta."thumbs/".$thumbW."x".$thumbH."/";

        //Traemos los templates
        $row = self::getTemplate("cardGroup");
        $imageCard = file_get_contents('template/core/imageCard.html', true);
       
        
        $data = $row;
        $dataAux = "";
        

        //Abrimos el directorio y si existe lo leemos

        if(!is_dir($pwRuta))
        {
            return false;
        }


        $dir = opendir ( $pwRuta );


        $cont = 1;        
        if ($dir)
        {   
            while ( ($archivo = readdir ( $dir )) !== false )
            {
                if (! is_file ( $pwRuta . $archivo ))
                {
                    continue;
                }
                
                //Si no existe el thumbnail lo creamos
                if (! file_exists ( $pwRutaThumb . $archivo ) )
                {
                	self::creaThumb ( $pwRuta . $archivo, $pwRutaThumb, $archivo, $thumbW, $thumbH, $calidad = 90 );
                }
               
                if (file_exists ( $pwRutaThumb . $archivo ))
                {

                    $file = $ruta.$archivo;     
                    
                    $fileName = rawurlencode(PwSecurity::encryptVariable(1, "", $file));
                    
                    $imageCardAux = $imageCard;
                    $imageCardAux = preg_replace ( "/__IMAGE__/", $rutaThumb . $archivo, $imageCardAux );
                    $imageCardAux = preg_replace ( "/__NAME__/", $archivo, $imageCardAux );
                    $imageCardAux = preg_replace ( "/__IMAGEPATH__/", $fileName, $imageCardAux );                  
                    $imageCardAux = preg_replace ( "/__PARAMS__/", $params, $imageCardAux );

                    $dataAux .= $imageCardAux;     
                   if($cont == 3)
                   {
                       $data = preg_replace("/__ITEMS__/",  $dataAux, $data);
                       $dataAux = "";
                       $data .= $row;
                       $cont = 0;
                   }    
                   $cont++;                    
                }
            } 
                     
        }
        else
        {            
            PwFunciones::setLogError(140);
        }
        $data = preg_replace("/__ITEMS__/",  $dataAux, $data);  
        
        return $data;
    }

    /**   
     * PW3
     * Función para regresar una lista con imágenes de una carpeta en especifico 
     * @param String	$ruta				Ruta en donde leeremos las imágenes
     * @param Boolean	$fileName			Aparece o no el nombre del archivo
     * @param Boolean	$radioCheck		    Si viene 1, ponemos la opcion de radio, 2 ponemos checkBox  debajo de la imagen
     * @param Boolean	$deleteIcon		    Aparece o no el icono de borrar
     * @param String	$selected			Elemento que estará preseleccionado
     * @param Integer	$thumbW				Ancho del thumbnail para la imagen
     * @param Integer $thumbH				Altura del thubnail para la imagen
     * TODO arreglar que se evea bonito y poner el acomodo
     */
     public static function getImageListSorted($ruta)
     {
         
        $data = "";
       
 		$connection = PwConnection::getInstance()->connection;    

        //Pasamos los parametros
        $params = rawurldecode(PwFunciones::getPVariable("params"));    
 
        //A la ruta relativa, le agregamos la ruta física
        $pwRuta = 'repository/'.$ruta;
        $dataAux = "";         
 
        //Abrimos el directorio y si existe lo leemos 
        if(!is_dir($pwRuta))
        {
            return false;
        }
 
        //Traemos el id de la seccion
        $id = basename($ruta);

        //Traemos la info de las imagenes de la base
        $condition = array("RUTA" => $pwRuta,  "ID_SECCION" => $id );        
        $order = array("SORT ASC");
        $sqlResults = PwSql::executeQuery($connection, "SITE_IMAGES", false, $condition, $order);            

        $data = self::getTemplate("imagePanel"); 
        $panelLi = self::getTemplate("imagePanelLi");  
         
        if ($sqlResults)
        {   
            foreach($sqlResults as $sqlItem)
            {
                $itemAux = $panelLi;
                $file = $ruta.$sqlItem["NOMBRE"];                        
                $fileName = rawurlencode(PwSecurity::encryptVariable(1, "", $file));
                   
                $itemAux = preg_replace ( "/__PARAMS__/", $params, $itemAux );
                $itemAux = preg_replace("/__IMGNAME__/",  $sqlItem["NOMBRE"], $itemAux);
                $itemAux = preg_replace("/__ID__/",  $id, $itemAux);
                $itemAux = preg_replace("/__SORT__/",  $sqlItem["ID"], $itemAux);
                $itemAux = preg_replace("/__NAME__/",  $sqlItem["NOMBRE"], $itemAux);
                $itemAux = preg_replace ( "/__IMAGEPATH__/", $fileName, $itemAux );                        
                $thumb = self::getImageCopy(substr($ruta, 0, -1), null, 145, 97, 1,   $sqlItem["NOMBRE"], true);                  
                $itemAux = preg_replace("/__IMAGE__/",  $thumb, $itemAux);
                $dataAux .= $itemAux;
             }                       
         }
         else
         {            
             PwFunciones::setLogError(140);
         }
         $data = preg_replace("/__ITEMS__/",  $dataAux, $data);  
         $data = preg_replace("/__PARAMS__/",  $params, $data);  
         
         return $data;
     }



     /**   
     * Función para regresar una lista con imágenes de una carpeta en especifico 
     * @param String	$ruta					Ruta en donde leeremos las imágenes
     * @param Boolean	$fileName			Aparece o no el nombre del archivo
     * @param Boolean	$radioCheck		Si viene 1, ponemos la opcion de radio, 2 ponemos checkBox  debajo de la imagen
     * @param Boolean	$deleteIcon		Aparece o no el icono de borrar
     * @param String	$selected			Elemento que estará preseleccionado
     * @param Integer	$thumbW				Ancho del thumbnail para la imagen
     * @param Integer $thumbH				Altura del thubnail para la imagen
     
     
     * 
     */
     public static function getImageListAnt($ruta, $fileName = false, $radioCheck = false, $deleteIcon = false, $selected = "", $thumbW = 80, $thumbH = 80, $id="", $lightBox = false, $soloImage = false)
     {
         
         $data = "";
         
         //Ruta completa
         $pwRuta = PWSREPOSITORY.$ruta;
         
         
         
         //Cargamos los templates
         if ($lightBox == true)
         {
             $thumbItem = self::getTemplate ( "thumbItemLightBox" );
         }
         if($soloImage == true)
         {
             $thumbItem = self::getTemplate ( "thumbItemSolo" );
         }
         else
         {
             $thumbItem = self::getTemplate ( "thumbItem" );
         }
 
         $pathArray = array ($pwRuta, "thumbs/",$thumbW."x".$thumbH."/" );
         $pwRutaThumb = self::createPath ( $pathArray );
         $rutaThumb = "repository/".$ruta."thumbs/".$thumbW."x".$thumbH."/";
         $radioCheckTemp = "";
         $deleteIconTemp = "";
         $fileNameTemp = "";
                 
         //Si necesitamos un check
         if ($radioCheck == 1)
         {
             $radioCheckTemp = self::getTemplate ( "thumbRadio" );
         }
         if ($radioCheck == 2)
         {
             $radioCheckTemp = self::getTemplate ( "thumbCheck" );
         }
         
         $dataAux = "";
 
         //Abrimos el directorio y si existe lo leemos
         $dir = opendir ( $pwRuta );
         $cont = 1;
         
         if ($dir)
         {     
             //Por cada archivo
             while ( ($archivo = readdir ( $dir )) !== false )
             {
                 if (! is_file ( $pwRuta . $archivo ))
                 {
                     continue;
                 }
                 
                 //Si no existe el thumbnail lo creamos
                 if (! file_exists ( $pwRutaThumb . $archivo ) )
                 {
                     self::creaThumb ( $pwRuta . $archivo, $pwRutaThumb, $archivo, $thumbW, $thumbH, $calidad = 90 );
                 }
                
                 if (file_exists ( $pwRutaThumb . $archivo ))
                 {
                     
                     $thumbItemAux = $thumbItem;
                     $thumbItemAux = preg_replace ( "/__THUMBPATH__/", $rutaThumb . $archivo, $thumbItemAux );
                     
                     if($fileName)
                     {
                         $fileNameTemp = self::getTemplate("fileName");
                         $fileNameTemp = preg_replace("/__FILENAME__/", $archivo, $fileNameTemp);
                     }
                     
                      if($deleteIcon)
                      {
                         $deleteIconTemp = self::getTemplate("deleteIcon");
                         $deleteIconTemp = preg_replace("/__IMGNAME__/", $archivo, $deleteIconTemp);
                         $deleteIconTemp = preg_replace("/__ID__/", $id, $deleteIconTemp);
                      }
                     
                     $thumbItemAux = preg_replace ( "/__NOMBRE__/", $archivo, $thumbItemAux );
                     $thumbItemAux = preg_replace ( "/__FILENAME__/", $fileNameTemp, $thumbItemAux );
                     $thumbItemAux = preg_replace ( "/__RADIOCHECK__/", $radioCheckTemp, $thumbItemAux );
                     $thumbItemAux = preg_replace ( "/__DELETE__/", $deleteIconTemp, $thumbItemAux );
                     
                     if ($lightBox)
                     {
                         $thumbItemAux = preg_replace ( "/__IMGPATH__/", $ruta . $archivo, $thumbItemAux );
                         $thumbItemAux = preg_replace ( "/__NUM__/", $id, $thumbItemAux );
                     }                        
                     $dataAux .= $thumbItemAux;                
                 }
             }             
             $data .= $dataAux;
         }
         else
         {            
             PwFunciones::setLogError(140);
         }
         
         
         return $data;
     }
    
    /**
     * Función para regresar una lista con imágenes de una carpeta en especifico
     * @param String	$ruta				Ruta en donde leeremos las imágenes
     * @param Boolean	$fileName			Aparece o no el nombre del archivo
     * @param Boolean	$radioCheck			Si viene 1, ponemos la opcion de radio, 2 ponemos checkBox  debajo de la imagen
     * @param Boolean	$deleteIcon			Aparece o no el icono de borrar
     * @param String	$selected			Elemento que estará preseleccionado
     * @param Integer	$thumbW				Ancho del thumbnail para la imagen
     * @param Integer 	$thumbH				Altura del thubnail para la imagen
     *
     */
    
    public function getFileNameList($ruta)
    {
    	$data = array();
    
    	//Abrimos el directorio y si existe lo leemos
    	$dir = opendir ( $ruta );
    	$cont = 1;
    	if ($dir)
    	{
    		
    		//Por cada archivo
    		while ( ($archivo = readdir ( $dir )) !== false )
    		{
    			if (! is_file ( $ruta . $archivo ))
    			{
    				continue;
    			}
    			if (file_exists ( $ruta . $archivo ))
    			{
    				$data[]= $archivo;
    			}
    		}
    	}
    	else
    	{
    		$this->setError ( 140, $ruta, __CLASS__ . " :: " . __LINE__ );
    	}
    	return $data;
    }
    
/**   
     * Función para regresar una lista con los archivos contenidos en una carpeta dada 
     * @param String	$ruta			Ruta en donde leeremos los archivos
     * @param Boolean	$radioCheck		Bandera para ver si pintamos un radio debajo del archivo
     * @param String	$selected		Fuciona junto con el checkbox, y preselecciona el archivo
     * @param	Integer	$w				Ancho del icono del archivo, por default 80
     * @param	Integer	$h				Altura del icono, por default 80
     * @param Boolean	$fileName		Si queremos que aparezca el nombre del archivo
     * @param Integer	$perLine		Número de archivos a presentar pro linea  
     * @param String  $id				Identificador de la lista de imagenes para el lightBox
     * @param Boolean	$delete			Para ver si necesitamos el link de borrar
     */
    public function getFileList($ruta, $radioCheck = 1, $selected = "", $w = 40, $h = 40, $fileName = true, $perLine = 5, $id = "", $delete = false)
    {

        $data = "";
        $dir = ""; 
        if (! is_dir ( $ruta ))
        {
            return "noFiles";
        }

        //Cargamos los templates
        $thumbTable = $this->getTemplate ( "fileTable" );
        $thumItem = "";
        
        $thumbItem = $this->getTemplate ( "fileItem" );
        
        $thumbTr = $this->getTemplate ( "fileLine" );
        $thumbRadio = "";
        //Si necesitamos un radio
        if ($radioCheck == 1)
        {
            $thumbRadio = $this->getTemplate ( "fileRadio" );
        }
        
        //Si necesitamos un radio
        if ($radioCheck == 2)
        {
            $thumbRadio = $this->getTemplate ( "fileCheck" );
        }

        $thumbDelete = "";
        //Si necesitamos link para eliminar
        if ($delete == true)
        {
            $thumbDelete = $this->getTemplate ( "deleteFile" );
        }
        
        $dataAux = "";
        
        //Abrimos el directorio y si existe lo leemos		
        $dir = opendir ( $ruta );
        $cont = 1;
        
        $selAux = null;
        if ($selected)
        {
            $selAux = explode ( ",", $selected );
        }        

        if ($dir)
        {
            //Por cada archivo
            while ( ($archivo = readdir ( $dir )) !== false )
            {
                if (! is_file ( $ruta . $archivo ))
                {
                    continue;
                }
                $archivoName = $archivo;
                $nameArray = explode(".", $archivoName);
                
                $thumbItemAux = $thumbItem;
                $ext = strtoupper(end($nameArray));
                //if(!file_exists("imagenes/fileIcons/".$ext.".png"))
                if(!file_exists(PWSREPOSITORY."imagenes/fileIcons/".$ext.".png"))
                {
                    $ext = "Default";
                }
                

                $thumbItemAux = preg_replace ( "/__ICON__/", $ext, $thumbItemAux );
                $thumbItemAux = preg_replace ( "/__FILEN__/", $archivoName, $thumbItemAux );
                
                $strFileName = "";
                if ($fileName)
                {
                    $strFileName = $this->getTemplate ( "fileName" );
                    $strFileName = preg_replace ( "/__FILENAME__/", $archivo, $strFileName );
                }
                
                $thumbItemAux = preg_replace ( "/__FILENAME__/", $strFileName, $thumbItemAux );
                $thumbRadioAux = "";
                
                if ($radioCheck == 2)
                {
                    
                    $existe = in_array ( $archivo, $selAux );
                    $thumbRadioAux = $thumbRadio;
                    $val = "";
                    if ($existe)
                    {
                        $val = "CHECKED";
                    }
                    $thumbRadioAux = preg_replace ( "/__CHECKED__/", $val, $thumbRadioAux );
                }
                
                else
                {
                    if ($selected != "" && ($selected == $archivo))
                    {
                        $thumbRadioAux = "";
                    }
                    else
                    {
                        
                        $thumbRadioAux = $thumbRadio;
                    }
                }
                $thumbRadioAux = preg_replace ( "/__NOMBRE__/", $archivo, $thumbRadioAux );
                $thumbItemAux = preg_replace ( "/__RADIO__/", $thumbRadioAux, $thumbItemAux );
                $thumbDeleteAux = $thumbDelete;
                $thumbDeleteAux = preg_replace ( "/__IMGNAME__/", $archivoName, $thumbDeleteAux );
                $thumbDeleteAux = preg_replace ( "/__ID__/", $id, $thumbDeleteAux );
                
                $thumbItemAux = preg_replace ( "/__DELETE__/", $thumbDeleteAux, $thumbItemAux );
                
                $dataAux .= $thumbItemAux;
                
                //Cada 5 hacemos una linea
                if ($cont > 0 && $cont % $perLine == 0)
                {
                    $trAux = $thumbTr;
                    $trAux = preg_replace ( "/__TRITEMS__/", $dataAux, $trAux );
                    $data .= $trAux;
                    $dataAux = "";
                }
                $cont ++;
            }
            
            //Traemos las imagenes de una linea incompleta al final 
            $trAux = $thumbTr;
            $trAux = preg_replace ( "/__TRITEMS__/", $dataAux, $trAux );
            $data .= $trAux;
            $dataAux = "";
            $thumbTable = preg_replace ( "/__TABLEITEMS__/", $data, $thumbTable );
            $thumbTable = preg_replace ( "/__CLAVE__/", "", $thumbTable );
            $thumbTable = preg_replace ( "/__NOMBRE__/", "", $thumbTable );
        }
        else
        {

            $this->setError ( 140, $ruta, __CLASS__ . " :: " . __LINE__ );
        }
        

        return $thumbTable;
    }
    
    public function getFileTable($ruta, $radioCheck = 1, $selected = "", $w = 40, $h = 40, $fileName = true, $perLine = 5, $id = "", $delete = false)
    {
    	
    	$data = "";
    	$dir = "";
    	if (! is_dir ( $ruta ))
    	{
    		return "noFiles";
    	}
    
    	//Cargamos los templates
    	$thumbTable = "";//$this->getTemplate ( "filesTable" );
    	$thumItem = "";
    
    	//$thumbItem = $this->getTemplate ( "fileItem");
    	$thumbItem = $this->getTemplate ( "fileItemRow");
    
    	$thumbTr = $this->getTemplate ( "fileLine" );
    	$thumbRadio = "";
    	//Si necesitamos un radio
    	if ($radioCheck == 1)
    	{
    		$thumbRadio = $this->getTemplate ( "fileRadio" );
    	}
    
    	//Si necesitamos un radio
    	if ($radioCheck == 2)
    	{
    		$thumbRadio = $this->getTemplate ( "fileCheck" );
    	}
    
    	$thumbDelete = "";
    	//Si necesitamos link para eliminar
    	if ($delete == true)
    	{
    		$thumbDelete = $this->getTemplate ( "deleteFile" );
    	}
    
    	$dataAux = "";
    
    	//Abrimos el directorio y si existe lo leemos
    	$dir = opendir ( $ruta );
    	$cont = 1;
    
    	$selAux = null;
    	if ($selected)
    	{
    		$selAux = explode ( ",", $selected );
    	}
    
    	if ($dir)
    	{
    		//Por cada archivo
    		while ( ($archivo = readdir ( $dir )) !== false )
    		{
    			if (! is_file ( $ruta . $archivo ))
    			{
    				continue;
    			}
    			$archivoName = $archivo;
    			$nameArray = explode(".", $archivoName);
    
    			$thumbItemAux = $thumbItem;
    			$ext = strtoupper(end($nameArray));
    			//if(!file_exists("imagenes/fileIcons/".$ext.".png"))
    			if(!file_exists(PWSREPOSITORY."imagenes/fileIcons/".$ext.".png"))
    			{
    				$ext = "Default";
    			}

    
    			$thumbItemAux = preg_replace ( "/__ICON__/", $ext, $thumbItemAux );
    			$thumbItemAux = preg_replace ( "/__FILEN__/", $archivoName, $thumbItemAux );
    
    			$strFileName = "";
    			if ($fileName)
    			{
    				$strFileName = $this->getTemplate ( "fileName" );
    				$strFileName = preg_replace ( "/__FILENAME__/", $archivo, $strFileName );
    			}
    
    			$thumbItemAux = preg_replace ( "/__FILENAME__/", $strFileName, $thumbItemAux );
    			$thumbRadioAux = "";
    
    			if ($radioCheck == 2)
    			{
    
    				$existe = in_array ( $archivo, $selAux );
    				$thumbRadioAux = $thumbRadio;
    				$val = "";
    				if ($existe)
    				{
    					$val = "CHECKED";
    				}
    				$thumbRadioAux = preg_replace ( "/__CHECKED__/", $val, $thumbRadioAux );
    			}
    
    			else
    			{
    				if ($selected != "" && ($selected == $archivo))
    				{
    					$thumbRadioAux = "";
    				}
    				else
    				{
    
    					$thumbRadioAux = $thumbRadio;
    				}
    			}
    			$thumbRadioAux = preg_replace ( "/__NOMBRE__/", $archivo, $thumbRadioAux );
    			$thumbItemAux = preg_replace ( "/__RADIO__/", $thumbRadioAux, $thumbItemAux );
    			$thumbDeleteAux = $thumbDelete;
    			$thumbDeleteAux = preg_replace ( "/__IMGNAME__/", $archivoName, $thumbDeleteAux );
    			$thumbDeleteAux = preg_replace ( "/__ID__/", $id, $thumbDeleteAux );
    
    			$thumbItemAux = preg_replace ( "/__DELETE__/", $thumbDeleteAux, $thumbItemAux );
    
    			$dataAux .= $thumbItemAux;
    
    			//Cada 5 hacemos una linea
    			if ($cont > 0 && $cont % $perLine == 0)
    			{
    				$trAux = $thumbTr;
    				$trAux = preg_replace ( "/__TRITEMS__/", $dataAux, $trAux );
    				$data .= $trAux;
    				$dataAux = "";
    			}
    			$cont ++;
    		}
    
    		//Traemos las imagenes de una linea incompleta al final
    		$trAux = $thumbTr;
    		$trAux = preg_replace ( "/__TRITEMS__/", $dataAux, $trAux );
    		$data .= $trAux;
    		$dataAux = "";
    		
    		$thumbTable = $cont > 1 ? $this->getTemplate ( "filesTable" ) : "";    		
    		$thumbTable = preg_replace ( "/__TABLEITEMS__/", $data, $thumbTable );
    		$thumbTable = preg_replace ( "/__CLAVE__/", "", $thumbTable );
    		$thumbTable = preg_replace ( "/__NOMBRE__/", "", $thumbTable );
    	}
    	else
    	{
    
    		$this->setError ( 140, $ruta, __CLASS__ . " :: " . __LINE__ );
    	}
    
    	return $thumbTable;
    }
    
    /**
     * PW3
     * Crea un thumb de la imagen guardada en una nueva ruta con los nuevos tamaños y calidad.
     * Soporta imagenes JPG, GIF, PNG
     *
     * @param String	$imagen 	Ruta a la imagen original
     * @param String	$thumb 		Ruta donde guardar el thumb
     * @param String 	$nombre 	Nombre del archivo
     * @param Integer	$w 				Ancho
     * @param Integer $h 				Alto
     * @param Integer $calidad 	Es la calidad de la imagen, si viene vacia le ponemos 90	 
     */
    public static function creaThumb($imagen, $thumb, $nombre, $w, $h, $calidad = 90)
    {
        //Checamos si envia un directorio
        if (is_dir ( $imagen ))
        {
            return false;
        }
        
        $info = getimagesize ( $imagen );
        
        if (! $info)
        {
            return false;
        }
        
        switch ($info ['mime'])
        {
            case 'image/jpeg' :
                $src = imagecreatefromjpeg ( $imagen );
                break;
            case 'image/gif' :
                $src = imagecreatefromgif ( $imagen );
                break;
            case 'image/png' :
                if ($calidad >= 10)
                {
                    $calidad = $calidad / 10;
                }
                if ($calidad == 10)
                {
                    $calidad = 9;
                }
                $src = imagecreatefrompng ( $imagen );
                break;
            default :
                return false;
        }
        
        $tmp = imagecreatetruecolor ( $w, $h );
        imagecopyresampled ( $tmp, $src, 0, 0, 0, 0, $w, $h, $info [0], $info [1] );        
        switch ($info ['mime'])
        {
            case 'image/jpeg' :
                imagejpeg ( $tmp, $thumb . $nombre, $calidad );
                break;
            case 'image/gif' :
                imagegif ( $tmp, $thumb . $nombre, $calidad );
                break;
            case 'image/png' :
                imagepng ( $tmp, $thumb . $nombre, $calidad );
                break;
        }
    }

   
    
    /**
     * PW3
     * Función que sube un rachivo al servidor en una ruta específica
     * @param String		$path		Ruta donde se guardarán los archivos, si es String lo convierte a array 
     * @param String		$delimiter	Caracter para leer la ruta
     * @param String		$newName 	Nombre del archivo con el que se va a guardar, si es un array, es un array de nombres
     */
    public static function uploadFile($path, $delimiter = "/", $newName = null)
    { 

       
        //Si la ruta no es un array, la convertimos en una
        if (! is_array ( $path ))
        {
            $pathArray = self::createPathArray ( $path, $delimiter );
        }

        //Se crea el path 
        $filePath = self::createPath( $pathArray );        
        
        if ($filePath)
        {
        	if (isset ( $_FILES ["archivos"] ))
            {
                $fileSize = $_FILES["archivos"]["size"];
                
                $ret = array ();
                $error = $_FILES ["archivos"] ["error"];
                
                //You need to handle  both cases
                //If Any browser does not support serializing of multiple files using FormData()
                if (! is_array ( $_FILES ["archivos"] ["name"] )) //single file
                {

                   
                    $fileName =  $_FILES ["archivos"] ["name"];                   
                    $fileName = PwFunciones::eliminaAcentos($fileName);
                    
                   
                    if($newName)
                    {
                        $type = self::getImageType( $_FILES ["archivos"] ["tmp_name"]);
                        $fileName = $newName.".$type";
                    }
                    $res =  move_uploaded_file ( $_FILES ["archivos"] ["tmp_name"], $filePath . $fileName );

                                       
                    $ret [] = $fileName;
                }
                else //Multiple files, file[]
                {
                    
                    $fileCount = count ( $_FILES ["archivos"] ["name"] );
                    for($i = 0; $i < $fileCount; $i ++)
                        {
                            $fileName = $_FILES ["archivos"] ["name"] [$i];
                            $fileName = PwFunciones::eliminaAcentos($fileName);
                            if($newName)
                            {
                                $type = self::getImageType( $_FILES ["archivos"] ["tmp_name"]);
                                $fileName = $newName.$i.".$type";
                            }
                           
                            move_uploaded_file ( $_FILES ["archivos"] ["tmp_name"] [$i], $filePath . $fileName );
                            $ret [] = $fileName;
                        }
                    }
                    return $ret;
            }
        }
    }

     /**
    * 
    * Limpia una ruta hasta el repositorio
    */
    public static function cleanPath($path, $delimiter = "/", $defaultFolder = "repository")
    {
        

        $resArray = explode($delimiter, $path);
        $resPath = array();
        $repFlag = false;

        foreach($resArray as $item)
        {
            if($item == $defaultFolder)
            {
                $repFlag = true;
            }

            if($repFlag == true )
            {
                $resPath[] = $item;
            }
        }

        $resPath = implode($delimiter, $resPath);
        return $resPath;
    }
    

    /**
    * PW3   
    * Crea un array con la ruta dada, cada elemento es un nivel
    */
    private static function createPathArray( $path, $delimiter)
    {
        $result = array();

        $resArray = explode($delimiter, $path);

        foreach($resArray as $item)
        {
            $result[] = $item.$delimiter;
        }
        return $result;
    }
    
    public function uploadFileDirect($path, $delimiter = "/", $newName = null)
    {
        
        
        //  error_log("Entro a subir archivo :: $delimiter :: $path");
        //  $this->getVardumpLog($_FILES);
        
        
        $filePath = $path;
        
        if ($filePath)
        {
            if (isset ( $_FILES ["archivos"] ))
            {
                
                
                $fileSize = $_FILES["archivos"]["size"];
                
                $ret = array ();
                $error = $_FILES ["archivos"] ["error"];
                
                //You need to handle  both cases
                //If Any browser does not support serializing of multiple files using FormData()
                if (! is_array ( $_FILES ["archivos"] ["name"] )) //single file
                {
                  
                  if($newName)
                   
                  {
                    $fileName = $newName;
                  }
                  else
                  {
                   // $fileName = str_replace(" ", "", $_FILES ["archivos"] ["name"]);                    
                    $fileName = PwFunciones::eliminaAcentos( $_FILES ["archivos"] ["name"]);
                  }
                    
                    $res =  move_uploaded_file ( $_FILES ["archivos"] ["tmp_name"], $filePath . $fileName );
                    
                    $ret [] = $fileName;
                }
                else //Multiple files, file[]
                {
                    $fileCount = count ( $_FILES ["archivos"] ["name"] );
                    for($i = 0; $i < $fileCount; $i ++)
                    {
                       // $fileName = $_FILES ["archivos"] ["name"] [$i];
                        $fileName = PwFunciones::eliminaAcentos( $_FILES ["archivos"] ["name"] [$i]);

                       
                        
                        move_uploaded_file ( $_FILES ["archivos"] ["tmp_name"] [$i], $filePath . $fileName );
                        $ret [] = $fileName;
                    }
                }
                return $ret;
            }
        }
    }
    
/**
     * 
     * Función que sube un archivo al servidor en una ruta específica, recibe el objeto archivo
     * @param String		$path				Ruta donde se guardarán los archivos, si es String lo convierte a array 
     * @param String		$delimiter	Caracter para leer la ruta
     */
    public function uploadSingleFile($path, $file, $delimiter = "/")
    {
        
        $result = false;
        //Si la ruta no es un array, la convertimos en una
        if (! is_array ( $path ))
        {
            $pathArray = $this->createPathArray ( $path, $delimiter );
        }
        
        //Se crea el path 
        $filePath = $this->createPath ( $pathArray );
        
        if ($filePath)
        {
            if (isset ( $file ))
            {   
                $ret = array ();
                $error = $file["error"];                
                $fileName = $file["name"];
                
                if(move_uploaded_file ( $file["tmp_name"], $filePath . $fileName ))
                {
                    $result = true;
                }    
            }
            else
            {
                $this->setError ( 144, __FUNCTION__, __CLASS__ );
                $result = false;
            }
        }
        else
        {
            $this->setError ( 131, __FUNCTION__ . " :: $path", __CLASS__ );
            $result = false;
            
        }
        
        return $result;
    }
    
    /**
     * PW3
     * Borra un archivo en una ruta dada
     * @param String $path      Ruta del archivo a eliminar     
     */
    public static function deleteFile($path)
    {
    	
    	if (file_exists ( $path ))
    	{
    		unlink ( $path );
    	}
    	return true;        
        
    }
    
    /**
     *
     * Elimina un los archivos de un folder
     * @param String $path	Ruta al dorectorio a borrar
     */
    public function deleteFolderContent($path)
    {
        if(is_dir($path))
        {
            $items = scandir($path);
            foreach ($items as $item)
            {
                //error_log("Borro para :: ".$path."/".$item);
                //Revisamos si el archivo existe ya que pudo haber sido borrado al revisar el directorio
                if (is_file($path."/".$item))
                {
                   // error_log("Borro para :: $path/$item");
                    $this->deleteFile($path."/", $item);                                     
                }
            }
            
        }
        else
        {
            $this->setError(144, $path, __CLASS__);
        }
    }
    

    /**
     * 
     * Elimina un folder y su contenido recursivamente
     * @param String $path	Ruta al dorectorio a borrar
     */
     public static function deleteFolder($path)
     {   
         
         if(is_dir($path))
         {            
             $items = scandir($path);
             foreach ($items as $item)
             {
 
                 if($item != "." && $item != "..")
                 {
 
                     //Si es directorio, entro recursivamente
                     if(filetype($path.$item) == "dir")
                     {
                         self::deleteFolder($path.$item."/");    
                        
                     }
                     //Si es archivo lo borro
                     else
                     {
                         self::deleteFile($path.$item);
                     }
                 
                 }
             }
 
             //Si no hay mas directorio u archivos, borro el directrorio
             reset($items);
             rmdir($path);
         }
         else
         {
            self::setError(144, $path, __CLASS__);
         }
     }
    
    /**
     * 
     * Elimina un folder y su contenido recursivamente
     * @param String $path	Ruta al dorectorio a borrar
     */
   /* public function deleteFolder($path)
    {   
        if(is_dir($path))
        {
            $items = scandir($path);
            foreach ($items as $item)
            {
                if($item != "." && $item != "..")
                {
                    if(filetype($path.$item) == "dir")
                    {
                        $this->deleteFolder($path.$item."/");
                    }
                    //Revisamos si el archivo existe ya que pudo haber sido borrado al revisar el directorio
                    if (file_exists($path.$item))
                    {
                        if(filetype($path.$item) == "file")
                        {
                            $this->deleteFile($path, $item);
                        }
                    }
                }
            }
            rmdir($path);
        }
        else
        {
            $this->setError(144, $path, __CLASS__);
        }
    }*/
    
    public function downloadFile($nombreArchivoBajar, $nuevoArchivo)
    {
        header ( "Content-Disposition: attachment; filename=$nombreArchivoBajar" );
        header ( "Content-Type: application/force-download" );
        header ( "Content-Length: " . filesize ( $nuevoArchivo ) );
        readfile ( $nuevoArchivo );
    }
    
    /**
     * Función que crea un archivo básico para escribir en el
     * @param String	$ruta	Ruta donde va a vivir el archivo, debe de finalizar con \\ o //
     * @param String	$nombre	Nombre del archivo
     * @param String	$modo	Modo de como vamos a abir el archivo
     */
    public function createFile($ruta, $nombre, $contenido, $modo = 'w')
    {
        $fp = fopen ( $ruta . $nombre, 'w' );
        fwrite ( $fp, utf8_decode ( $contenido ) );
        fclose ( $fp );
    
    }
    
    /**
     * Función que genera los recibos para la ejecución de las validaciones
     * @param String 	$ruta			Ruta donde se guardará el recibo 
     * @param String	$nombreArchivo	Nombre del archivo que se va a guardar
     * @param String	$mensaje		Mensaje a guardar en el archivo
     */
    public function generaRecibo($ruta, $nombreArchivo, $mensaje)
    {
        $pathArray = $ruta;
        
        //Si la ruta no es un array, la convertimos en una y creamos la ruta
        if (! is_array ( $ruta ))
        {
            $pathArray = $this->createPathArray ( $ruta, "\\" );
            $filePath = $this->createPath ( $pathArray );
        }
        if (! $filePath)
        {
            $this->setError ( 140, __FUNCTION__, __CLASS__ );
        }
        
        $mensaje = strip_tags ( $mensaje );
        $this->createFile ( $ruta, $nombreArchivo . ".txt", $mensaje );
    
    }
    
    
    
    private function uploadFtpFile()
    {

      $key = $this->funciones->getVariable("key");

      if($_FILES)
      {

        $fileSize = $_FILES["archivo"]["size"][0];
        $res = false;

       // 800000
        if($fileSize > 300000)
        {

	     $ftp = $this->funciones->getClass("ftp", "ftp.coyotepec.gob.mx");

	   	 if($ftp && $ftp->ftp_login("coyotepe@coyotepec.gob.mx",'jD0A$R9SE'))
	  	 {

            $ftp->ftp_pasv(true) ;
	    	$directorio = $ftp->ftp_pwd();
	    	$directorioAux = "../files/documentos/documentos$key";
	    	if(!file_exists($directorioAux))
	    	{
	    	  $ftp->ftp_mkdir($directorio."public_html/transparencia/files/documentos/documentos$key") ;
	    	}

	    	$ftp->ftp_chdir($directorio."public_html/transparencia/files/documentos/documentos$key");
	    	$directorio = $ftp->ftp_pwd();

	    	//$archivos = $ftp->ftp_rawlist ($directorio );
	    	$archivos = $ftp->ftp_nlist($directorio);


	    	foreach ($archivos as $archivo )
	    	{
	    	  if($archivo == "." || $archivo == "..")
	    	  {
	    	   // echo "Salto $archivo <br>";
	    	    continue;
	    	  }

	    	  $ftp->ftp_delete($archivo);
	    	 // echo "Borro $archivo <br>";
	    	}



	        $rutaRemota = "$directorio/".$_FILES["archivo"]["name"][0];
	        $rutaLocal =  $_FILES["archivo"]["tmp_name"][0];
	        $nombreArchivo = $_FILES["archivo"]["name"][0];

	        $upload = $ftp->ftp_put($rutaRemota, $rutaLocal, FTP_BINARY);
	        if($upload)
	        {
	          $datos = "`archivo` = '$nombreArchivo'";
	          $keyFields = array("`key`" => "'$key'");
			  $res = $this->sql->updateData($this->connection, "documentos", $datos, $keyFields);
	        }
	       // echo "Cargo Archivo: $upload <br>";

	        $ftp->ftp_close();
	  	 }

        }

        else
        {

         // echo "Ejecuto normal";
          $res = $this->doInsertFiles($key, $_FILES);
        }
       //

        if($res)
        {
          return $this->getTemplate("succes");
        }
        else
        {
          return $this->getTemplate("errorFile");
        }
	  }

	  return $this->getTemplate("errorFile");

    }
    
    
    /*public function fixFiles( $arr )
    {
      foreach( $arr as $key => $all )
      {
        
        foreach( $all as $i => $val )
        {
          
            $new[$i][$key] = $val;    
        }    
      }
      return $new;
  }*/
    
    
    private function getImageType($imagen)
    {
        $info = getimagesize ( $imagen );
        
        if (! $info)
        {
            return false;
        }
        
        $data = "";
        
        
        switch ($info ['mime'])
        {
            case 'image/jpeg' :
              $data = "jpg";
                break;
            case 'image/gif' :
              $data = "gif";
                break;
            case 'image/png' :
                $data = "png";
                break;
            default :
                return false;
        }return $data;
        
    }
    
    
    public function getArrayDirectoryItems($ruta,  $folders = false, $files = false)
    {    
    
    	$folderItems = scandir($ruta);
    	
    	$folderData = array("FOLDERS" => array(), "FILES"=> array());
    	foreach ($folderItems as $item)
    	{    
    		if($item == "." || $item == "..")
    		{
    			continue;
    		}
    			
    		if($folders == true)
    		{
    			if(is_dir($ruta."/$item"))
    			{    				
    				$folderData["FOLDERS"][] = $item;
    			}
    		}
    			
    		if($files == true)
    		{
    			if(is_file($ruta."/$item"))
    			{
    				$folderData["FILES"][] = $item;
    			}
    		}
    	}
    
    	return $folderData;
    
    }
    
    /**
     * Regresa un array con los directorios contenidos en la ruta especificada
     * @param String 	$ruta	Ruta que se va a leer
     * @return Array	Array con los directorios de la ruta
     */
    public static function getGlobDirectoryItems($ruta)
    {
       $folderData = glob($ruta . '/*' , GLOB_ONLYDIR);
    	 return $folderData;
    
    }

    
    /**
     * Regresa un array con los directorios contenidos en la ruta especificada
     * @param String 	$ruta	Ruta que se va a leer
     * @return Array	Array con los directorios de la ruta
     */
    public function getGlobDirectoryItemsRecursive($ruta)
    {
    	//error_log($ruta);
    	//$nivel = $this->getFolderLevel($ruta);
    	$folderData = glob($ruta . '/*' , GLOB_ONLYDIR);
    	foreach($folderData as $item)
    	{
    		
    		$folderAux = $this->getGlobDirectoryItemsRecursive($item);
    		$folderData = array_merge($folderData, $folderAux);
    	}

    	
    	return $folderData;
    
    }
    

    /**
     * Regresa un array con los directorios contenidos en la ruta especificada
     * @param String 	$ruta	Ruta que se va a leer
     * @return Array	Array con los directorios de la ruta
     */
    public function getGlobDirectoryItemsRecursiveInicial($ruta, $validMonths, $validMonthsEng, $tipoOperadora)
    {
    	$folderData = glob($ruta . '/*' , GLOB_ONLYDIR);
    	
	    foreach($folderData as $key=> $item)
	    {
	    	
	    	if($tipoOperadora == 1)
	    	{
		    	$nivel = $this->getFolderLevel($item);		    

		    	//Solo pone los del año en curso
		    	if($nivel == 2)
		    	{
		    		$folderName = basename($item);
		    			
		    		if(!isset($validMonths[$folderName]))
		    		{		    				
		    			$deleteKey = array_search($item, $folderData);
		    			unset($folderData[$deleteKey]);
		    			continue;
		    		}
		    	}
		    	
		    	if($nivel == 3)
		    	{	 
		    		$folderName = basename($item);		    			 
		    		$folderAux = explode("/", $item);
		    		$año = $folderAux[2];
		    		if(!in_array($folderName, $validMonths[$año]))
		    		{
		    			$deleteKey = array_search($item, $folderData);
		    			unset($folderData[$deleteKey]);
		    			continue;
		    		}
		    	}
		    	$folderAux = $this->getGlobDirectoryItemsRecursiveInicial($item,$validMonths,$validMonthsEng, $tipoOperadora);
		    	$folderData = array_merge($folderData, $folderAux);
	    	}
	    	if($tipoOperadora == 2)
	    	{
	    		$nivel = $this->getFolderLevelMandatos($item);
	    		
	    		//Solo pone los del año en curso
	    		if($nivel == 3)
	    		{
	    			$folderName = basename($item);
	    			 
	    			if(!isset($validMonthsEng[$folderName]))
	    			{
	    				$deleteKey = array_search($item, $folderData);
	    				unset($folderData[$deleteKey]);
	    				continue;
	    			}
	    		}
	    		if($nivel == 4)
	    		{
	    			$folderName = strtoupper(basename($item));
	    			$folderAux = explode("/", $item);
	    			$año = $folderAux[3];
	    			if(!in_array($folderName, $validMonthsEng[$año]))
	    			{	    				
	    				$deleteKey = array_search($item, $folderData);
	    				unset($folderData[$deleteKey]);
	    				continue;
	    			}
	    		}
	    		$folderAux = $this->getGlobDirectoryItemsRecursiveInicial($item,$validMonths,$validMonthsEng, $tipoOperadora);
	    		$folderData = array_merge($folderData, $folderAux);
	    	}
	    }

	    return $folderData;
    }
    
    
    
    
    
    
   /* public function getGlobDirectoryItemsRecursiveInicial_2($ruta, $validMonths)
    {
    	error_log("Glod directory $ruta");
    	
    //	die();
    	$folderData = glob($ruta . '/*' , GLOB_ONLYDIR);
    	foreach($folderData as $item)
    	{
    		$nivel = $this->getFolderLevel($item);

    		//Solo pone los del año en curso    		
    		if($nivel == 2)
    		{
    			$folderName = basename($item);
    			error_log($folderName);
				
    			if(!isset($validMonths[$folderName]))
    			{
    				error_log("No valido");
    				continue;
    			}
    			error_log("Valido");
    			die();
    		}
    		if($nivel == 3)
    		{
    			
    			$folderName = basename($item);
    			error_log($folderName);
    			
    			$folderAux = explode("/", $item);
    			$año = $folderAux[2];
    			if(!in_array($folderName, $validMonths[$año]))    			
    			{
    				continue;
    			}
    			//error_log("$item :: $folderName :: $nivel :: $año");
    		}
    		$folderAux = $this->getGlobDirectoryItemsRecursiveInicial($item,$validMonths);
    		$folderData = array_merge($folderData, $folderAux);
    	}
    	 
    	return $folderData;    
    }*/
    
    public function getGlobDirectoryItemsRecursive2($ruta, $arrayRes)
    {

    	$nivel = $this->getFolderLevel($ruta)+1;
    	$folderData = glob($ruta . '/*' , GLOB_ONLYDIR);
    	foreach($folderData as $item)
    	{
    		$arrayRes[] = "$item::$nivel";
    		$arrayRes = $this->getGlobDirectoryItemsRecursive2($item, $arrayRes);
    	}
    		 
    	return $arrayRes;
    
    }
    
    
    /**
     * Regresa un array con los archivos de unacarpeta recursivamente
     * @param String 	$ruta	Ruta que se va a leer
     * @return Array	Array con los directorios de la ruta
     */
  public function getDirectoriesIterator($ruta)
  {
  	
  	$result = array();
    $iterator = new RecursiveIteratorIterator( 
       new RecursiveDirectoryIterator($ruta, RecursiveDirectoryIterator::SKIP_DOTS),  
       RecursiveIteratorIterator::SELF_FIRST);

    foreach($iterator as $file) 
    {
    	if($file->isDir()) 
    	{
       	$result[] = $file;
       } 
    }

    return $result;
  }
  

  /**
   * Función que regresa un array con los nombres de los archivos encontrados en la ruta dada
   * @param String	$ruta	Ruta fisica donde buscar el archivo
   * @return String	$result Regresa el String con la ruta
   */
  public static function getFilePath($ruta)
  {
  	//$ruta = preg_replace("/__ID__/", $id, $ruta);
  	
  	$dir = opendir ( $ruta );
  	$archivoName = array();
  
  	if ($dir)
  	{
  		//Por cada archivo
  		while ( ($archivo = readdir ( $dir )) !== false )
  		{
  			//error_log($ruta.$archivo . " :: " . is_file ( $ruta . $archivo ));
  			if (! is_file ( $ruta . $archivo ))
  			{
  				continue;
  			}
  			else
  			{
  				$archivoName[] = $archivo;
  			}
  		}
  	}
  
  	return $archivoName;
  }
  
  /**
   * Función que regresa un array con los nombres de los archivos encontrados en la ruta dada
   * @param String	$ruta	Ruta fisica donde buscar el archivo
   * @return String	$result Regresa el String con la ruta
   */
  public static function getFileNameArrayFromPath($ruta)
  {
  	//$ruta = preg_replace("/__ID__/", $id, $ruta);
  	 
  	$dir = opendir ( $ruta );
  	$archivoName = array();
  
  	if ($dir)
  	{
  		//Por cada archivo
  		while ( ($archivo = readdir ( $dir )) !== false )
  		{
  			//error_log($ruta.$archivo . " :: " . is_file ( $ruta . $archivo ));
  			if (! is_file ( $ruta . $archivo ))
  			{
  				continue;
  			}
  			else
  			{
  				$archivoName[] = $archivo;
  			}
  		}
  	}
  
  	return $archivoName;
  }
  
  
  
  /**
     * Función que regresa un array con los nombres de los archivos encontrados en la ruta dada
     * @param String	$ruta	Ruta fisica donde buscar el archivo
     * @return String	$result Regresa el String con la ruta
     */
    public static function getFileNameFromPath($ruta, $archivoName = "default.jpg")
    {
    	//$ruta = preg_replace("/__ID__/", $id, $ruta);
   // 	$archivoName = "default.jpg";

    	if(!is_dir($ruta))
    	{
    	
    		return $archivoName;
    	}
    	 
    	$dir = opendir ( $ruta );
    	 
    
    	if ($dir)
    	{
    		//Por cada archivo
    		while ( ($archivo = readdir ( $dir )) !== false )
    		{
    			//error_log($ruta.$archivo . " :: " . is_file ( $ruta . $archivo ));
    			if (! is_file ( $ruta . $archivo ))
    			{
    				continue;
    			}
    			else
    			{
    				$archivoName = $archivo;
    				break;
    			}
    		}
    	}
    
    	return $archivoName;
    }
  
  public static function getImageCopy($imagePath, $id, $w = 100, $h=100, $tipoDefault = 1,  $imageName = null, $crop = false)
  {
  
    //Armo el repositorio
  	$imagePathAux = "repository/".$imagePath."$id/";
  	$rutaImage = PWSREPOSITORY.$imagePath."$id/";
      $image = "";
    
    //Si no viene el nombre, tomo lap rimera 
    if($imageName == null)  
    {
        $imageName = self::getFileNameFromPath($rutaImage);       
    }

    
  	
  	if($imageName == "default.jpg")
  	{
  		switch($tipoDefault)
  		{
  			//Para grid de productos
  			case 2:
  				$image = $imagePath."pgdefault.jpg";
  				break;
  			case 3:
  				$image = $imagePath."pldefault.jpg";
  				break;
  			case 4:
  				$image = $imagePath."cardefault.jpg";
  				break;
  			default :
  				$image = $imagePath.$imageName;
  				break;
  		}
  	}
  	else
  	{
  
  		$pathArray = array ($rutaImage, "thumbs/",$w."x".$h."/" );
  		$rutaThumb = self::createPath ( $pathArray );
  		$thumbAux = "thumbs/".$w."x".$h."/";
  		$newFile = $rutaThumb.$imageName;
  		$newFileAux = $imagePathAux.$thumbAux.$imageName;
  		
  		if(!is_file($newFile))
  		{
            
            if($crop == true)
            {
                self::creaThumbCrop( $rutaImage.$imageName, $rutaThumb, $imageName, $w, $h);
            }
            else
            {
                self::creaThumb ( $rutaImage.$imageName, $rutaThumb, $imageName, $w, $h);
            }
  			
  			$image = $imagePathAux."thumbs/$w"."x"."$h/".$imageName;
  		}
  		else
  		{              
  			$image = $newFileAux;
  		}
  	}  	
  
  	return $image;
  }

  public static function getImageCopy2($imagePath, $id, $w = 100, $h=100, $tipoDefault = 1,  $imageName = null)
  {    
  
      //$rutaImage = $imagePath."$id/";
      $rutaImage = PWSREPOSITORY.$imagePath."$id/";
      $image = "";
      if(!$imageName)
      {
          $imageName = self::getFileNameFromPath($rutaImage);
      }
      
      
      if($imageName == "default.jpg")
      {
          
          switch($tipoDefault)
          {
              //Para grid de productos
              case 2:
                  $image = $imagePath."pgdefault.jpg";
                  break;
                  case 3:
                  $image = $imagePath."pldefault.jpg";
                  break;
                  case 4:
                      $image = $imagePath."cardefault.jpg";
                      break;
              default :
                  $image = $imagePath.$imageName;
                  break;
          }
      }
      else
      {
          
          $pathArray = array ($rutaImage, "thumbs/",$w."x".$h."/" );
          $rutaThumb = self::createPath ( $pathArray );
          $newFile = $rutaThumb.$imageName;
  
          if(!is_file($newFile))
          {
              self::creaThumb ( $rutaImage.$imageName, $rutaThumb, $imageName, $w, $h);
              $image = $newFile;
          }
          else
          {    			
              $image = $newFile;
          }
      }    	
      return $image;
  }
  
  
  
	public function getImagePath($imagePath, $id)
  {
    
   	$imagePathAux = "repository/".$imagePath."$id/";
   	$rutaImage = PWSREPOSITORY.$imagePath."$id/";
    	
   	$image = "";
   	$imageName = $this->getFileNameFromPath($rutaImage);
    	
   	if($imageName == "default.jpg")
   	{
    
   		$image = "repository/".$imagePath.$imageName;
   	}
   	else
   	{
    		
   		$image = $imagePathAux.$imageName;	
    }
    return $image;
   }
   
   public function getFileName($filePath, $id)
   {
   
   	$filePathAux = "repository/".$filePath."$id/";
   	$rutaFile = PWSREPOSITORY.$filePath."$id/";
   	 
   	$file = "";
   	$fileName = $this->getFileNameFromPath($rutaFile, "default");
   	 
   	if($fileName == "default")
   	{
   
   		$file = $fileName;
   	}
   	else
   	{
   
   		$file = $filePathAux.$fileName;
   	}
   	return $file;
   }
   
    

  

/**
 * Resize image maintaining aspect ratio, occuping
 * as much as possible with width and height inside
 * params.
 *
 * @param $image
 * @param $width
 * @param $height
 */
 public static function resizeMax($image, $width, $height)
 {
   /* Original dimensions */
   $origw = imagesx($image);
   $origh = imagesy($image);

   

   if($origw < $origh)
   {
       $hAux = $origh;
       $origh = $origw;
       $origw = $hAux;

       $wAux = $width;
       $height =  $width;
       $width = $wAux;

   }

 
   $ratiow = $width / $origw;
   $ratioh = $height / $origh;
   $ratio = min($ratioh, $ratiow);
 
   $neww = $origw * $ratio;
   $newh = $origh * $ratio;
 
   $new = imageCreateTrueColor($neww, $newh);


   $thumb = PWSREPOSITORY."imagenes/portfolio/1/thumbs/";
   imagecopyresampled($new, $image, 0, 0, 0, 0, $neww, $newh, $origw, $origh);

  //error_log("Thumb con mime  $thumb$nombre  :: ".$new['mime']);
  imagejpeg ( $new, $thumb . $nombre, $calidad );
  switch ($new['mime'])
  {
     
      case 'image/jpeg' :
        
          imagejpeg ( $new, $thumb . $nombre, $calidad );
          break;
      case 'image/gif' :
          imagegif ( $new, $thumb . $nombre, $calidad );
          break;
      case 'image/png' :
          imagepng ( $new, $thumb . $nombre, $calidad );
          break;
  }
   return $new;
 }


 /**
 * Trae la spropiedades de una imagen
 */
 public static function imgCreate($filename)
 {
     //error_log("Code :: ".mb_detect_encoding($filename));
     $filename = utf8_decode($filename);
     //$filename=iconv("UTF-8", "ISO-8859-1",$filename);
     //error_log("filename2  :: $filename");

     if(!is_file($filename))
     {
        return false;
     }
     $size = getimagesize($filename);
     $calidad = 90;
     if ($size['mime']=='image/jpeg')
     {
         $image = imagecreatefromjpeg($filename);
     }
     if ($size['mime']=='image/png')
     {
         $image = imagecreatefrompng($filename);
         $calidad =  9;
     }
     if ($size['mime']=='image/gif')
     {
         $image = imagecreatefromgif($filename);
         $calidad =  9;
     }

     return  ["image" => $image, "calidad" => $calidad, "mime" => $size["mime"]];


 
}


 /**
 *  Regresa la imagen a proporciones y recortada para que respete el aspecto
 *
 */
 public static function creaThumbCrop($srcImage , $thumb, $nombre, $width ,$height, $displ = 'center')
 {

   
   $imgData = self::imgCreate($srcImage);

   if($imgData == false)
   {
       return false;
   }

   $imagen = $imgData["image"];

   /* Original dimensions */
   $origw = imagesx($imagen);
   $origh = imagesy($imagen);
 
   $ratiow = $width / $origw;
   $ratioh = $height / $origh;
   $ratio = max($ratioh, $ratiow); /* This time we want the bigger image */
 
   $neww = $origw * $ratio;
   $newh = $origh * $ratio;
 
   $cropw = $neww-$width;
   /* if ($cropw) */
   /*   $cropw/=2; */
   $croph = $newh-$height;
   /* if ($croph) */
   /*   $croph/=2; */
 
   if ($displ=='center')
     $displ=0.5;
   elseif ($displ=='min')
     $displ=0;
   elseif ($displ=='max')
     $displ=1;
 
   $new = imageCreateTrueColor($width, $height);
 
   imagecopyresampled($new, $imagen, -$cropw*$displ, -$croph*$displ, 0, 0, $width+$cropw, $height+$croph, $origw, $origh);


   switch ($imgData ['mime'])
   {
       case 'image/jpeg' :
           imagejpeg ( $new, $thumb . $nombre, $imgData["calidad"] );
           break;
       case 'image/gif' :
           imagegif ( $new, $thumb . $nombre, $imgData["calidad"] );
           break;
       case 'image/png' :
           imagepng ( $new, $thumb . $nombre, $imgData["calidad"] );
           break;
   }

 }




 public static function eliminaAcentos($cadena){
        
    //error_log("Elimino acentos para $cadena");
    $no_permitidas= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
    $permitidas= array ("a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
    $texto = str_replace($no_permitidas, $permitidas ,$cadena);
    
    return $texto;
    
    
}
 


    /**
    * PW3
    */
    private static function getTemplate($name)
    {
        $template = array();
        
           $template ["thumbItem"] = <<< TEMP
<div class="col-lg-3 col-md-4 col-xs-6 thumb">
	<img src="__THUMBPATH__" class="img-thumbnail">
 	__FILENAME__ __RADIOCHECK__ __DELETE__
</div>		
TEMP;

            $template ["thumbItemSolo"] = <<< TEMP
<div class="col-lg-12 col-md-12 col-xs-12 thumb">
	<img src="__THUMBPATH__" class="img-thumbnail">
 	__FILENAME__ __RADIOCHECK__ __DELETE__
</div>		
TEMP;

     $template ["thumbItemLightBox"] = <<< TEMP
<div class="col-lg-3 col-md-4 col-xs-6 thumb">
	<a href = "__IMGPATH__" data-lightbox="gallery__NUM__"> 
		<img src="__THUMBPATH__" class = "img-thumbnail">
 		 </a>__FILENAME__ __RADIOCHECK__ __DELETE__ 
</div>	
		
TEMP;
        
        $template ["fileName"] = <<< TEMP
<br>__FILENAME__</br>
TEMP;

        
       $template ["deleteIcon"] = <<< TEMP
<a id="deleteThumb" title="Eliminar" href="#" onclick="deleteImage('__IMGNAME__', '__ID__', '__PARAMS__');return false;" ><img src = "pw/imagenes/icons/delete.png"></a> 

TEMP;
        
        
        
         
        $template ["thumbLine"] = <<< TEMP
<tr>
__TRITEMS__
</tr>
				
TEMP;
        
     
        
        $template ["thumbRadio"] = <<< TEMP
<input type= "radio" name = "imgThumb" id = "imgThumb" value ="__NOMBRE__">	
TEMP;

          $template ["thumbCheck"] = <<< TEMP
<input type= "checkBox" name = "imgThumb" id = "imgThumb" value ="__VALUE__">	
TEMP;

         $template ["fileTable"] = <<< TEMP
		
<table class = "thumbTable"  >
<thead>
  <tr>
     <th>__CLAVE__ </th>
     <th>__NOMBRE__</th>
  </tr>
 </thead>
__TABLEITEMS__
</table>
		
TEMP;
           
           $template ["filesTable"] = <<< TEMP
           
<table class = "table table-bordered table-hover" id="generalDataTable">
<thead>
  <tr>
     <th>Tipo </th>
     <th>Nombre</th>
     <th>Acciones</th>
  </tr>
 </thead>
__TABLEITEMS__
</table>
           
TEMP;
           $template ["fileItemRow"] = <<< TEMP
<tr>
	<td class = "fileItem"><img src = "pw/imagenes/fileIcons/__ICON__.png" class = "fileIcon" alt = "__FILEN__" title = "__FILEN__" width="50" height="50">__RADIO__</td>
	<td>__FILENAME__</td>
	<td>__DELETE__</td>
</tr>
TEMP;

          $template ["fileItem"] = <<< TEMP
<td class = "fileItem" width= "50"><img src = "pw/imagenes/fileIcons/__ICON__.png" class = "fileIcon" alt = "__FILEN__" title = "__FILEN__">__RADIO__ __FILENAME__ __DELETE__</td>		
TEMP;

          $template ["fileRadio"] = <<< TEMP
<br><input type= "radio" name = "imgThumb" id = "imgThumb" value ="__NOMBRE__" >	
TEMP;
        
        $template ["fileCheck"] = <<< TEMP
<br><input type= "checkbox" name = "imgThumb" id = "imgThumb" value ="__NOMBRE__"  __CHECKED__>	
TEMP;
        

                $template ["deleteFile"] = <<< TEMP
<br><a id="deleteFile" title="Eliminar" href="#" onclick="deleteFile(__ID__,'__IMGNAME__');return false;" class = "llink"><img src = "pw/imagenes/icons/delete.png"></a>

TEMP;


         $template ["fileLine"] = <<< TEMP
<tr>
__TRITEMS__
</tr>				
TEMP;



$template ["cardGroup"] = <<< TEMP
<div class = "card-group m-3">
    __ITEMS__
</div>
TEMP;


$template ["imagePanel"] = <<< TEMP
<script>

$(document).ready(function()
{

   
    $( "#sortable" ).sortable({
        
        stop: function( event, ui ) 
        {         
            var imagesArr    = [];
            // get image ids order
            $('#sortable li').each(function()
            {              
               var id = this.id;
               var split_id = id.split("_");
               imagesArr.push(split_id[1]);
            });

            //alert(imagesArr);
            addImageLists(imagesArr, '__PARAMS__')
        }
    });

  

});

</script>

    <div style = "margin-left:8px;">
    <ul id="sortable" >
        __ITEMS__
    </ul>
    </div>


TEMP;


$template ["imagePanelLi"] = <<< TEMP
<li class="ui-state-default" id="image___SORT__">
<img src="__IMAGE__" title="__NAME__" >
<!--<p><a href="#" class="btn btn-red btn-xs" id="deleteThumb"  title="Eliminar" href="#" onclick="deleteImage('__IMGNAME__',__ID__, '__PARAMS__');return false;">Eliminar</a></p>-->
<!--<p><a id="deleteThumb" title="Eliminar" href="#" onclick="deleteImage('__IMGNAME__', '__ID__', '__PARAMS__');return false;" ><img src = "pw/imagenes/icons/delete.png"></a> </p>-->
<p><a href = "#" onclick="deleteImage('__IMAGEPATH__', '__PARAMS__');return false;" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a></p>




</li>
TEMP;

        
        
        return $template [$name];
    }
}

?>