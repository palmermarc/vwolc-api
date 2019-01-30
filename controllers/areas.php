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

  public function create_new_area( $args, $token) {

    if( empty( $args['name'] ) ) {
      return [
        'success' => false,
        'message' => 'An area name is required.'
      ];
    }

    if( empty( $args['starting_vnum'] ) ) {
      return [
        'success' => false,
        'message' => 'A starting vnum is required.'
      ];
    }

    if( empty( $token['user'] ) ) {
      return [
        'success' => false,
        'message' => 'You must be logged in to access this.'
      ];
    }

    $name = $args['name'];
    $starting_vnum = $args['starting_vnum'];

    $this->olc->db->insert(
      'areas',
      [
        'user_id' => $token['user']->id,
        'name' => $name,
        'starting_vnum' => $starting_vnum,
      ]
    );

    $area_response = self::get_areas([], $token);

    return array(
      "success" => true,
      "message" => "The area has been created.",
      "results" => $area_response['results']
    );
  }

  public function get_areas( $args, $token ) {
    $valid_arg_keys = [
      'user_id',
      'name'
    ];

    $validated_args = [];

    foreach( $args as $key => $val ) {
      if( !in_array( $key, $valid_arg_keys ) ) {
        continue;
      }

      $validated_args[$key] = $val;
    }

    $page = isset( $args['page'] ) ? $args['page'] : 1;

    $limit = 100;
    $offset = $limit * ($page-1);

    $query = "SELECT * FROM areas a";

    $i = 0;

    $query_vals = [];

    if( $token['user']->role === "administrator" ) {
      $query .= " WHERE 1 = 1 ";
    } else {
      $query_vals[':user_id'] = $token['user']->id;
      $query .= " WHERE user_id = :user_id ";
    }

    foreach( $validated_args as $key => $val ) {
      $query .= " AND " . $key . " = :" . $key;
      $query_vals[':'.$key] = $val;

      $i++;
    }

    $query .= " LIMIT " . $limit . " OFFSET " . $offset;

    $results = $this->olc->db->select(
      $query,
      $query_vals
    );

    $total_results = $this->olc->db->select( "SELECT COUNT(id) as total FROM areas" );

    $return = array();
    $areas = [];

    foreach( $results as $area ) {
      $areas[] = array(
        "ID" => $area->id,
        "name" => $area->name,
        "starting_vnum" => $area->starting_vnum,
        "created_by" => $user_id
      );
    }

    $return['results'] = $areas;
    $return['totalCount'] = $total_results[0]->total;
    $return['success'] = true;

    return $return;
  }

  public function update_area( $area_id, $args ) {
    if( empty( $args['name'] ) ) {
      return [
        'success' => false,
        'message' => 'An area name is required.'
      ];
    }

    if( empty( $args['starting_vnum'] ) ) {
      return [
        'success' => false,
        'message' => 'A starting vnum is required.'
      ];
    }

    if( empty( $token['user'] ) ) {
      return [
        'success' => false,
        'message' => 'You must be logged in to access this.'
      ];
    }


    /**
     * If the user is an administrator, then check to see if the area exists. If the user is just a builder, only update an area that they created
     */
    if( $token['user']->role === "administrator" ) {
      $mappings = [
        ":area_id" => $area_id,
      ];

      $query = " * FROM `areas` WHERE `id` = :area_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    } else {
      $mappings = [
        ":area_id" => $area_id,
        ":user_id" => $args['user_id'],
      ];

      $query = " * FROM `areas` WHERE `id` = :area_id AND user_id = :user_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    }

    if( !empty( $result ) && $result !== false ) {
      return [
        'success' => false,
        'message' => 'The area you are trying to edit either does not exist or you do not have permission to edit it.'
      ];
    }

    $name = $args['name'];
    $staring_vnum = $args['staring_vnum'];

    $result = $this->olc->db->update(
      'areas',
      [
        'name' => $name,
        'staring_vnum' => $staring_vnum,
      ],
      array(
        'id' => $area_id,
      )
    );

    if ( $result === false ) {

      return [
        'success' => false,
        'message' => "There was an error updating the user.",
        'errors' => true
      ];
    }

    $area_response = self::get_areas([], $token);

    return array(
      "success" => true,
      "message" => "The area has been updated.",
      "results" => $area_response['results']
    );

  }

  public function get_area( $area_id, $token ) {
    if( empty( $token['user'] ) ) {
      return [
        'success' => false,
        'message' => 'You must be logged in to access this.'
      ];
    }

    /**
     * If the user is an administrator, then check to see if the area exists. If the user is just a builder, only update an area that they created
     */
    if( $token['user']->role === "administrator" ) {
      $mappings = [
        ":area_id" => $area_id,
      ];

      $query = " * FROM `areas` WHERE `id` = :area_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    } else {
      $mappings = [
        ":area_id" => $area_id,
        ":user_id" => $token['user']->id,
      ];

      $query = " * FROM `areas` WHERE `id` = :area_id AND user_id = :user_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    }

    if( empty( $result ) && $result !== false ) {
      return [
        'success' => false,
        'message' => 'The area you are trying to retrieve either does not exist or you do not have permission to view it.'
      ];
    }

    return array(
      "success" => true,
      "message" => "The area has been updated.",
      "results" => $result[0]
    );
  }

}