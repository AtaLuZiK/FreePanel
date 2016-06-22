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


function hex_dump($data, $newline = "\n")
{
    static $from = '';
    static $to = '';

    static $width = 16; # number of bytes per line

    static $pad = '.'; # padding for non-visible characters

    if ($from === '') {
        for ($i=0; $i<=0xFF; $i++) {
            $from .= chr($i);
            $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
        }
    }

    $hex = str_split(bin2hex($data), $width * 2);
    $chars = str_split(strtr($data, $from, $to), $width);

    $offset = 0;
    foreach ($hex as $i => $line) {
        echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
        $offset += $width;
    }
}
