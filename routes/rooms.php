<?php

/**
 * Get a list of rooms for an area
 */
$app->get('/rooms', function($request, $response, $args ) {
  $token = $request->getAttribute("token");
  $uri = $request->getUri();
  parse_str( $uri->getQuery(), $query_vars );
  return $response->withJson( $this->rooms->get_rooms( $query_vars, $token ) );
});

/**
 * Get a specific room
 */
$app->get('/rooms/{area_id}', function( $request, $response, $args ) {
  $token = $request->getAttribute("token");
  return $response->withJson( $this->rooms->get_room( $args['area_id'], $token ) );
});

/**
 * Create a new room
 */
$app->post('/rooms', function($request, $response, $args ) {
  $body = $request->getParsedBody();

  $token = $request->getAttribute("token");

  return $response->withJson( $this->rooms->create_new_room( $body, $token ) );
});

/**
 * Update an existing room
 */
$app->put('/rooms/{area_id}', function( $request, $response, $args ) {
  $area_id = $args['area_id'];
  $data = $request->getParsedBody();
  $api_response = $this->rooms->update_area( $area_id, $data );
  $status_code = ( $api_response['success'] ) ? 200 : 400;

  return $response->withStatus( $status_code )->withJson( $api_response );
});