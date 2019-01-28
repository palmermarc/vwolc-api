<?php

namespace App\Controller;

class stationController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  public function get_all_markets() {
    $markets = $this->olc->db->select( "SELECT * FROM markets" );

    return $markets;
  }

  public function get_market( $market_id = null ) {

    $mappings = array(
      ":market_id" => $market_id
    );

    $result = $this->olc->db->select("
           `name`, `slug`
           FROM `markets`
           WHERE `id` = :market_id
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
        "market" => $market_id
      );
    } else if ( empty( $result ) ) {
      return array(
        "success" => true,
        "totalCount" => 0,
        "message" => "No record found.",
        "results" => array(),
        "market" => $market_id
      );
    }

    $market = reset($result);

    return array(
      "name" => $market->name,
      "slug" => $market->slug,
    );


  }

  public function update( $market_id, $fields ) {



    //"id":"1","name":"Minneapolis","slug":"msp","hidden":"0"

    // name, slug

  }

  /**
   * Save an entire record or multiple field values
   *
   * @param $record_id
   * @param $fields
   * @return array
   */
  public function updateMarket( $record_id, $fields ) {

    if ( !empty( $fields ) ) {

      $errors = false;

      foreach ($fields as $field) {

        if ( isset( $field["field_value"] ) && !empty( $field['field_id'] ) ) {
          $result = $this->olc->db->update(
            "markets",
            array(
              "field_value" => maybe_serialize_input($field['field_value'])
            ),
            array(
              "record_id" => $record_id,
              "field_id" => $field['field_id']
            )
          );

          if ( $result === false ) {
            $errors = true;
          }
        }
      } // endforeach

      return array(
        "success" => true,
        "errors" => $errors,
        "record_id" => $record_id,
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

  public function deleteMarket( $args ) {
    $market_id = $args['market_id'];

    if( 0 == abs( intval( $args['market_id'] ) ) ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Market ID has not been defined",
        "results" => array(),
        "market" => $market_id
      );
    }

    $result = $this->olc->db->delete( 'markets', array( 'id' => $market_id ) );

    return array(
      "success" => true,
      "totalCount" => 0,
      "message" => "The market has been deleted.",
      "results" => $result,
      "market" => $market_id
    );
  }

  public function createMarket( $args ) {

    if( empty( $args['name'] ) ) {
      return array(
        "success" => false,
        "message" => "A name is required to create a market",
      );
    }

    $name = $args['name'];

    $raw_slug = ( empty( $args['slug'] ) ) ? $name : $args['slug'];

    $slug = strtolower( str_replace( ' ', '-', $raw_slug ) );

    $this->olc->db->insert(
      'markets',
      array(
        'name' => $name,
        'slug' => $slug
      )
    );

    return array(
      "success" => true,
      "message" => "The '" . $name ."' market has been created."
    );
  }

}