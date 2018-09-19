<?php namespace ProcessWire;

class Example
{ 
  public static function test () {
    return 'test successful';
  }

  public static function getAllUsers() {
    $response = new \StdClass();
    $response->users = [];

    foreach(wire('users') as $user) {
      array_push($response->users, [
        "id" => $user->id,
        "name" => $user->name
      ]);
    }

    return $response;
  }
}