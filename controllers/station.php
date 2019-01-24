<?php

namespace App\Controller;

/**
 * Class stationController
 * @package App\Controller
 */
class stationController {

  protected $marcopromo;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->marcopromo = $container;
  }

  /**
   * @return array
   */
  public function get_all_stations() {
    $results = $this->marcopromo->db->select(
      "SELECT s.*, m.name as market_name FROM stations s LEFT JOIN markets m on m.id = s.market_id"
    );

    $stations = array(
      'success' => true,
      'results' => []
    );

    foreach( $results as $station ) {
      $stations['results'][] = array(
        "ID" => $station->id,
        "name" => $station->name,
        "slug" => $station->slug,
        "call_letters" => $station->call_letters,
        "market" => array(
          "ID" => $station->market_id,
          "name" => $station->market_name
        )
      );
    }

    return $stations;
  }

  public function get_station( $station_id = null ) {

    $mappings = array(
      ":station_id" => $station_id
    );

    $result = $this->marcopromo->db->select("
        `name`, `slug`
        FROM `stations`
        WHERE `id` = :station_id
        LIMIT 1
      ",
      $mappings
    );

    if ( $result === false ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Could not retrieve record.",
        "results" => array(),
        "station" => $station_id
      );
    } else if ( empty( $result ) ) {
      return array(
        "success" => true,
        "totalCount" => 0,
        "message" => "No record found.",
        "results" => array(),
        "station" => $station_id
      );
    }

    $station = reset($result);

    return array(
      "ID" => $station->id,
      "name" => $station->name,
      "slug" => $station->slug,
      "call_letters" => $station->slug,
      "market" => array(
        "ID" => $station->market_id,
        "name" => $station->market_name
      )
    );

  }

  /**
   * Update an entire record or multiple field values
   *
   * @param $record_id
   * @param $fields
   * @return array
   */
  public function update_station_record( $station_id, $fields ) {

    if ( !empty( $fields ) ) {

      $errors = false;

      $result = $this->marcopromo->db->update(
        'stations',
        (array) $fields,
        array(
          'id' => $station_id,
        )
      );

      return $result;

      if ( $result === false ) {
        $errors = true;
      }

      return array(
        "success" => true,
        "errors" => $errors,
        "record_id" => $station_id,
        "message" => "The record was saved" . ( $errors ? " with some errors." : "." ),
      );

    } else {
      return array(
        "success" => false,
        "message" => "The field data appears to be missing in the request.",
        "fields" => $fields
      );
    }
  }

  /**
   * Delete a record
   *
   * @param $record_id
   * @return array
   */

  public function delete_station( $args ) {
    $station_id = $args['station_id'];

    if( 0 == absint( $args['station_id'] ) ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Station ID has not been defined",
        "results" => array(),
        "station" => $station_id
      );
    }

    $result = $this->marcopromo->db->delete( 'stations', array( 'id' => $station_id ) );

    return array(
      "success" => true,
      "totalCount" => 0,
      "message" => "The station has been deleted.",
      "results" => $result,
      "station" => $station_id
    );
  }

  /**
   * Create a new station
   *
   * @param $fields
   * @return array
   */
  public function create_station( $args ) {

    if( empty( $args['name'] ) ) {
      return array(
        "success" => false,
        "message" => "A name is required to create a station",
      );
    }

    if( empty( $args['call_letters'] ) ) {
      return array(
        "success" => false,
        "message" => "You cannot create a station without providing the call letters.",
      );
    }

    if( empty( $args['market_id'] ) || abs( $args['market_id'] ) != $args['market_id'] ) {
      return array(
        "success" => false,
        "message" => "A valid market id is required"
      );
    }

    $name = $args['name'];

    $market_id = $args['market_id'];

    $raw_slug = ( empty( $args['slug'] ) ) ? $name : $args['slug'];

    $slug = strtolower( str_replace( array( ' ', '_' ), '-', $raw_slug ) );

    $this->marcopromo->db->insert(
      'stations',
      array(
        'market_id' => $market_id,
        'name' => $name,
        'slug' => $slug,
        'call_letters' => $args['call_letters']
      )
    );

    return array(
      "success" => true,
      "message" => "The '" . $name ."' station has been created."
    );
  }

}
