<?php

namespace Klakier\AllegroAPI\Utils;

use Throwable;
use Klakier\AllegroAPI\Api;

class Util
{
    static function acquireApplicationToken(Api &$api): string
    {
        $token = "";
        try {
            echo "Client Credentials Flow: ";
            if (isset($_COOKIE["allegro_application_token"])) {
                echo "Token OK";
                $token = $_COOKIE["allegro_application_token"];
            } else {
                echo "Token not set, getting...";
                $token = $api->getApplicationToken();
                echo "Token OK";
            }

            self::printToken($api, $token);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            echo $msg;
        }
        return $token;
    }

    static function acquireUserToken(Api &$api, string $authotizationCode): string
    {
        $token = "";
        try {
            echo "Authorization Code Flow: ";

            echo "Getting Token... ";
            $token = $api->getUserToken($authotizationCode);
            echo "Token OK";

            self::printToken($api, $token);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            echo $msg;
        }
        return $token;
    }

    static private function printToken($api, $token)
    {
        echo "<br><br>$token";
        $decodedToken = $api->decodeJWT($token);
        echo "<br><br><pre>";
        echo json_encode($decodedToken->header, JSON_PRETTY_PRINT);
        echo "</pre><pre>";
        echo json_encode($decodedToken->payload, JSON_PRETTY_PRINT);
        echo "</pre><br><br>";
    }
}
