<?php

namespace Klakier\Database;

use PDO;
use Exception;
use Klakier\Database\DbConnect;

class DbOperation
{

    private $con;
    private static $instance;

    function __construct()
    {
        $db = new DbConnect();
        if ($db == null)
            throw new Exception("Can't connect with database, PDO is null.");
        $this->con = $db->connect();
        if ($this->con == null)
            throw new Exception("Can't connect with database, connection is null");
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            $className = __CLASS__;
            self::$instance = new $className;
        }

        return self::$instance;
    }

    public function insertRequestToDb($request)
    {
        if ($this->con == null)
            return -1;

        $query = $this->con->prepare(
            "INSERT INTO public.test(request) VALUES (:request);"
        );

        $query->bindValue(':request', $request, PDO::PARAM_STR);

        if ($query->execute()) {
            return 1;
        } else {
            return 0;
        }
    }
}
