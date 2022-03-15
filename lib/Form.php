<?php
namespace Pitweb;
use Pitweb\Funciones as PwFunciones;
//use Pitweb\Security as PwSecurity;
use Pitweb\Sql as PwSql;
use Pitweb\Form as PwForm;


/**
 * Clase encargada de ejecutar funciones para formas html
 * en automático
 * @author pcalzada
 *
 */
class Form 
{
    
    /**
     * Nombre de la clase
     * @var String  - Nombre de la clase 
     */
    //private $className;
    

    function __construct()
    {
      
    }
    
    /** 	 
     * Función que genera un combo con los valores dados por un array
     * @param String 	$name					Nombre del combo
     * @param Array		$arrValues				Array con los valores que vamos ausar, debe de estar en la forma $key=>$value
     * @param Bolean	$space					Si se quiere un espacio al inicio del combo
     * @param Integer 	$value					Valor del combo preseleccionado
     * @param String	$formName				Nombre de la forma para enviar en onChangeSubmit
     * @param Boolean	$onChangeSubmit			Nos dice si se envia o no el formulario al tener un cambio
     * @param String 	$onChangeFunction		Nombre de la función en JS que llamamos al tener un cambio
     * @param Boolean	$todos					Bandera para poner espacio en blanco o la palabra Todos al principio del comobo
     */
    
