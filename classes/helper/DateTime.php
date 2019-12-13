<?php

namespace SkyNetBack\Helper;

class DateTime {

    public static function getTimezoneOffsetForDB($dbOffset)
    {
        if (isset($dbOffset) && $dbOffset != 'SYSTEM')
        {
            list($hours, $minutes) = explode(':', $dbOffset);

            if (strlen($hours) < 3)
            {
                // Change a format a little bit

                $sign = substr($hours, 0, 1);
                $hours = $sign . '0' . trim($hours, '+-');
            }

            return "{$hours}:{$minutes}";
        }

        // Return server timezone offset

        $serverTimeZone = new \DateTimeZone(date_default_timezone_get());
        $serverDateTime = new \DateTime("now", $serverTimeZone);

        return str_replace(':', '', $serverDateTime->format('P'));
    }

}
