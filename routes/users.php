<?php

$app->post('/users/authenticate', function($request, $response, $args ) {

  $body = $request->getParsedBody();
  $api_response = $this->users->verify_user_login( $body );
  $response_code = ( $api_response['token'] ) ? 200 : 401;
  return $response->withJson( $api_response )->withStatus( $response_code );
});

$app->post('/users', function($request, $response, $args ) {

  $body = $request->getParsedBody();

  return $response->withJson( $this->users->create_new_user( $body ) );
});

$app->get('/users/validate', function($request, $response, $args ) {

  $decoded = $request->getAttribute("jwt");
  return $response->withJson( $this->users->validate_user_token( $decoded ) );
});