    public static function getArraySelect($name, $arrValues, $space = false, $value = false, $formName = false, $onChangeSubmit = false, $onChangeFunction = false, $class = "", $todos = false, $extraParams = false)
    {
        
        $select = self::getTemplate ( "select" );
        $select = preg_replace ( "/__CLASS__/", $class, $select );
        $select = preg_replace ( "/__NAME__/", $name, $select );
        
        $templateOptions = self::getTemplate ( "option" );
        
        $options = "";
        if ($space)
        {
            $options = self::getTemplate ( "nullOption" );
        }
        
        if($todos && !$space){
        	$options .= self::getTemplate ( "todosOption" );
        }
        
        
        $select = preg_replace ( "/__EXTRAPARAMS__/", $extraParams ? $extraParams : "", $select );        
        
        
        
        if(!$arrValues)
        {
            PwFunciones::setLogError(14);
            $select = preg_replace ( "/__OPTIONS__/", "", $select );
            $select = preg_replace ( "/__ONCHANGE__/", "", $select );            
            return $select;
        }
        
        
        foreach ( $arrValues as $key => $dataValue )
        {
            $aux = $templateOptions;
            $aux = preg_replace ( "/__VALUE__/", $key, $aux );
            $aux = preg_replace ( "/__SELECTED__/", $key == $value ? " SELECTED" : "", $aux );
            $aux = preg_replace ( "/__TEXT__/", $dataValue, $aux );
            $options .= $aux . "\n";
        }
        
        $select = preg_replace ( "/__OPTIONS__/", $options, $select );
        
        $changeSubmit = "";
        if ($onChangeFunction)
        {
            $changeSubmit = self::getTemplate ( "onChange" );
            $changeSubmit = preg_replace ( "/__FUNCTION__/", $onChangeFunction, $changeSubmit );
        }
        
        if ($onChangeSubmit)
        {
            $changeSubmit = self::getTemplate ( "changeSubmit" );
            $changeSubmit = preg_replace ( "/__FORMNAME__/", $formName, $changeSubmit );
        }
        
        $select = preg_replace ( "/__ONCHANGE__/", $changeSubmit, $select );
        
        
        
        /*if($onChangeSubmit)
		{		  
		  $select = preg_replace("/__ONCHANGE__/", "onChange='document.$formName.submit()'", $select);
		}
  	*/
        
        return $select;
    }


    


    
    
    
    /**
     * Genera el código para crear un select con campos de una base de datos. 
     * Puede llamar a una función en js para hacerlo dinámico
     * 
     * @param Object	$mainObj		Objeto principal del sistema
     * @param String	$table			Tabla a la que se conecta
     * @param String 	$name			Nombre que va a recibir el campo
     * @param String 	$key			Llave para el select
     * @param String 	$fld			Campo que mostrara el texto del select
     * @param Boolean	$space			Si se quiere un espacio al inicio del combo
     * @param boolean 	$selected 		Condición para que aparezca preselecionado algun elemento, puede ser un valor o un array de valores
     * @param String 	$order 			Campo para hacer el ordenamiento del select
     * @param Array 	$condition 		Array con las condiciones para el query
     * @param String 	$onChange 		Si existe, llama una funcion en js al cambiar
     * @param String 	$class 			La clase CSS para agregar al select
     * @param String 	$subString 		Limita el tamaño del texto a mostrar en el select	
     *
     * @return Regresa el html necesario para el select
     */
    public static function getSelect($connection, $table, $name, $key, $fld, $space = false, $selected = false, $order = false, $condition = null, $onChange = false, $class = "", $substring = false, $extraParams = false, $todos = false)
    {

        if ($table)
        {
            $fields = array ($key, $fld );

            $sqlData = PwSql::executeQuery ($connection, $table, $fields, $condition, $order );

            if ($sqlData)
            {
                
                $select = self::getTemplate ( "select" );
                $select = preg_replace ( "/__CLASS__/", $class, $select );

                $change = "";
                if ($onChange)
                {
                    $change = self::getTemplate ( "onChange" );
                    $change = preg_replace ( "/__FUNCTION__/", $change, $select );
                }
                
                $select = preg_replace ( "/__ONCHANGE__/", $change, $select );
                
                $select = preg_replace("/__ID__/",$name, $select);
                $select = preg_replace ( "/__NAME__/", $name, $select );
                $select = preg_replace ( "/__DISABLED__/", "", $select );
                
                
                $select = preg_replace ( "/__EXTRAPARAMS__/", $extraParams ? $extraParams : "", $select );
                
                $templateOptions = self::getTemplate ( "option" );
                
                $options = "";
                if ($space == true)
                {
                    
                    if($todos != false)
                    {
                        
                         $val =  self::getTemplate ( "todosOptionVal" );
                         $val = preg_replace("/__VAL__/", $todos, $val);
                         $options .= $val;
                        
                    }
                    else
                    {
                        $val = self::getTemplate ( "nullOption" );
                        $val = preg_replace("/__VAL__/", $space, $val);
                        $options .= $val;
                    }                    
                }
                
                foreach ( $sqlData as $field )
                {
                    
                    //$field = $this->getArrayObject ( $mainObj->conId, $field );
                    $aux = $templateOptions;
                   // $aux = preg_replace ( "/__CLASS__/", $class, $aux );
                    $aux = preg_replace ( "/__VALUE__/", $field [$key], $aux );
                    //$aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );

                    if($selected)
                    {
                        if(is_array($selected))
                        {
                            $aux = preg_replace ( "/__SELECTED__/",  in_array($field [$key], $selected)? " SELECTED" : "", $aux );
                            
                        }
                        else
                        {
                            $aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                            
                        }
                                                
                    }
                    else
                    {
                        $aux = preg_replace ( "/__SELECTED__/", "", $aux );
                        
                    }
                    
                    
                    //$aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                    if ($substring)
                    {
                        $aux = preg_replace ( "/__TEXT__/", substr ( rawurldecode ( $field [$fld] ), 0, $substring ), $aux );
                    }
                    else
                    {
                        $aux = preg_replace ( "/__TEXT__/", rawurldecode ( $field [$fld] ), $aux );
                    }
                    $options .= $aux . "\n";
                }
                $select = preg_replace ( "/__OPTIONS__/", $options, $select );
                
                return $select;
            }
            else
            {
                PwFunciones::setLogError( 112);
            }
        }
        
        //$this->setError ( 111, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
        PwFunciones::setLogError(111);
        
        return "";
    }
    
    
    
