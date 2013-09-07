<?php
class DB {
	private static $config = false;
	private static $client = false;
	private static $db = false;

	public static function collection ($collection) {
		if (self::$config === false) {
			self::$config = Config::db();
		}
		if (self::$client ==- false) {
			self::$client = new MongoClient(self::$config['conn']);
		}
		if (self::$db === false) {
			self::$db = new MongoDB(self::$client, self::$config['name']);
		}
		return new MongoCollection(self::$db, $collection);
	}

	public static function id ($id) {
		return new MongoId((string)$id);
	}
}