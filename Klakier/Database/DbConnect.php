<?php

namespace Klakier\Database;

use PDO;
use Exception;
use PDOException;

class DbConnect
{

    private $con;

    function connect()
    {

        try {
            $credentials = $this->getDbCredentials();
            extract($credentials);
            $path = ltrim($path, "/");

            $this->con = new PDO(
                sprintf("pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s", $host, $port, $path, $user, $pass),
                $user,
                $pass,
                [
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
        } catch (PDOException $e) {
            echo "Failed to connect " . $e->getCode() . " " . $e->getMessage();
            return null;
        } catch (Exception $e) {
            echo "Failed to connect " . $e->getCode() . " " . $e->getMessage();
            return null;
        }

        return $this->con;
    }

    function getDbCredentials()
    {

        $database_url = getenv("DATABASE_URL");
        //$database_url = getenv("DATABASE_URL_LOCAL");
        return parse_url($database_url);
    }
}
