<?php

namespace App\Controller;

/**
 * Class stationController
 * @package App\Controller
 */
class roomsController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  public function create_new_room( $args, $token) {

    if( empty( $args['name'] ) ) {
      return [
        'success' => false,
        'message' => 'An room name is required.'
      ];
    }

    if( empty( $args['description'] ) ) {
      return [
        'success' => false,
        'message' => 'A room description is required.'
      ];
    }

    /**
     * Make sure that the area exists and the user has permission to touch it
     */
    if( $token['user']->role === "administrator" ) {
      $mappings = [
        ":area_id" => $args['area_id'],
      ];

      $query = " * FROM `areas` WHERE `id` = :area_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    } else {
      $mappings = [
        ":area_id" => $args['area_id'],
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
        'message' => 'The room you are trying to create either does not exist, or belongs to an area that you do not have permission to edit.'
      ];
    }

    $name = $args['name'];
    $description = $args['description'];
    $room_flags = $args['room_flags'];
    $sector_type = $args['sector_type'];
    $exits = $args['exits'];
    $extra_descr_data = $args['extra_descr_data'];
    $roomtext_data = $args['roomtext_data'];
    $area_id = $args['area_id'];

    $this->olc->db->insert(
      'rooms',
      [
        'name' => $name,
        'description' => $description,
        'room_flags' => $room_flags,
        'sector_type' => $sector_type,
        'exits' => $exits,
        'extra_descr_data' => $extra_descr_data,
        'roomtext_data' => $roomtext_data,
        'area_id' => $area_id
      ]
    );

    $room_response = self::get_rooms([ "area_id" => $area_id], $token);

    return array(
      "success" => true,
      "message" => "The room has been created.",
      "results" => $room_response['results']
    );
  }

  public function get_rooms( $args, $token ) {
    $valid_arg_keys = [
      'area_id',
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

    $query = "SELECT * FROM rooms a";

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

    $total_results = $this->olc->db->select( "SELECT COUNT(id) as total FROM rooms WHERE area_id = :area_id", [ ":area_id" => $args['area_id'] ] );

    $return = array();
    $rooms = [];

    foreach( $results as $room ) {
      $rooms[] = array(
        "ID" => $room->id,
        "name" => $room->name,
        "description" => $room->description,
        "room_flags" => $room->room_flags,
        "sector_type" => $room->sector_type,
        "exits" => $room->exits,
        "extra_descr_data" => $room->extra_descr_data,
        "roomtext_data" => $room->roomtext_data,
        "area_id" => $room->area_id,
      );
    }

    $return['results'] = $rooms;
    $return['totalCount'] = $total_results[0]->total;
    $return['success'] = true;

    return $return;
  }

  public function update_room( $room_id, $args ) {
    if( empty( $args['name'] ) ) {
      return [
        'success' => false,
        'message' => 'An room name is required.'
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
     * If the user is an administrator, then check to see if the room exists. If the user is just a builder, only update an room that they created
     */
    if( $token['user']->role === "administrator" ) {
      $mappings = [
        ":room_id" => $room_id,
      ];

      $query = " * FROM `rooms` WHERE `id` = :room_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    } else {
      $mappings = [
        ":room_id" => $room_id,
        ":user_id" => $args['user_id'],
      ];

      $query = " * FROM `rooms` WHERE `id` = :room_id AND user_id = :user_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    }

    if( !empty( $result ) && $result !== false ) {
      return [
        'success' => false,
        'message' => 'The room you are trying to edit either does not exist or you do not have permission to edit it.'
      ];
    }

    $name = $args['name'];
    $staring_vnum = $args['staring_vnum'];

    $result = $this->olc->db->update(
      'rooms',
      [
        'name' => $name,
        'staring_vnum' => $staring_vnum,
      ],
      array(
        'id' => $room_id,
      )
    );

    if ( $result === false ) {

      return [
        'success' => false,
        'message' => "There was an error updating the user.",
        'errors' => true
      ];
    }

    $room_response = self::get_rooms([], $token);

    return array(
      "success" => true,
      "message" => "The room has been updated.",
      "results" => $room_response['results']
    );

  }

  public function get_room( $room_id, $token ) {
    if( empty( $token['user'] ) ) {
      return [
        'success' => false,
        'message' => 'You must be logged in to access this.'
      ];
    }

    /**
     * If the user is an administrator, then check to see if the room exists. If the user is just a builder, only update an room that they created
     */
    if( $token['user']->role === "administrator" ) {
      $mappings = [
        ":room_id" => $room_id,
      ];

      $query = " * FROM `rooms` WHERE `id` = :room_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    } else {
      $mappings = [
        ":room_id" => $room_id,
        ":user_id" => $token['user']->id,
      ];

      $query = " * FROM `rooms` WHERE `id` = :room_id AND user_id = :user_id LIMIT 1";

      $result = $this->olc->db->select(
        $query,
        $mappings
      );
    }

    if( empty( $result ) && $result !== false ) {
      return [
        'success' => false,
        'message' => 'The room you are trying to retrieve either does not exist or you do not have permission to view it.'
      ];
    }

    return array(
      "success" => true,
      "message" => "The room has been updated.",
      "results" => $result[0]
    );
  }

}