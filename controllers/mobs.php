<?php

namespace App\Controller;

/**
 * Class copyController
 * @package App\Controller
 */
class copyController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  /**
   * @return array
   */
  public function get_copies( $args ) {

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

    $query = "SELECT c.*, s.id as station_id, s.name as station_name, s.slug as station_slug FROM copies c LEFT JOIN stations s on c.station_id = s.id";

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

    $results = $this->olc->db->select(
      $query,
      $query_vals
    );

    $total_results = $this->olc->db->select("SELECT COUNT(id) as total FROM copies");

    $return = array();
    $copies = array();

    foreach( $results as $copy ) {
      $copies[] = array(
        "ID" => $copy->id,
        "name" => $copy->name,
        "content" => $copy->content,
        "instructions" => $copy->instructions,
        "start_date" => $copy->start_date,
        "end_date" => $copy->end_date,
        "type" => $copy->type,
        "station" => array(
          "ID" => $copy->station_id,
          "name" => $copy->station_name,
          "slug" => $copy->station_slug
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

  public function get_copy( $copy_id ) {

    if( empty( $copy_id ) ) {
      return array( 'error_message' => 'A valid copy id is required' );
    }

    $result = $this->olc->db->select(
      "SELECT c.*, s.id as station_id, s.name as station_name, s.slug as station_slug FROM copies c LEFT JOIN stations s on c.station_id = s.id WHERE c.id = :copy_id",
      [ ":copy_id" => $copy_id ]
    );

    if ( $result === false ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Could not retrieve record.",
        "results" => array(),
        "copy" => $copy_id
      );
    } else if ( empty( $result ) ) {
      return array(
        "success" => true,
        "totalCount" => 0,
        "message" => "No record found.",
        "results" => array(),
        "copy" => $copy_id
      );
    }

    $copy = reset( $result );

    $schedule_results = $this->olc->db->select(
      "* FROM schedules WHERE copy_id = :copy_id",
      [ ":copy_id" => $copy_id ]
    );

    if( $schedule_results === false ) {
      $schedule_results = array();
    }
    $schedules = [];

    if( !empty( $schedule_results ) ) {
      foreach ($schedule_results as $schedule_result) {
        $schedules[] = [
          'id' => $schedule_result->id,
          'date' => $schedule_result->date,
          'time' => $schedule_result->time,
          'logged' => $schedule_result->logged,
          'created' => $schedule_result->created
        ];
      }
    }

    $schedules = array();

    $return = [
      "success" => true,
      "results" => [
        "ID" => $copy->id,
        "name" => $copy->name,
        "content" => $copy->content,
        "instructions" => $copy->instructions,
        "start_date" => $copy->start_date,
        "end_date" => $copy->end_date,
        "type" => $copy->type,
        "exclude_from_30day_rule" => $copy->exclude_from_30day_rule,
        "prize_amount" => $copy->prize_amount,
        "minimum_age" => $copy->minimum_age,
        "delivery_method" => $copy->delivery_method,
        "station" => [
          "ID" => $copy->station_id,
          "name" => $copy->station_name,
          "slug" => $copy->station_slug
        ],
        "schedules" => $schedule_results
      ]
    ];

    return $return;

  }

  public function delete_copy( $copy_id ) {
    if( 0 == abs( intval( $copy_id) ) ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Copy ID has not been defined",
        "results" => array(),
        "copy" => $copy_id
      );
    }

    $result = $this->olc->db->delete( 'copies', array( 'id' => $copy_id ) );

    return array(
      "success" => true,
      "totalCount" => 0,
      "message" => "The copy has been deleted.",
      "results" => $result,
      "copy" => $copy_id
    );
  }

  public function create_copy( $submitted_args ) {

    // Validate the responses
    $args = array_merge( [ 'station_id' => 0, 'content' => '', 'instructions' => '', 'start_date' => '', 'end_date' => '', 'type' => '' ], $submitted_args );

    $errors = [];

    if( empty( $args['name'] ) ) {
      $errors[] = 'The Copy must have a name.';
    }

    if( empty( $args['content'] ) ) {
      $errors[] = 'The Copy Content can not be empty.';
    }

    if( empty( $args['start_date'] ) ) {
      $errors[] = 'The Start Date can not be empty.';
    }

    if( empty( $args['end_date'] ) ) {
      $errors[] = 'The End Date can not be empty.';
    }

    if( empty( $args['type'] ) ) {
      $errors[] = 'The Copy Type can not be empty.';
    }

    if( 0 == $args['station_id'] ) {
      $errors[] = 'The Station ID can not be empty.';
    }

    if( !empty( $errors ) ) {
      return [
        'errors' => $errors,
        'success' => false,
        'original_request' => $args
      ];
    }


    $copy_id = $this->olc->db->insert(
      'copies',
      array(
        'name' => $args['name'],
        'station_id' => $args['station_id'],
        'content' => $args['content'],
        'instructions' => $args['instructions'],
        'start_date' => $args['start_date'],
        'end_date' => $args['end_date'],
        'type' => $args['type'],
      )
    );

    $message = "Your copy has been created.";

    if( isset( $args['schedule'] ) && is_array( $args['schedule'] ) ) :

      $copy_scheduled = create_copy_schedule( $args['schedule'] );

      $message = "Your copy has been created successfully and the system has set " . $copy_scheduled . " items into the schedule.";
    endif;

    return [
      'success' => true,
      'message' => $message,
      'copy_id' => $copy_id
    ];
  }

  public function update_copy( $copy_id, $data ) {

    if ( !empty( $data ) ) {

      $errors = false;

      $result = $this->olc->db->update(
        'copies',
        (array) $data,
        array(
          'id' => $copy_id,
        )
      );

      if ( $result === false ) {
        $errors = true;
        $message = "The record was saved" . ( $errors ? " with some errors." : "." );
      } else {

        $schedule_response = '';

        if( isset( $data['schedule'] ) && is_array( $data['schedule'] ) ) :
          $schedule_response = $this->handle_schedule_changes( $copy_id, $data['station_id'], $data['schedule'] );
        endif;
      }

      $message = "Your copy and copy schedules have been updated.";

      return array(
        "success" => true,
        "errors" => $errors,
        "message" => $message,
        "schedule_response" => $schedule_response
      );

    } else {
      return array(
        "success" => false,
        "message" => "The field data appears to be missing in the request.",
        "fields" => $data
      );
    }
  }

  function handle_schedule_changes( $copy_id, $station_id, $new_schedule ) {

    $current_schedule_results = $this->olc->db->select(
      "* FROM schedules WHERE copy_id = :copy_id",
      [ ':copy_id' => $copy_id ]
    );

    $message = '';
    $has_copy = ( false === $current_schedule_results ) ? false : true;
    $current_schedule = [];

    if( $has_copy ) :
      // Loop through the array and just grab the IDs
      foreach( $current_schedule_results as $result ) :
        $current_schedule[$result->id] = true;
      endforeach;
    endif;



    // Loop through the new schedule and handle accordingly
    foreach( $new_schedule as $schedule ) {
      if( isset( $schedule['id'] ) ) {
        $this->update_copy_schedule( $schedule['id'], $station_id, $schedule['date'], $schedule['time'] );
        unset( $current_schedule[$schedule['id']]);
      } else {
        $this->create_copy_schedule( $cop_id, $station_id, $schedule['date'], $schedule['time'] );
      }
    }


    // For anything that was not unset from the database, delete it.
    foreach( $current_schedule as $id_to_remove => $throwaway ) {
      $this->delete_copy_schedule( $id_to_remove );
    }

    return $message;
  }


  function create_copy_schedule( $copy_id = null, $station_id = null, $date = null , $time = null ) {
    $copy_scheduled = 0;

    if( $copy_id === null  || $station_id === null || $date === null || $time === null ) {
      return false;
    }

    $this->olc->db->insert(
      'schedules',
      array(
        'copy_id' => $copy_id,
        'station_id' => $station_id,
        'date' => $date,
        'time' => $time
      )
    );

    return $copy_scheduled;
  }


  /**
   * Delete the Copy Schedule based on the ID provided
   *
   * @param $schedule_id
   */
  function delete_copy_schedule( $schedule_id ) {
    $this->olc->db->delete(
      'schedules',
      [ 'id' => $schedule_id ]
    );
  }


  /**
   * Update the Copy Schedule based on the parameters provided
   *
   * @param $schedule_id
   * @param $date
   * @param $time
   */
  function update_copy_schedule( $schedule_id, $station_id, $date, $time ) {
    $this->olc->db->update(
      'schedules',
      [
        'date' => $date,
        'time' => $time,
        'station_id' => $station_id
      ],
      [
        'id' => $schedule_id
      ]
    );
  }

}