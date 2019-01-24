<?php
namespace Middleware;
use Slim\Http\Request;
use Slim\Http\Response;

class TokenAuth {
  private $container;

  private $userAccessLevel;
  private $adminAccessLevel;

  public function __construct( $container ) {
    $this->container = $container;
    // Higher number = more access
    $this->readAccessLevel = 1;
    $this->writeAccessLevel = 4;
    $this->adminAccessLevel = 8;
    $this->superAdminAccessLevel = 10;
  }
  function ACL( $path, $method ){
    //init access list
    $accessList = array(

      array(
        'access' => $this->adminAccessLevel,
        'path' => "/users/",
        'method' => ['GET']
      ),

      array(
        'access' => $this->adminAccessLevel,
        'path' => "/clients/[{page}]",
        'method' => ['GET']
      ),
    );

    //search access list
    foreach ( $accessList as $value ) {
      foreach ( $value['method'] as $valueMethod ) {
        if( $value['path'] == $path && $valueMethod == $method ) {
         return $value;
        }
      }
    }
  }

  public function denyAccess(){
    http_response_code( 401 );
    exit;
  }

  public function checkUserAccessLevel( $accessRule, $_userAccessLevel ) {
    if( $_userAccessLevel == 'user' )
      $_userAccessLevel = $this->userAccessLevel;
    else if( $_userAccessLevel == 'admin' )
      $_userAccessLevel = $this->adminAccessLevel;

    //check the access level
    if( $_userAccessLevel >= $accessRule )
      return true;
  }

  public function __invoke( Request $request, $response, $next ) {
    $token = null;

    if(isset( $request->getHeader( 'X-MarcoPromo-Token' )[0] ) )
      $token = $request->getHeader( 'X-MarcoPromo-Token' )[0];

    //same format as api route
    $route = $request->getAttribute( 'route' );

    if ( !empty( $route ) ){
      $path = $route->getPattern();
      $method = $request->getMethod();
      $accessRule = $this->ACL( $path, $method );

      if(isset( $accessRule ) && $token != null ) {
        $checkToken = $this->container->users->validate_token( $token );
        if( $checkToken != null )  {
          if( $this->checkUserAccessLevel( $accessRule['access'], $checkToken['accessLevel'] ) ) {
            // $this->container->users->updateUserToken( $token );
          } else
            $this->denyAccess();
        } else {
          $this->denyAccess();
        }
      } else if( isset( $accessRule ) && $token == null )
        $this->denyAccess();

      $response = $next( $request, $response );
      return $response;
    } else {
     return $next( $request, $response );
    }
  }
}