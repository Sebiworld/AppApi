<?php

namespace ProcessWire;

class Example {
	public static function test() {
		return 'test successful';
	}

	public static function getAllUsers() {
		$response = [
			'users' => []
		];

		foreach (wire('users') as $user) {
			array_push($response['users'], [
				'id' => $user->id,
				'name' => $user->name
			]);
		}

		return $response;
	}

	public static function getUser($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);

		$response = new \StdClass();
		$user = wire('users')->get($data->id);

		if (!$user->id) {
			throw new \Exception('User not found', 404);
		}

		$response->id = $user->id;
		$response->name = $user->name;

		return $response;
	}
}
