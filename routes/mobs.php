<?php


$app->get(/**
 * @param Request $request
 * @param Response $response
 * @param array $args
 * @return mixed
 */
  '/markets/{market_id}/stations', function (Request $request, Response $response, array $args ) {

  //return $response->withJson( $this->market->get_all_markets() );

});

$app->get(/**
 * @param Request $request
 * @param Response $response
 * @param array $args
 * @return mixed
 */
  '/markets/{market_id}', function ($request, $response, array $args ) {

  return $response->withJson( $this->market->get_market( $args['market_id'] ) );

});

$app->get(/**
 * @param Request $request
 * @param Response $response
 * @param array $args
 * @return mixed
 */
  '/markets', function ($request, $response, $args ) {

  return $response->withJson( $this->market->get_all_markets() );

});


$app->delete(/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
  '/markets/{market_id}', function($request, $response, $args ) {
  return $response->withJson( $this->market->deleteMarket( $args ) );
});

$app->post(/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
  '/markets', function($request, $response, $args ) {

  $body = $request->getParsedBody();

  return $response->withJson( $this->market->createMarket( $body ) );
});

$app->put(/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
  '/markets/{market_id}', function($request, $response, $args ) {

  $market_id = $args['market_id'];

  $body = $request->getParsedBody();

  return $response->withJson( $this->market->updateMarket( $market_id, $body ) );
});
