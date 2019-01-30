<?php

/**
 * Validate a list of areas
 */
$app->get('/areas', function($request, $response, $args ) {
  $uri = $request->getUri();
  parse_str( $uri->getQuery(), $query_vars );
  return $response->withJson( $this->areas->get_areas( $query_vars ) );
});

/**
 * Update an area
 */
$app->put('/areas/{area_id}', function( $request, $response, $args ) {
  $area_id = $args['area_id'];
  $data = $request->getParsedBody();
  $api_response = $this->areas->update_area( $area_id, $data );
  $status_code = ( $api_response['success'] ) ? 200 : 400;

  return $response->withStatus( $status_code )->withJson( $api_response );
});

/**
 * Get a specific area
 */
$app->get('/areas/{area_id}', function( $request, $response, $args ) {

  return $response->withJson( $this->areas->get_area( $args['area_id'] ) );
});
