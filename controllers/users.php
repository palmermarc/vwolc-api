<?php

namespace App\Controller;

use \Firebase\JWT\JWT;

/**
 * Class stationController
 * @package App\Controller
 */
class usersController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  public function verify_user_login( $args ) {
    $mappings = [
      ":username" => $args['username'],
    ];

    $result = $this->olc->db->select("
        *
        FROM `users`
        WHERE `username` = :username
        LIMIT 1
      ",
      $mappings
    );

    if ( $result === false ) {
      return [
        "success" => false,
        "message" => "The user cannot be authenticated.",
      ];
    } else if ( empty( $result ) ) {
      return [
        "success" => false,
        "message" => "The user cannot be authenticated.",
      ];
    }

    $user = reset( $result );

    if( !password_verify( $args['password'], $user->password ) ) {
      return [
        "success" => false,
        "message" => "The user cannot be authenticated."
      ];
    }

    unset( $user->password );

    $token_key = 'supersecretkeyyoushouldnotcommittogithub';
    $token_args  = array(
      "iss" => "http://olc.api",
      "iat" => time()-60*60*6,
      "exp" => time() + 60*60*12,
      "user" => $user
    );

    $token = JWT::encode( $token_args, $token_key );
    $user->token = $token;
    return (array) $user;

  }

  public function create_new_user( $args ) {

    if( empty( $args['password'] ) ) {
      return [
        'success' => false,
        'message' => 'A password is required.'
      ];
    }

    if( empty( $args['first_name'] ) || empty( $args['last_name'] ) ) {
      return [
        'success' => false,
        'message' => 'A name is required.'
      ];
    }

    if( empty( $args['email'] ) ) {
      return [
        'success' => false,
        'message' => 'An email is required.'
      ];
    }

    $first_name = $args['first_name'];
    $last_name = $args['last_name'];
    $password = password_hash( $args['password'], PASSWORD_BCRYPT, [ 'cost' => 12 ]);
    $email = $args['email'];
    $username = $args['username'];

    if( empty( $args['username'] ) ) {
      $username = $email;
    }

    $this->olc->db->insert(
      'users',
      [
        'username' => $username,
        'password' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone
      ]
    );

    return array(
      "success" => true,
      "message" => "The user has been created.",
    );
  }

  private function create_user_token( $user ) {
    $token_key = 'supersecretkeyyoushouldnotcommittogithub';
    $token_args  = array(
      "iss" => "http://olc.api",
      "iat" => time()-60*60*6,
      "exp" => time() + 60*60*12,
      "user" => $user
    );

    $token = JWT::encode( $token_args, $token_key );
  }

  function validate_user_token( $decoded_token ) {
    $return_arr = $decoded_token->user;
    $return_arr->status = 'success';
    return $return_arr;
  }

}
