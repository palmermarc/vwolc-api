<?php

namespace App\Controller;

/**
 * Class stationController
 * @package App\Controller
 */
class contestsController {

  protected $marcopromo;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->marcopromo = $container;
  }


  public function delete_contest( $contest_id ) {

    if( 0 == absint( $contest_id ) ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Please specific a contest to delete",
        "results" => array(),
        "station" => $contest_id
      );
    }

    $this->marcopromo->delete( 'contests', array( 'id' => $contest_id ) );

    return array(
      "success" => true,
      "totalCount" => 0,
      "message" => "The contest has been deleted.",
      "results" => $result,
      "station" => $contest_id
    );
  }

  function get_contents( $search_fields ) {
    $valid_arg_keys = [
      'station_id',
      'type',
      'location',
      'active',
      'search'
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

    // TODO: Make sure that we have the ability to search for contests by station, market, name, prize, prize value, type, etc.
    $query = "SELECT c.*, s.id as station_id, s.name as station_name, s.slug as station_slug FROM contests c LEFT JOIN stations s on c.station_id = s.id";

    $i = 0;

    foreach( $validated_args as $key => $val ) {

      if( $i == 0 ) {
        $query .= " WHERE ";
      } else {
        $query .= " AND ";
      }

      if( $key == 'search' ) {
        $query .= 'c.name LIKE :name';
        $val = '%' . $val . '%';
        $key = 'name';
      } else {
        $query .= $key . " = :" . $key;
      }

      $query_vals[':'.$key] = $val;

      $i++;
    }

    $query .= " LIMIT " . $limit . " OFFSET " . $offset;

    $results = $this->marcopromo->db->select(
      $query,
      $query_vals
    );

    $total_results = $this->marcopromo->db->select("SELECT COUNT(id) as total FROM contests");

    $return = array();
    $copies = array();

    foreach( $results as $contest ) {
      $copies[] = array(
        "ID" => $contest->id,
        "name" => $contest->name,
        "content" => $contest->content,
        "instructions" => $contest->instructions,
        "start_date" => $contest->start_date,
        "end_date" => $contest->end_date,
        "type" => $contest->type,
        "station" => array(
          "ID" => $contest->station_id,
          "name" => $contest->station_name,
          "slug" => $contest->station_slug
        )
      );
    }

    $return['results'] = $copies;
    $return['totalCount'] = $total_results[0]->total;
    $return['success'] = true;
    $return['query'] = $query;
    $return['query_args'] = $query_vals;

    return $return;
  }

  /**
   * @return mixed
   */
  public function getMarcopromo()
  {
    return $this->marcopromo;
  }


}