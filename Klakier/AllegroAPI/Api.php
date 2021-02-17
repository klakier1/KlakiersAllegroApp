<?php

namespace Klakier\AllegroAPI;

use stdClass;
use Exception;
use Klakier\Database\DbOperation;

class Api
{
    protected $clientId;
    protected $clientSecret;

    private $baseUrlApi;
    private $baseUrlAuth;
    private $authRedirectUrl;

    function __construct(string $clientId, string $clientSecret, bool $useSandBox = false, bool $isRunningLocal = false)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrlApi = $useSandBox ? "https://api.allegro.pl.allegrosandbox.pl" : "https://api.allegro.pl";
        $this->baseUrlAuth = $useSandBox ? "https://allegro.pl.allegrosandbox.pl/auth/oauth" : "https://allegro.pl/auth/oauth";
        $this->authRedirectUrl = $isRunningLocal ? "http://localhost/allegro_app/oauth2callback" : "https://allegro-helper-app.herokuapp.com/oauth2callback";
    }

    private function getToken(array &$query, string $cookie)
    {
        $authUrl = $this->baseUrlAuth . "/token" . "?" . http_build_query($query);

        if (!$ch = curl_init($authUrl))
            throw new Exception("GET TOKEN: cant create request");;

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERNAME, $this->clientId);
        curl_setopt($ch, CURLOPT_PASSWORD, $this->clientSecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $resultJSON = curl_exec($ch);
        $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resultJSON == false) {
            throw new Exception("GET TOKEN: empty respone CODE: $resultCode");
        }

        $tokenObject = json_decode($resultJSON);

        if ($resultCode == 200) {
            $decodedToken = $this->decodeJWT($tokenObject->access_token);
            $expire = $decodedToken->payload->exp;
            setcookie($cookie, $tokenObject->access_token, $expire, "/", "", false, true);
            echo "<br>Token ważny do: " . date("M d Y H:i:s", $expire) . "<br>";
            return $tokenObject->access_token;
        } else if ($resultCode >= 400) {
            throw new Exception("GET TOKEN: $resultCode $tokenObject->error");
        } else {
            throw new Exception("GET TOKEN: $resultCode");
        }
    }

    function getApplicationToken(): string
    {
        $query = [
            "grant_type" => "client_credentials"
        ];

        return $this->getToken($query, "allegro_application_token");
    }

    function getUserToken($authorizationCode): string
    {
        $query = [
            "grant_type" => "authorization_code",
            "code" => $authorizationCode,
            "redirect_uri" => $this->authRedirectUrl
        ];

        return $this->getToken($query, "allegro_user_token");
    }

    function getUserAuthorizationCode(): string
    {
        $query = [
            "response_type" => "code",
            "client_id"    => $this->clientId,
            "redirect_uri" => $this->authRedirectUrl,
            "prompt" => "confirm"
            //"state" => "test"
        ];

        $authUrl = $this->baseUrlAuth . "/authorize" . "?" . http_build_query($query);

        header("Location: $authUrl");
        exit;
    }

    function callGetMethod(string $token, string $path): stdClass
    {
        $getUrl = $this->baseUrlApi . $path;

        $ch = curl_init($getUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/vnd.allegro.public.v1+json"
        ]);

        $resultJSON = curl_exec($ch);
        $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resultJSON == false) {
            throw new Exception("GET CALL: empty respone CODE: $resultCode");
        }

        $resultObject = json_decode($resultJSON);

        if ($resultCode >= 200 && $resultCode < 300) {
            return $resultObject;
        } else if ($resultCode >= 300 && $resultCode < 400) {
            $errorDescription = $this->getError($resultObject);
            throw new Exception("ERROR GET CALL: $path $resultCode $errorDescription");
        } else if ($resultCode >= 400) {
            $errorDescription = $this->getError($resultObject);
            throw new Exception("ERROR GET CALL: $path $resultCode $errorDescription");
        } else {
            throw new Exception("ERROR GET CALL: $path $resultCode");
        }
    }

    //TODO sprawdzić postmanem
    function callPostMethod(string $token, string $path, string $body): stdClass
    {
        $getUrl = $this->baseUrlApi . $path;

        $ch = curl_init($getUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/vnd.allegro.beta.v2+json",
            "Content-Type: application/vnd.allegro.beta.v2+json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $resultJSON = curl_exec($ch);
        $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resultJSON == false) {
            throw new Exception("GET CALL: empty respone CODE: $resultCode");
        }

        $resultObject = json_decode($resultJSON);

        if ($resultCode >= 200 && $resultCode < 300) {
            return $resultObject;
        } else if ($resultCode >= 300 && $resultCode < 400) {
            $errorDescription = $this->getError($resultObject);
            throw new Exception("ERROR GET CALL: $path $resultCode $errorDescription");
        } else if ($resultCode >= 400) {
            $errorDescription = $this->getError($resultObject);
            throw new Exception("ERROR GET CALL: $path $resultCode $errorDescription");
        } else {
            throw new Exception("ERROR GET CALL: $path $resultCode");
        }
    }

    //TODO zroboic zeby wyswietlał wszystkie błedy
    function getError(stdClass $result): string
    {
        if (isset($result)) {
            if (isset($result->error))
                return $result->error;
            else if (isset($result->errors[0])) {
                return $result->errors[0]->message;
            }
        }
        return "";
    }

    function decodeJWT(string $token): stdClass
    {
        $arr = explode(".", $token);
        $ret = array(
            "header" => json_decode(base64_decode($arr[0])),
            "payload" => json_decode(base64_decode($arr[1]))
        );
        $ret = (object)$ret;
        return $ret;
    }

    function printAllCategories(string $token, string &$res, string $parentId = "", int $howDeep = 1)
    {
        $howDeep--;
        if ($howDeep === -1) return "";

        if ($parentId) {
            $query = ["parent.id" => $parentId];
            $getChildrenUrl = "/sale/categories" . "?" . http_build_query($query);
        } else {
            $getChildrenUrl = "/sale/categories";
        }

        $categoriesList = $this->callGetMethod($token, $getChildrenUrl);

        $res .= "<ul>";
        foreach ($categoriesList->categories as $key => $value) {
            $res .= "<li>";

            $res .= "$value->id  $value->name";
            if (!$value->leaf) {
                $this->printAllCategories($token, $res, "$value->id", $howDeep);
            }
            $res .= "</li>";
        }
        $res .= "</ul>";
    }
}
