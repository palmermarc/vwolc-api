<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

// Require the JSON Web Tokens library
require __DIR__ . '/../vendor/firebase/php-jwt/src/JWT.php';
require __DIR__ . '/../vendor/tuupola/slim-jwt-auth/src/JwtAuthentication.php';

$app->add(new Tuupola\Middleware\JwtAuthentication([
  "path" => ["/"],
  "ignore" => ["/users/authenticate"],
  "secret" => "supersecretkeyyoushouldnotcommittogithub",
  "secure" => false,
  "error" => function ($response, $arguments) {
    $data["status"] = "error";
    $data["message"] = $arguments["message"];
    return $response
      ->withHeader("Content-Type", "application/json")
      ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }
]));

$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-OLC-Token')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
