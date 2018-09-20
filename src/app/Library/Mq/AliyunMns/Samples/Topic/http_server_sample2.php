<?php

function log2( $logthis )
{
    file_put_contents('logfile.log', date("Y-m-d H:i:s"). " " . $logthis.PHP_EOL, FILE_APPEND | LOCK_EX);
}

if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
       $headers = array();
       foreach ($_SERVER as $name => $value)
       {
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
}

$headers = getallheaders();
log2(json_encode($headers));
$content = file_get_contents("php://input");
log2(json_encode($content));

header("HTTP/1.1 204 No Content");
echo 'ok';
?>
