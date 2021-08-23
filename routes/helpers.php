<?php
require 'JwtHandler.php'; 
function decodeToken($token)
{
    
    $jwt = new JwtHandler();

    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    return $x;
}
function istutor($token)
{
    $jwt = new JwtHandler();

    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    if ($x["role"]=="tutor") return true;
    else return false;
}
function isstudent($token)
{
    $jwt = new JwtHandler();

    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    if ($x["role"]=="student") return true;
    else return false;
}
?>