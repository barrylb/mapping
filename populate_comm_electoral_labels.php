<?php

$pgconn = pg_connect("host=localhost port=5432 dbname=gisdb user=maps password=spam");

$result = pg_query($pgconn, "SELECT centroid, elect_div FROM comm_electoral_labels");
$n = 0;
while ($row = pg_fetch_array($result)) {
    preg_match('/([0-9.\-]+) ([0-9.\-]+)/', $row['centroid'], $centroid);
    $longitude = $centroid[1];
    $latitude = $centroid[2];
    $elect_div = $row['elect_div'];
    // manual tweaks
    if ($elect_div == 'PEARCE') {
        $longitude -= 0.05;
        $latitude += 0.1;
    } elseif ($elect_div == 'FENNER') {
        $longitude -= 0.28;
    } elseif ($elect_div == 'LEICHHARDT') {
        $longitude -= 0.25;
    } elseif ($elect_div == 'SYDNEY') {
        $latitude = -33.89;
        $longitude = 151.20;
    } elseif ($elect_div == 'BASS') {
        $latitude -= 0.2;
    } elseif ($elect_div == 'GELLIBRAND') {
        $longitude += 0.02;
    } elseif ($elect_div == 'SCULLIN') {
        $latitude -= 0.01;
    } elseif ($elect_div == 'MURRAY') {
        $latitude += 0.1;
    } elseif ($elect_div == 'FREMANTLE') {
        $longitude += 0.03;
    }
    $displayname = ucwords(strtolower($elect_div), $delimiters = " \t\r\n\f\v-'");
    if ($elect_div == 'MCMAHON') {
        $displayname = 'McMahon';
    } elseif ($elect_div == 'MCEWEN') {
        $displayname = 'McEwen';
    } elseif ($elect_div == 'MCMILLAN') {
        $displayname = 'McMillan';
    } elseif ($elect_div == 'MCPHERSON') {
        $displayname = 'McPherson';
    }
    pg_query("UPDATE comm_electoral_labels SET elect_div_displayname='" .
        pg_escape_string($displayname) .
        "', label_latitude=$latitude, label_longitude=$longitude WHERE elect_div='" .
        pg_escape_string($elect_div) . "'");
    $n++;
}

echo "Updated $n rows.\n";
