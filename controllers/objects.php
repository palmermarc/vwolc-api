<?php

namespace App\Controller;

/**
 * Class copyController
 * @package App\Controller
 */
class listenersController {

  protected $olc;

  // constructor receives container instance
  public function __construct( $container ) {
    $this->olc = $container;
  }

  /**
   * @return array
   */
  public function get_listeners( $args ) {

    $valid_arg_keys = [
      'first_name',
      'last_name',
      'phone',
      'zip',
      'email_address',
    ];

    $validated_args = [];
    $query_vals = [];

    foreach( $args as $key => $val ) {
      if( !in_array( $key, $valid_arg_keys ) ) {
        continue;
      }

      $validated_args[$key] = $val;
    }

    $query = "SELECT * FROM listeners ";

    $i = 0;

    foreach( $validated_args as $key => $val ) {

      if( $val != '' ) {
        if( $i == 0 ) {
          $query .= " WHERE ";
        } else {
          $query .= " AND ";
        }


        $query .= $key . " = :" . $key;
        $query_vals[':'.$key] = $val;

        $i++;
      }
    }

    $page = isset( $args['page'] ) ? $args['page'] : 1;

    $limit = 100;
    $offset = $limit * ($page-1);
    $query .= " LIMIT " . $limit . " OFFSET " . $offset;

    $results = $this->olc->db->select(
      $query,
      $query_vals
    );

    $return = [
      'success' => true,
      'totalCount' => 0,
      'results' => []
    ];

    $total_results = $this->olc->db->select("COUNT(id) as total FROM listeners");
    $return['totalCount'] = $total_results[0]->total;
    $listeners = [];

    foreach( $results as $listener ) {
      $listeners[] = array(
        "ID" => $listener->id,
        "first_name" => $listener->first_name,
        "last_name" => $listener->last_name,
        "address" => $listener->address,
        "address2" => $listener->address2,
        "city" => $listener->city,
        "state" => $listener->state,
        "zip" => $listener->zip,
        "primary_phone" => $listener->primary_phone,
        "secondary_phone" => $listener->secondary_phone,
        "email" => $listener->email,
      );
    }

    $return['results'] = $listeners;

    return $return;
  }

  public function get_listener( $listener_id = 0 ) {
    if( $listener_id == 0 or abs( intval( $listener_id ) ) == 0 ) {
      return array(
        'success' => false,
        'errors' => array(
          '' => 'You cannot look up a listener without a valid listener id'
        )
      );
    }


    $query = "SELECT *
      FROM listeners
      WHERE id = :id";

    $results = $this->olc->db->select(
      $query,
      array( ':id' => $listener_id )
    );

    if( $results ) {
      return [
        'success' => 'true',
        'results' =>  reset( $results )
      ];
    }

    return [
      'success' => false,
      'errors' => [ '' => "Something went wrong." ]
    ];

  }

  public function delete_listener( $listener_id ) {

    if( 0 == absint( $listener_id ) ) {
      return array(
        "success" => false,
        "totalCount" => 0,
        "message" => "Please specific a listener to delete",
        "results" => array(),
        "station" => $listener_id
      );
    }

    $result = $this->olc->db->delete( 'listeners', array( 'id' => $listener_id ) );

    return array(
      "success" => true,
      "totalCount" => 0,
      "message" => "The listener has been deleted.",
      "results" => $result,
      "station" => $listener_id
    );
  }

  public function create_new_listener( $args ) {

    $listener_data = array_merge( [
      'first_name' => '',
      'last_name' => '',
      'date_of_birth' => '',
      'address' => '',
      'address2' => '',
      'city' => '',
      'state' => '',
      'zip' => '',
      'primary_phone' => '',
      'secondary_phone' => '',
      'email' => '',
      'notes' => ''
    ], $args );

    $listener_id = $this->olc->db->insert(
      'listeners',
      array(
        'first_name' => $listener_data['first_name'],
        'last_name' => $listener_data['last_name'],
        'date_of_birth' => $listener_data['date_of_birth'],
        'address' => $listener_data['address'],
        'address2' => $listener_data['address2'],
        'city' => $listener_data['city'],
        'state' => $listener_data['state'],
        'zip' => $listener_data['zip'],
        'primary_phone' => $listener_data['primary_phone'],
        'secondary_phone' => $listener_data['secondary_phone'],
        'email' => $listener_data['email']
      )
    );

    return [
      'success' => true,
      'listener_id' => $listener_id,
      'message' => "The user has been successfully created. "
    ];
  }

  public function update_listener( $listener_id, $args ) {
    // Validate the responses
    $listener_data = array_merge( [
      'first_name' => '',
      'last_name' => '',
      'date_of_birth' => '',
      'address' => '',
      'address2' => '',
      'city' => '',
      'state' => '',
      'zip' => '',
      'primary_phone' => '',
      'secondary_phone' => '',
      'email' => '',
    ], $args );

    $errors = [];

    if( empty( $listener_data['first_name'] ) ) {
      $errors['name'] = 'The Copy must have a name.';
    }

    if( empty( $listener_data['last_name'] ) ) {
      $errors['content'] = 'The Copy Content can not be empty.';
    }

    if( empty( $listener_data['email'] ) && empty( $listener_data['primary_phone'] ) ) {
      $errors['primary_phone'] = 'Please provide either an email address or a primary phone number so promotions can contact the winner';
    }

    if( !empty( $errors ) ) {
      return [
        'errors' => $errors,
        'success' => false,
        'original_request' => $args
      ];
    }

    $this->olc->db->update(
      'listeners',
      $listener_data,
      [
        'id' => $listener_id
      ]

    );

    return [
      'success' => true,
      'message' => 'The user has been updated.'
    ];
  }

  public function validate_user_email( $email ) {

  }

}

