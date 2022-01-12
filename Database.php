<?php

// Singleton!
class Database
{
    /**
     * @var $dbInstance PDO
     */
    private static ?PDO $dbInstance = null;

    private function __construct() {

    }

    public static function getInstance(): PDO {
        if (self::$dbInstance == null) {
            self::$dbInstance = new PDO("mysql:dbname=todo_system;host=localhost;port=3306  ", "root", "");
        }

        return self::$dbInstance;
    }
}