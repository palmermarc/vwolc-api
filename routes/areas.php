<?php

$app->get( '/copies', function ($request, $response, $args ) {

  $uri = $request->getUri();
  parse_str( $uri->getQuery(), $query_vars );
  return $response->withJson( $this->copies->get_copies( $query_vars ) );

});

$app->get( '/copies/{copy_id}', function ($request, $response, $args ) {

  return $response->withJson( $this->copies->get_copy( $args['copy_id'] ) );

});

$app->post( '/copies', function($request, $response, $args) {
  $body = $request->getParsedBody();
  $api_response = $this->copies->create_copy( $body );
  $status_code = ( $api_response['success'] ) ? 200 : 400;
  return $response->withStatus($status_code)->withJson( $api_response );
});

$app->delete( '/copies/{copy_id}', function($request, $response, $args ) {
  return $response->withJson( $this->copies->delete_copy( $args['copy_id'] ) );
});

$app->put( '/copies/{copy_id}', function($request, $response, $args) {
  $copy_id = $args['copy_id'];
  $data = $request->getParsedBody();
  $api_response = $this->copies->update_copy( $copy_id, $data );
  $status_code = ( $api_response['success'] ) ? 200 : 400;

  return $response->withStatus( $status_code )->withJson( $api_response );
});