<?php

$app->get( '/contests', function( $request, $response, $args ) {
  $uri = $request->getUri();
  parse_str( $uri->getQuery(), $query_vars );
  return $response->withJson( $this->contests->get_contests( $query_vars ) );
});

$app->post(/**
 * @param Request $request
 * @param Response $response
 * @param array $args
 * @return mixed
 */
  '/contests', function ($request, $response, $args ) {

  $body = $request->getParsedBody();

  return $response->withJson( $this->contests->create_new_contest( $body ) );

});


$app->delete('/contests/{contest_id}', function ( $request, $response, $args ) {

  $contest_id = $args['contest_id'];

  return $response->withJson( $this->contests->delete_contest( $contest_id) );

});