<?php

/**
 * get system uptime
 * @param string $format The following characters are recognized in the format parmaeter string
 */
function get_uptime($format = 'd')
{
    if (strtolower(PHP_OS) == 'linux') {
        $uptime = @file_get_contents("/proc/uptime");
        if ($uptime !== false) {
            $uptime = explode(" ", $uptime);
            $uptime = $uptime[0];
            $days = explode(".", (($uptime % 31556926) / 86400));
            $hours = explode(".", ((($uptime % 31556926) % 86400) / 3600));
            $minutes = explode(".", (((($uptime % 31556926) % 86400) % 3600) / 60));
            $time = ".";
            if ($minutes > 0)
                $time = $minutes[0] . " mins" . $time;
            if ($minutes > 0 && ($hours > 0 || $days > 0))
                $time = ", " . $time;
            if ($hours > 0)
                $time = $hours[0] . " hours" . $time;
            if ($hours > 0 && $days > 0)
                $time = ", " . $time;
            if ($days > 0)
                $time = $days[0] . " days" . $time;
        } else {
            $time = false;
        }
    } else {
        return false;
    }
    return $time;
}


function friendlySize($sizeInBytes, $precision = 2)
{
    $units = ['bytes', 'M', 'G', 'T'];
    $newSize = $sizeInBytes;
    for ($i = 0; $i < sizeof($units); ++$i) {
        $tmp = round($newSize / 1024, $precision);
        if ($tmp < 1)
            break;
        $newSize = $tmp;
    }
    return $newSize . $units[$i];
}