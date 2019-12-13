<?php

namespace SkyNetBack\Core;

use PDO;

class Model {

    private static $pdo;

    public static $table;

    protected $data;
    protected $update;

    public static function pdo()
    {
        if (!isset(self::$pdo))
        {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ];

            self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $opt);
        }

        return self::$pdo;
    }

    public function field($name, $default = null)
    {
        return isset($this->data[ $name ]) ? $this->data[ $name ] : $default;
    }

    public function set($name, $value)
    {
        if (!isset($this->update)) $this->update = [];
        $this->update[ $name ] = [ 'type' => 'value', 'value' => $value ];
    }

    public function setExpr($name, $expression)
    {
        if (!isset($this->update)) $this->update = [];
        $this->update[ $name ] = [ 'type' => 'expression', 'value' => $expression ];
    }

    public function save()
    {
        if (!isset($this->update) || empty($this->update)) return false;

        if (!isset($this->data['ID']))
        {
            throw new \Exception('The model is not loaded');
        }

        $setSQL = [];
        $values = [];

        foreach ($this->update as $key => $value)
        {
            if ($value['type'] == 'expression')
            {
                $setSQL[] = $key . ' = ' . $value['value'];
            }
            else
            {
                $setSQL[] = $key . ' = ?';
                $values[] = $value['value'];
            }
        }

        $values[] = $this->data['ID'];

        $setSQL = implode(',', $setSQL);

        $stmt = self::pdo()->prepare("
            UPDATE " . static::$table . " SET
                {$setSQL}
            WHERE ID = ?
        ");

        $stmt->execute($values);

        return $stmt->rowCount() > 0;
    }

    public function forJSONOutput()
    {
        return $this->data;
    }

}

