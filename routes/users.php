<?php

/**
 * Post Functions for auth and creating new users
 */
$app->post('/users/authenticate', function( $request, $response, $args ) {

  $body = $request->getParsedBody();
  $api_response = $this->users->verify_user_login( $body );
  $response_code = ( $api_response['token'] ) ? 200 : 401;
  return $response->withStatus( $response_code )->withJson( $api_response );
});

$app->post('/users', function($request, $response, $args ) {

  $body = $request->getParsedBody();

  return $response->withJson( $this->users->create_new_user( $body ) );
});

/**
 * Update a user
 */
$app->put('/users/{user_id}', function( $request, $response, $args ) {
  $user_id = $args['user_id'];
  $data = $request->getParsedBody();
  $api_response = $this->users->update_user( $user_id, $data );
  $status_code = ( $api_response['success'] ) ? 200 : 400;

  return $response->withStatus( $status_code )->withJson( $api_response );
});

/**
 * Validate the user token
 */
$app->get('/users/validate', function($request, $response, $args ) {
  $token = $request->getAttribute("token");

  if( empty( $token['user'] ) ) {
    return $response->withStatus( 400 )->withJson( [ 'status' => false, 'message' => 'Invalid token' ] );
  }
  $token['user']->status = "success";

  return $response->withStatus( 200 )->withJson( $token['user'] );
});

/**
 * Validate a list of users
 */
$app->get('/users', function($request, $response, $args ) {

  $uri = $request->getUri();
  parse_str( $uri->getQuery(), $query_vars );
  return $response->withJson( $this->users->get_users( $query_vars ) );
});

/**
 * Get a specific user
 */
$app->get('/users/{user_id}', function( $request, $response, $args ) {

  return $response->withJson( $this->users->get_user( $args['user_id'] ) );
});
