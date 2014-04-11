<?php

require_once 'Log/mdb2.php';

class Log_xdmdb2 extends Log_mdb2
{

    function log($message, $priority = null)
    {
        if (is_array($message))
        {
            return parent::log(json_encode($message), $priority);
        }
        else
        {
            return parent::log(json_encode(array('message' => $message)), $priority);
        }
    }
}