 /**
     * Genera el código de los elementos de un select con campos de una base de datos. 
     * Puede llamar a una función en js para hacerlo dinámico
     * 
     * @param Object	$mainObj		Objeto principal del sistema
     * @param String	$table			Tabla a la que se conecta
     * @param String 	$key			Llave para el select
     * @param String 	$fld			Campo que mostrara el texto del select
     * @param Boolean	$space			Si se quiere un espacio al inicio del combo
     * @param boolean 	$selected 		Condición para que aparezca preselecionado algun elemento, puede ser un valor o un array de valores
     * @param String 	$order 			Campo para hacer el ordenamiento del select
     * @param Array 	$condition 		Array con las condiciones para el query
     * @param String 	$subString 		Limita el tamaño del texto a mostrar en el select	
     *
     * @return Regresa el html necesario para el select
     */
    public function getSelectOptions($mainObj, $table,  $key, $fld, $space = false, $selected = false, $order = false, $condition = null,   $substring = false)
    {
        if ($table)
        {
            $fields = array ($key, $fld );

            $sqlData = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition, $order );
            
            if ($sqlData)
            {
                
                $templateOptions = $this->getTemplate ( "option" );
                
                $options = "";
                if ($space == true)
                {
                    $options .= $this->getTemplate ( "nullOption" );
                }
                
                foreach ( $sqlData as $field )
                {
                    
                    $field = $this->getArrayObject ( $mainObj->conId, $field );
                    $aux = $templateOptions;
                   // $aux = preg_replace ( "/__CLASS__/", $class, $aux );
                    $aux = preg_replace ( "/__VALUE__/", $field [$key], $aux );
                    //$aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );

                    if($selected)
                    {
                        if(is_array($selected))
                        {
                            $aux = preg_replace ( "/__SELECTED__/",  in_array($field [$key], $selected)? " SELECTED" : "", $aux );
                            
                        }
                        else
                        {
                            $aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                            
                        }
                                                
                    }
                    else
                    {
                        $aux = preg_replace ( "/__SELECTED__/", "", $aux );
                        
                    }
                    
                    
                    //$aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                    if ($substring)
                    {
                        $aux = preg_replace ( "/__TEXT__/", substr ( rawurldecode ( $field [$fld] ), 0, $substring ), $aux );
                    }
                    else
                    {
                        $aux = preg_replace ( "/__TEXT__/", rawurldecode ( $field [$fld] ), $aux );
                    }
                    $options .= $aux . "\n";
                }
                
                
                return $options;
            }
            else
            {
                $this->setError ( 112, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
            }
        }
        
        $this->setError ( 111, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
        
        return "";
    }
    
/**
     * Genera el código para crear un select con campos de una base de datos. 
     * Puede llamar a una función en js para hacerlo dinámico
     * 
     * @param Object	$mainObj		Objeto principal del sistema
     * @param String	$consulta		Consulta a ejecutar
     * @param Array		$params			Parámetros de la consulta
     * @param String 	$name			Nombre que va a recibir el campo
     * @param String 	$key			Llave para el select
     * @param String 	$fld			Campo que mostrara el texto del select
     * @param Boolean	$space			Si se quiere un espacio al inicio del combo
     * @param boolean 	$selected 		Condición para que aparezca preselecionado algun elemento
     * @param String 	$order 			Campo para hacer el ordenamiento del select
     //* @param Array 	$condition 		Array con las condiciones para el query
     * @param String 	$onChange 		Si existe, llama una funcion en js al cambiar
     * @param String 	$class 			La clase CSS para agregar al select
     * @param String 	$subString 		Limita el tamaño del texto a mostrar en el select	
     *
     * @return Regresa el html necesario para el select
     */
    public function getQuerySelect($mainObj, $consulta, $params, $name, $key, $fld, $space = false, $selected = false, $order = false, $onChange = false, $class = "", $substring = false, $extraParams = false, $todos = false)
    {
        
        if ($consulta)
        {
            
            $fields = array ($key, $fld );

            //$sqlData = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition );
            $ps = $mainObj->sql->setSimpleQuery( $mainObj->connection, $consulta );
            $sqlData = $mainObj->sql->executeSimpleQuery( $ps, $params, $consulta, null, false, true);
	  
            
            if ($sqlData)
            {
                
                $select = $this->getTemplate ( "select" );
                $select = preg_replace ( "/__CLASS__/", $class, $select );

                $change = "";
                if ($onChange)
                {
                    $change = $this->getTemplate ( "onChange" );
                    $change = preg_replace ( "/__FUNCTION__/", $change, $select );
                }
                
                $select = preg_replace ( "/__ONCHANGE__/", $change, $select );
                
                $select = preg_replace ( "/__NAME__/", $name, $select );
                //$select = preg_replace ( "/__EXTRAPARAMS__/", "", $select );
                $select = preg_replace ( "/__EXTRAPARAMS__/", $extraParams ? $extraParams : "", $select );
                
                $templateOptions = $this->getTemplate ( "option" );
                
                $options = "";
                if ($space == true)
                {
                    //$options .= $this->getTemplate ( "nullOption" );
                    
                    if($todos != false)
                    {
                        
                        $val =  $this->getTemplate ( "todosOptionVal" );
                        $val = preg_replace("/__VAL__/", $todos, $val);
                        $options .= $val;
                        
                    }
                    else
                    {
                        $options .= $this->getTemplate ( "nullOption" );
                    }
                    
                }
                
                foreach ( $sqlData as $field )
                {
                    
                    $field = $this->getArrayObject ( $mainObj->conId, $field );
                    $aux = $templateOptions;
                    $aux = preg_replace ( "/__CLASS__/", $class, $aux );
                    $aux = preg_replace ( "/__VALUE__/", $field [$key], $aux );
                    //$aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                   if($selected)
                    {
                        if(is_array($selected))
                        {
                            $aux = preg_replace ( "/__SELECTED__/",  in_array($field [$key], $selected)? " SELECTED" : "", $aux );
                            
                        }
                        else
                        {
                            $aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                            
                        }
                                                
                    }
                    else
                    {
                        $aux = preg_replace ( "/__SELECTED__/", "", $aux );
                        
                    }
                    if ($substring)
                    {
                        $aux = preg_replace ( "/__TEXT__/", substr ( rawurldecode ( $field [$fld] ), 0, $substring ), $aux );
                    }
                    else
                    {
                        $aux = preg_replace ( "/__TEXT__/", rawurldecode ( $field [$fld] ), $aux );
                    }
                    $options .= $aux . "\n";
                }
                $select = preg_replace ( "/__OPTIONS__/", $options, $select );
                
                return $select;
            }
            else
            {
                $this->setError ( 112, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
            }
        }
        
        $this->setError ( 111, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
        
        return "";
    }
    
    /**
     * Genera el código para crear un select con campos de una base de datos. 
     * Puede llamar a una función en js para hacerlo dinámico
     * 
     * @param Object	$mainObj		Objeto principal del sistema
     * @param String	$table			Tabla a la que se conecta
     * @param Array		$fields			Array con los campos a buscar 
     * @param Array 	$condition 		Array con las condiciones para el query
     * @param String 	$key			Llave para el select
     * @param String 	$fld			Campo que mostrara el texto del select

     * @return Regresa el html necesario para <options>
     */
    public function getOptionList($mainObj, $table, $fields, $condition, $key, $fld, $operation, $selected = false)
    {
        

        $sqlData = $mainObj->sql->executeQuery ( $mainObj->connection, $table, $fields, $condition, false, $operation );
        
        
        if ($sqlData)
        {
            
            $templateOptions = $this->getTemplate ( "option" );
            
            $options = "";

            
            foreach ( $sqlData as $field )
            {
                
                $field = $this->getArrayObject ( $mainObj->conId, $field );
                $aux = $templateOptions;
                $aux = preg_replace ( "/__CLASS__/", "", $aux );
                $aux = preg_replace ( "/__VALUE__/", $field [$key], $aux );
                $aux = preg_replace ( "/__TEXT__/", rawurldecode ( $field [$fld] ), $aux );
                $aux = preg_replace ( "/__SELECTED__/", $field [$key] == $selected ? " SELECTED" : "", $aux );
                $options .= $aux . "\n";
            }            
            return $options;
        }
        else
        {
            $this->setError ( 113, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
            return "false";
        }
        
        $this->setError ( 111, __LINE__ . "::" . __FUNCTION__, __CLASS__, 2 );
        return "false";
    
    }

    /**
    * Función para pintar un campo de texto 
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getText($params)
    {
      
        $data = self::getTemplate("textfield");      
        $data = preg_replace("/__ID__/", $params["id"], $data);
        $data = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $data);
        $data = preg_replace("/__PLACEHOLDER__/",  isset($params["placeholder"]) ?  $params["placeholder"] : "", $data);
        $data = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $data);
        $value = isset($params["value"]) ? $params["value"] : "";
        if(isset($params["encode"]) && $params["encode"] == true)
        {
            $value = htmlentities(rawurldecode($value));
        }
        $data = preg_replace("/__VALUE__/", $value, $data);


       // $data = preg_replace("/__VALUE__/", isset($params["value"]) ? $params["value"] : "", $data);

        return $data;
    }

    /**
    * Función para pintar un campo de texto 
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getPassword($params)
    {
      
        $data = self::getTemplate("passwordfield");      
        $data = preg_replace("/__ID__/", $params["id"], $data);
        $data = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $data);
        $data = preg_replace("/__PLACEHOLDER__/",  isset($params["placeholder"]) ?  $params["placeholder"] : "", $data);
        $data = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $data);
        $value = isset($params["value"]) ? $params["value"] : "";
        if(isset($params["encode"]) && $params["encode"] == true)
        {
            $value = htmlentities(rawurldecode($value));
        }
        $data = preg_replace("/__VALUE__/", $value, $data);


       

        return $data;
    }



    /**
    * Función para pintar un area de texto 
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getTextArea($params)
    {
      
        $data = self::getTemplate("textarea");      
        $data = preg_replace("/__ID__/", $params["id"], $data);
        $data = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $data);
        $data = preg_replace("/__PLACEHOLDER__/",  isset($params["placeholder"]) ?  $params["placeholder"] : "", $data);
        $data = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $data);
        $data = preg_replace("/__COLS__/", isset($params["cols"]) ? $params["cols"] : "", $data);
        $data = preg_replace("/__ROWS__/", isset($params["rows"]) ? $params["rows"] : "", $data);
        $data = preg_replace("/__MAXL__/", isset($params["maxlength"]) ? $params["maxlength"] : "", $data);
        $data = preg_replace("/__REQUIRED__/", isset($params["required"]) ? "required" : "", $data);


        $value = isset($params["value"]) ? $params["value"] : "";
        if(isset($params["encode"]) && $params["encode"] == true)
        {
            $value = htmlentities(rawurldecode($value));
        }
        $data = preg_replace("/__VALUE__/", $value, $data);

        return $data;
    }



    /**     
    * Función que genera un combo con los valores dados por un array
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getFormCheck($params)
    {
        //PwFunciones::getVardumpLog($params);
        
        $field = self::getTemplate ( "checkField" );
        
        $field = preg_replace("/__ID__/", $params["id"], $field);
        $field = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $field);
        $field = preg_replace("/__VALUE__/", isset($params["value"]) ? $params["defaultValue"] : $params["value"], $field);
        $field = preg_replace("/__CHECKED__/",  isset($params["value"]) && $params["value"] != 0 ?  "CHECKED" : "", $field);
        $field = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $field);
        
        
        return $field;
    }

    /**     
    * Función que genera un combo con los valores dados por un array
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getFormSelect($params)
    {
        
        $select = self::getTemplate ( "select" );


        //Si en los parametros enviamos que sea multiple
        
        if(isset($params["multiple"]) && $params["multiple"] == true)
        {

            $multipleVal = "multiple";
            $multipleSize = "";
            $select = self::getTemplate ( "selectMultiple" );            
            $multipleSize = isset($params["multipleSize"]) && $params["multipleSize"] > 0 ? $params["multipleSize"] : $multipleSize;


            $select = preg_replace("/__MULTIPLE__/", $multipleVal, $select);   
            $select = preg_replace("/__MSIZE__/", $multipleSize, $select);   

        }




        $select = preg_replace ( "/__CLASS__/", isset($params["CLASS"]) ? $params["CLASS"] : "form-control", $select );
        $select = preg_replace("/__ID__/", $params["id"], $select);
        $select = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $select);

        $templateOptions = self::getTemplate ( "option" );
        
        $options = "";
        if (isset($params["space"]))
        {
            $options = self::getTemplate ( "nullOption" );
            $options = preg_replace("/__VAL__/",  $params["space"], $options);
        }
        
        if(isset($params["todos"]) && !$params["space"])
        {
            $options .= self::getTemplate ( "todosOption" );
        }        

        //Si en los parámetros enviamos que se desactive

        $select = preg_replace("/__DISABLED__/", (isset($params["disabled"]) && $params["disabled"] == true) ?  "disabled" : "", $select);    
        
     
        
        
        if(!$params["arrValues"])
        {
            PwFunciones::setLogError(14);
            $select = preg_replace ( "/__OPTIONS__/", "", $select );
            $select = preg_replace ( "/__ONCHANGE__/", "", $select );            
            return $select;
        }
       
        //Trae el valor normal
        $value = isset($params["value"]) ? $params["value"] : null;


        //Checamos si es un multiple select
        $multipleFlag  = false;
        $arrValue =array();

        
        if(isset($params["multiple"]) && ($params["multiple"] == true) && $value != null)
        {            
            $arrValue = json_decode($value);    

            //Si el valor que trae no es un array, ponemos el array vacio            
            if(!is_array($arrValue))
            {
                $arrValue = array();
            }

            $multipleFlag = true;
        }
       
        //Trae los valores del select
        foreach ( $params["arrValues"] as $key => $dataValue )
        {

           
            $aux = $templateOptions;
            $aux = preg_replace ( "/__VALUE__/", $key, $aux );
            
            if($multipleFlag == true)
            {
                $aux = preg_replace ( "/__SELECTED__/",  in_array($key, $arrValue) ? " SELECTED" : "", $aux );
            }
            else
            {
                $aux = preg_replace ( "/__SELECTED__/", $key == $value ? " SELECTED" : "", $aux );
            }

            
            $aux = preg_replace ( "/__TEXT__/", $dataValue, $aux );
            $options .= $aux . "\n";
        }
        
        $select = preg_replace ( "/__OPTIONS__/", $options, $select );
        
        $changeSubmit = "";
        if (isset($params["onChangeFunction"]))
        {
            $changeSubmit = self::getTemplate ( "onChange" );
            $changeSubmit = preg_replace ( "/__FUNCTION__/", $params["onChangeFunction"], $changeSubmit );
        }
        
        if (isset($params["onChangeSubmit"]))
        {
            $changeSubmit = self::getTemplate ( "changeSubmit" );
            $changeSubmit = preg_replace ( "/__FORMNAME__/", $params["formName"], $changeSubmit );
        }
        
        $select = preg_replace ( "/__ONCHANGE__/", $changeSubmit, $select );
        
        
        return $select;
    }


    /**
    * Funcioón para pintar un campo de texto 
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getHidden($params)
    {
      
        $data = self::getTemplate("formHidden");      
        $data = preg_replace("/__ID__/", $params["id"], $data);
        $data = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $data);
        $data = preg_replace("/__VALUE__/", isset($params["value"]) ? $params["value"] : "", $data);

        return $data;
    }

     /**     
    * Función que genera un combo con los valores dados por un array
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getFormToggle($params)
    {
        //PwFunciones::getVardumpLog($params);
        
        $field = self::getTemplate ( "toggle" );
        
        $field = preg_replace("/__ID__/", $params["id"], $field);
        $field = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $field);
        $field = preg_replace("/__VALUE__/", isset($params["value"]) ? $params["defaultValue"] : $params["value"], $field);
        $field = preg_replace("/__CHECKED__/",  isset($params["value"]) && $params["value"] != 0 ?  "CHECKED" : "", $field);
        $field = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $field);
        
        
        return $field;
    }

     /**
    * Función para pintar un campo de texto 
    * @param String    $params     Parametros para las propiedades de la forma     
    */
    public static function getFormDatepicker($params, $dateDefaultFormat)
    {
      
        //PwFunciones::getVardumpLog($params);
        $data = self::getTemplate("datepicker");      
        $data = preg_replace("/__ID__/", $params["id"], $data);
        $data = preg_replace("/__NAME__/", isset($params["name"]) ?  $params["name"]:  $params["id"], $data);
        $data = preg_replace("/__PLACEHOLDER__/",  isset($params["placeholder"]) ?  $params["placeholder"] : "", $data);
        $data = preg_replace("/__DISABLED__/",  (isset($params["disabled"])  && $params["disabled"]  == true) ? "disabled" : "" , $data);
       
       

        $value = "";
        if(isset($params["value"]) && $params["value"]  != "")
        {
            $value = str_replace("/", "-", $params["value"]);
       
            $value = date_create($value);


            if(isset($params["format"]) && $params["format"] != null)
            {
                $value =  date_format($value,  $params["format"]);                      
            }
            //Usamos el formato de default que se define al inicio de la clase
            else
            {
                //PwFunciones::getVardumpLog($value);
                $value =  date_format($value,  $dateDefaultFormat);                       
            } 
        }
        $data = preg_replace("/__VALUE__/", $value, $data);

        return $data;
    }



    
    private static function getTemplate($name)
    {
        $template ["select"] = <<< TEMP
<select name= "__NAME__" id= "__ID__" __ONCHANGE__ class = "__CLASS__" __DISABLED__>__OPTIONS__</select>
TEMP;

    $template ["selectMultiple"] = <<< TEMP
<select name= "__NAME__[]" id= "__ID__[]" __ONCHANGE__ class = "__CLASS__" __DISABLED__ __MULTIPLE__ size = "__MSIZE__">__OPTIONS__</select>
TEMP;

        
        $template ["option"] = <<< TEMP
<option value = "__VALUE__"  __SELECTED__ >__TEXT__</option>
TEMP;
        
        $template ["nullOption"] = <<< TEMP
<option value = '__VAL__'></option>
TEMP;

        $template ["todosOption"] = <<< TEMP
<option value = ''>Todos</option> 
TEMP;
        
        
        $template ["todosOptionVal"] = <<< TEMP
<option value = ''>__VAL__</option>
TEMP;
        
        
        $template ["onChange"] = <<< TEMP
onChange="__FUNCTION__(0)"
TEMP;
        
        $template ["changeSubmit"] = <<< TEMP
onChange="document.__FORMNAME__.submit()"
TEMP;
        
        $template["textfield"] = <<< TEMP
        <input type="text" class="form-control" id="__ID__" name= "__NAME__" placeholder="__PLACEHOLDER__" __DISABLED__ value = "__VALUE__">         
TEMP;


$template["passwordfield"] = <<< TEMP
        <input type="password" class="form-control" id="__ID__" name= "__NAME__" placeholder="__PLACEHOLDER__" __DISABLED__ value = "__VALUE__">         
TEMP;

        

    $template["requiredField"] = <<< TEMP
    <small class="form-control-feedback" id = "__NAME__Error"></small> 
TEMP;

    $template["labelField"] = <<< TEMP
<label for="__ID__">__LABEL__</label>
TEMP;
       

    $template["formElement"] = <<< TEMP
    <div class="form-group col-xs-__XS__ col-sm-__SM__ col-md-__MD__ col-lg-__LG__">
        __LABEL__
        __FIELD__
        __REQUIRED__
    </div>
TEMP;

$template["textarea"] = <<< TEMP
<textarea class = "form-control rounded-0 form-control-md" name = "__NAME__" id = "__ID__" cols= "__COLS__" rows = "__ROWS__"  maxlength = "__MAXL__" placeholder="__PLACEHOLDER__" __REQUIRED__ __DISABLED__ style = "font-size:11px;">
__VALUE__
</textarea>
    
TEMP;



$template["checkField"] = <<< TEMP
    <input  type="checkbox" name="__NAME__" id = "__ID__" value="__VALUE__" __CHECKED__ __DISABLED__>
TEMP;


$template["formHidden"] = <<< TEMP
    <input type="hidden" name="__NAME__" id = "__ID__" value="__VALUE__">
TEMP;

$template["datepicker"] = <<< TEMP
  <input type="text" class="form-control" id="__ID__" name= "__NAME__" placeholder="__PLACEHOLDER__" __DISABLED__ value = "__VALUE__">         

TEMP;


    $template["toggle"] = <<< TEMP
   <label class=" u-check g-mr-20 mx-0 mb-0">
                    <input class="g-hidden-xs-up g-pos-abs g-top-0 g-right-0" name="radGroup1_1" type="checkbox">
                    <div class="u-check-icon-radio-v7">
                      <i class="fa" data-check-icon="&#xf00c" data-uncheck-icon="&#xf00d"></i>
                    </div>
                  </label>

TEMP;




    
        

     return $template[$name];
        
    }   

}
?>