<?php

class ftp
{
  public $conn;

  function __construct($url)
  {
    $this->conn = ftp_connect($url);
  }

  public function __call($func,$a)
  {
    if(strstr($func,'ftp_') !== false && function_exists($func))
    {
      array_unshift($a,$this->conn);
      return call_user_func_array($func,$a);
    }
    else
    {
            die("$func is not a valid FTP function");
    }
  }
}



?>