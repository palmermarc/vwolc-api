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

  public function get_users($args) {
    $valid_arg_keys = [
      'username',
      'email'
    ];

    $validated_args = [];
    $query_vals = [];

    foreach( $args as $key => $val ) {
      if( !in_array( $key, $valid_arg_keys ) ) {
        continue;
      }

      $validated_args[$key] = $val;
    }

    $page = isset( $args['page'] ) ? $args['page'] : 1;

    $limit = 100;
    $offset = $limit * ($page-1);

    $query = "SELECT * FROM users u";

    $i = 0;

    foreach( $validated_args as $key => $val ) {

      if( $i == 0 ) {
        $query .= " WHERE ";
      } else {
        $query .= " AND ";
      }

      $query .= $key . " = :" . $key;

      $query_vals[':'.$key] = $val;

      $i++;
    }

    $query .= " LIMIT " . $limit . " OFFSET " . $offset;

    $results = $this->olc->db->select(
      $query,
      $query_vals
    );

    $total_results = $this->olc->db->select( "SELECT COUNT(id) as total FROM users" );

    $return = array();
    $users = array();

    foreach( $results as $user ) {
      $users[] = array(
        "ID" => $user->id,
        "name" => $user->first_name . " " . $user->last_name,
        "first_name" => $user->first_name,
        "last_name" => $user->last_name,
        "email" => $user->email,
        "phone" => $user->phone,
        "stations" => ( empty( $user->stations ) ) ? [] : unserialize( $user->stations ),
        "default_station" => $user->default_station,
        "role" => $user->role,
      );
    }

    $return['results'] = $users;
    $return['totalCount'] = $total_results[0]->total;
    $return['success'] = true;

    return $return;
  }

  public function verify_user_login( $args ) {

    $mappings = [
      ":email" => $args['email'],
    ];

    $result = $this->olc->db->select("
        *
        FROM `users`
        WHERE `email` = :email
        LIMIT 1
      ",
      $mappings
    );

    if ( $result === false ) {
      return [
        "success" => false,
        "message" => "User does not exist.",
      ];
    } else if ( empty( $result ) ) {
      return [
        "success" => false,
        "message" => "User does not exist.",
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

    if( empty( $args['username'] ) ) {
      return [
        'success' => false,
        'message' => 'A username is required.'
      ];
    }

    if( empty( $args['email'] ) ) {
      return [
        'success' => false,
        'message' => 'An email is required.'
      ];
    }

    if( empty( $args['role'] ) ) {
      return [
        'success' => false,
        'message' => 'A role is required.'
      ];
    }

    if( empty( $args['password'] ) ) {
      return [
        'success' => false,
        'message' => 'A password is required.'
      ];
    }

    $mappings = [
      ":username" => $args['username'],
      ":email" => $args['email'],
    ];

    $result = $this->olc->db->select("
        *
        FROM `users`
        WHERE `username` = :username
        OR `email` = :email
        LIMIT 1
      ",
      $mappings
    );

    if( !empty( $result ) && $result !== false ) {
      return [
        'success' => false,
        'message' => 'A user with this information already exists.'
      ];
    }

    $email = $args['email'];
    $role = $args['role'];
    $username = $args['username'];
    $password = $args['password'];
    $password = password_hash( $password, PASSWORD_BCRYPT, [ 'cost' => 12 ] );

    if( empty( $args['username'] ) ) {
      $username = $email;
    }

    $this->olc->db->insert(
      'users',
      [
        'username' => $username,
        'password' => $password,
        'email' => $email,
        'role' => $role
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
    $decoded = JWT::decode( $decoded_token, 'supersecretkeyyoushouldnotcommittogithub', array('HS256') );
    $return = $decoded->user;
    $return->status = 'success';
    return $return;
  }

  public function get_user( $user_id ) {
    $mappings = [
      ":user_id" => $user_id
    ];

    $result = $this->olc->db->select("
        *
        FROM `users`
        WHERE `id` = :user_id
        LIMIT 1
      ",
      $mappings
    );

    if ( $result === false ) {
      return [
        "success" => false,
        "message" => "User does not exist.",
      ];
    } else if ( empty( $result ) ) {
      return [
        "success" => false,
        "message" => "User does not exist.",
      ];
    }

    $user = reset( $result );
    $user = (array) $user;
    unset( $user['password'] );

    return [
      'success' => true,
      'results' => $user
    ];
  }

  public function update_user( $user_id, $args ) {
    if( empty( $args['first_name'] ) || empty( $args['last_name'] ) ) {
      return [
        'success' => false,
        'message' => 'A name is required.'
      ];
    }

    if( empty( $args['email'] ) ) {
      return [
        'success' => false,
        'message' => 'An email address is required.'
      ];
    }

    if( empty( $args['role'] ) ) {
      return [
        'success' => false,
        'message' => 'The user must have a role - set role to none if you would like to terminate their access.'
      ];
    }

    $first_name = $args['first_name'];
    $last_name = $args['last_name'];
    //$password = password_hash( $args['password'], PASSWORD_BCRYPT, [ 'cost' => 12 ] );
    $default_station_id = $args['default_station'];
    $phone = $args['phone'];
    $email = $args['email'];
    $role = $args['role'];

    $result = $this->olc->db->update(
      'users',
      [
        //'password' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'stations' => serialize( $args['stations']),
        'default_station' => $default_station_id,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
      ],
      array(
        'id' => $user_id,
      )
    );

    if ( $result === false ) {

      return [
        'success' => false,
        'message' => "There was an error updating the user.",
        'errors' => true
      ];
    }

    return array(
      "success" => true,
      "message" => "The user has been updated.",
    );
  }
}