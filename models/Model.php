<?php
namespace App\Models;

abstract class Model {
    protected \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }
}
