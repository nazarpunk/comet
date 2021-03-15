<?php /**
 * @noinspection PhpUnused
 * @noinspection SqlNoDataSourceInspection
 */
declare(strict_types=1);

namespace nazarpunk\Comet;

use mysqli;
use Exception;

class Comet {
	/**
	 * @param int $user_id
	 * @return string|null
	 * @throws Exception
	 */
	public static function get_user_key(int $user_id): ?string {
		if (!is_array(self::$setting)) throw new Exception('No Comet Connection param');
		return sha1(self::$setting['user_secret'] . $user_id);
	}

	private static array $setting;

	public static function setting(
		// connection
		string $hostname = null
		, string $username = null
		, string $password = null
		, string $database = 'CometQL_v1'
		, int $port = 3307
		// user
		, string $user_secret = null

	) {
		self::$setting = [
			// connection
			'connection'  => [
				'hostname' => $hostname,
				'username' => $username,
				'password' => $password,
				'database' => $database,
				'port'     => $port
			],
			// user
			'user_secret' => $user_secret
		];
	}

	/**
	 * @return mysqli
	 * @throws Exception
	 * @noinspection SqlResolve
	 */
	private static function get_connection(): mysqli {
		static $comet;
		if (isset($comet)) return $comet;
		if (!is_array(self::$setting)) throw new Exception('No Comet Connection param');

		$comet = @new mysqli(...self::$setting['connection']);
		if ($comet->connect_errno) throw new Exception($comet->connect_errno);

		return $comet;
	}

	/**
	 * @param array $data
	 * @return string
	 * @throws Exception
	 */
	private static function json(array $data): string {
		return self::get_connection()->real_escape_string(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
	}

	/**
	 * @param int    $user_id
	 * @param string $event
	 * @param mixed  $data
	 * @return array
	 * @throws Exception
	 * @noinspection SqlResolve
	 */
	public static function send_user(int $user_id, string $event, array $data = []): array {
		$json = self::json($data);
		self::get_connection()->query("insert into users_auth (id, hash) values ($user_id,'" . self::get_user_key($user_id) . "')");
		self::get_connection()->query("insert into users_messages (id,event,message) values ($user_id,'$event','$json')");
		return $data;
	}

	/**
	 * @param        $channel
	 * @param string $event
	 * @param array  $data
	 * @return array
	 * @throws Exception
	 * @noinspection SqlResolve
	 */
	public static function send_channel(string $channel, string $event, array $data = []): array {
		$json = self::json($data);
		self::get_connection()->query("insert into pipes_messages (name, event, message) values ('$channel','$event','$json')");
		return $data;
	}
}
