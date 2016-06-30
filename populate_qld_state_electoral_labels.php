<?php

$pgconn = pg_connect("host=localhost port=5432 dbname=gisdb user=maps password=spam");

$result = pg_query($pgconn, "SELECT centroid, adminarean FROM qld_state_electoral_labels");
$n = 0;
while ($row = pg_fetch_array($result)) {
    preg_match('/([0-9.\-]+) ([0-9.\-]+)/', $row['centroid'], $centroid);
    $longitude = $centroid[1];
    $latitude = $centroid[2];
    $adminarean = $row['adminarean'];
    switch ($adminarean) {
        case 'ASHGROVE':
            $latitude -= 0.01;
            break;
        case 'BURDEKIN':
            $latitude -= 0.1;
            $longitude -= 0.1;
            break;
        case 'BURNETT':
            $longitude -= 0.6;
            break;
        case 'CAIRNS':
            $longitude -= 0.1;
            $latitude -= 0.09;
            break;
        case 'CONDAMINE':
            $latitude -= 0.08;
            break;
        case 'GLADSTONE':
            $longitude -= 0.6;
            $latitude -= 0.3;
            break;
        case 'HINCHINBROOK':
            $longitude -= 0.25;
            break;
        case 'KEPPEL':
            $longitude -= 0.8;
            break;
        case 'LOCKYER':
            $longitude -= 0.1;
            break;
        case 'LYTTON':
            $longitude -= 0.04;
            $latitude -= 0.04;
            break;
        case 'MACKAY':
            $longitude -= 0.05;
            break;
        case 'MIRANI':
            $longitude -= 0.8;
            break;
        case 'MULGRAVE':
            $longitude -= 0.3;
            break;
        case 'NANANGO':
            $longitude += 0.1;
            break;
        case 'REDLANDS':
            $longitude -= 0.05;
            break;
        case 'SANDGATE':
            $longitude -= 0.06;
            break;
        case 'TOWNSVILLE':
            $longitude -= 0.6;
            $latitude -= 0.35;
            break;
        case 'WARREGO':
            $longitude += 1.0;
            break;
        case 'WHITSUNDAY':
            $longitude -= 1.0;
            break;
    }
    $displayname = ucwords(strtolower($adminarean), $delimiters = " \t\r\n\f\v'");
    pg_query("UPDATE qld_state_electoral_labels SET adminarean_displayname='" .
        pg_escape_string($displayname) .
        "', label_latitude=$latitude, label_longitude=$longitude WHERE adminarean='" .
        pg_escape_string($adminarean) . "'");
    $n++;
}

echo "Updated $n rows.\n";
