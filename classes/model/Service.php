<?php

namespace SkyNetBack\Model;

use SkyNetBack\Core\Model;

class Service extends Model {

    public static $table = 'services';

    public static function findById($userId, $id)
    {
        $stmt = self::pdo()->prepare("
            SELECT * FROM " . self::$table . "
            WHERE user_id = ? AND ID = ?
            LIMIT 1
        ");

        $stmt->execute([ $userId, $id ]);

        $data = $stmt->fetch();

        if (!$data) return null;

        $model = new Service();
        $model->data = $data;

        return $model;
    }

}
