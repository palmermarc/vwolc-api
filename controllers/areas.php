<?php

namespace App\Controller;

/**
 * Class stationController
 * @package App\Controller
 */
class areasController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  public function get_areas($args) {
    return $args;
    $valid_arg_keys = [
      'user_id'
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

}