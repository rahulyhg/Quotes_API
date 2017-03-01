<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */




class  Responce{
    /* Member variables */
    var $error = false;
    var $message = "";
    var $data = array();
    var $responceArray = array();

    /* Member functions */
    function setError($par){
       $this->error = $par;
    }
    function setMessage($par){
       $this->message = $par;
    }
    function setData($key,$par){
       $this->data[$key] = $par;
    }
    function setArray()
    {
        $this->responceArray["error"] = $this->error;
        $this->responceArray["message"] = $this->message;
        $this->responceArray["data"] = $this->data;
        return $this->responceArray;

    }
      
   
}


?>

