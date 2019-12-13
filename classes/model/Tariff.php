<?php

namespace SkyNetBack\Model;

use SkyNetBack\Core\Model;
use SkyNetBack\Helper\DateTime;

class Tariff extends Model {

    public static $table = 'tarifs';

    public static function findById($id)
    {
        $stmt = self::pdo()->prepare("
            SELECT *
            FROM " . self::$table . "
            WHERE ID = ?
            LIMIT 1
        ");

        $stmt->execute([ $id ]);

        $data = $stmt->fetch();

        if (!$data) return null;

        $model = new Tariff();
        $model->data = $data;

        return $model;
    }

    public static function findPlansByServiceId($userId, $serviceId)
    {
        $stmt = self::pdo()->prepare("
            SELECT
                *,
                UNIX_TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL pay_period MONTH)) AS new_payday,
                @@SESSION.time_zone AS timezone
            FROM " . self::$table . "
            WHERE tarif_group_id = (
                SELECT t.tarif_group_id
                FROM " . self::$table . " t
                    LEFT JOIN " . Service::$table . " s ON s.tarif_id = t.ID
                WHERE s.user_id = ? AND s.ID = ?
                LIMIT 1
            )
        ");

        $stmt->execute([ $userId, $serviceId ]);

        $rows = $stmt->fetchAll();

        if (empty($rows)) return [];

        $timeZoneAdd = DateTime::getTimezoneOffsetForDB($rows[0]['timezone']);

        // Find shortest title

        $tariffsData = [];

        foreach ($rows as $row)
        {
            $groupId = +$row['tarif_group_id'];
            $titleLength = mb_strlen($row['title']);

            unset($row['tarif_group_id'], $row['timezone']);

            if (!isset($tariffsData[ $groupId ]))
            {
                // Create item for this tariff group

                $tariffsData[ $groupId ] = [
                    'title_length' => 999999,
                    'plans' => [],
                ];
            }

            $group =& $tariffsData[ $groupId ];

            // Append this tariff to its group
            $group['plans'][] = $row;

            if ($titleLength < $tariffsData[ $groupId ]['title_length']) {
                // And set new tariff with shortest title

                $group['title_length'] = $titleLength;
                $group['data'] = $row;
            }

            unset($group);
        }

        // Create models

        $result = [];

        foreach ($tariffsData as $groupId => $dataItem)
        {
            $tariffModel = new Tariff();
            $tariffModel->data = [
                'title' => $dataItem['data']['title'],
                'link' => $dataItem['data']['link'],
                'speed' => $dataItem['data']['speed'],
                'plans' => [],
            ];

            foreach ($dataItem['plans'] as $planItem)
            {
                // Loop through each plan and create model

                $planItem['new_payday'] .= $timeZoneAdd;
                $planItem['price'] = floatval($planItem['price']);

                unset($planItem['link']);

                $planModel = new Plan();
                $planModel->data = $planItem;

                $tariffModel->data['plans'][] = $planModel;
            }

            $result[] = $tariffModel;
        }

        return $result;
    }

    public function forJSONOutput()
    {
        $result = [
            'title' => $this->data['title'],
            'link' => $this->data['link'],
            'speed' => $this->data['speed'],
            'tarifs' => [],
        ];

        foreach ($this->data['plans'] as $plan)
        {
            $result['tarifs'][] = $plan->forJSONOutput();
        }

        return $result;
    }

}
