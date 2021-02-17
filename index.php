<?php

require_once __DIR__ . "/vendor/autoload.php";

use Klakier\AllegroAPI\Api;
use Klakier\AllegroAPI\Credentials;
use Klakier\AllegroAPI\Utils\Util;
use Klakier\Router\Route;
use Klakier\Database\DbOperation;

$useSandbox = false;
$isRunningLocal = file_exists("isRunningLocal");

$request = $_SERVER['REQUEST_URI'];
// trim subfolder on localhost
if ($isRunningLocal)
    $request = preg_replace('/^\/allegro_app/', '', $request);

$credentials = new Credentials($useSandbox);
$api = new Api($credentials->client_id, $credentials->client_secret, $useSandbox, $isRunningLocal);

$db = DbOperation::getInstance();
echo "Request: " . $request . "</br>";

Route::add('/cctoken', fn () => Util::acquireApplicationToken($api));

Route::add('/login', fn () => $api->getUserAuthorizationCode());

Route::add('/oauth2callback', function () use ($api) {
    if (isset($_GET['code']))
        Util::acquireUserToken($api, $_GET['code']);
    else
        throw new Exception("Authorization code missing !");
});

//Routes for testting
Route::add('/me', function () use ($api) {
    if (isset($_COOKIE['allegro_user_token']))
        var_dump($api->callGetMethod($_COOKIE['allegro_user_token'], "/me"));
    else
        echo "TOKEN NOT SET";
});

//Test route to forward GET call directly to Allegro API
Route::add('/get/(.*)', function ($var) use ($api) {

    if (isset($_COOKIE['allegro_user_token'])) {

        //bouild query
        $url = "/$var";
        if (count($_GET) > 0) {
            $url .= "?" . http_build_query($_GET);
            $url = str_replace("_", ".", $url);
        }

        $res = $api->callGetMethod($_COOKIE['allegro_user_token'], $url);
        echo $url;
        echo "<pre>" . print_r($res, true) . "</pre>";
    } else
        echo "TOKEN NOT SET";
});

//TODO sprawdzić czemu nie działa, zrobić parsowanie błedów w klasie Api
Route::add('/testOffer', function () use ($api) {
    $body = '{"product":{"id":"711719927204"},"sellingMode":{"price":{"amount":"2250.85","currency":"PLN"}},"stock":{"available":10}}';
    if (isset($_COOKIE['allegro_user_token'])) {
        $res = $api->callPostMethod($_COOKIE['allegro_user_token'], "/sale/product-offers", $body);
        echo "<pre>" . print_r($res, true) . "</pre>";
    } else
        echo "TOKEN NOT SET";
});

Route::pathNotFound(function ($request) {
    echo "Not found $request";
});
Route::methodNotAllowed(function ($request, $method) {
    echo "Method $method not allowed on $request";
});

Route::run($isRunningLocal ? '/allegro_app' : '/');
