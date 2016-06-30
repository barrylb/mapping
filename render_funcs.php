<?php
   
function FillPolygons(
    $image,
    $colour,
    $transparentcolour,
    $wkt,
    $mid_long,
    $mid_lat,
    $scale_horiz,
    $scale_vert
) {
    $image_width = imagesx($image);
    $image_height = imagesy($image);
    $image_horiz_center = $image_width/2;
    $image_vert_center = $image_height/2;

    // Change coordinate format
    $wkt = preg_replace("/([0-9\.\-]+) ([0-9\.\-]+),*/", "$1,$2,", $wkt);
    
    $wkt = substr($wkt, 15);
    $wkt = substr($wkt, 0, -3);
    $polygons = explode(')),((', $wkt);
    
    foreach ($polygons as $polygon) {
        $boundary = explode('),(', $polygon);
        for ($i=0; $i < count($boundary); $i++) {
            $tmp_boundary = $boundary[$i];
            if ($tmp_boundary != '') {
                if ($tmp_boundary[strlen($tmp_boundary)-1] == ',') {
                    $tmp_boundary = substr($tmp_boundary, 0, strlen($tmp_boundary)-1);
                }
                $coord = explode(',', $tmp_boundary);
                $last_x = -99999;
                $last_y = -99999;
                $points = array();
                for ($c=0; $c < count($coord);) {
                    $x = intval(($coord[$c] - $mid_long) * $scale_horiz + $image_horiz_center);
                    $y = $image_height - intval(($coord[$c+1] - $mid_lat) * $scale_vert + $image_vert_center)-1;
                    if (($x != $last_x) || ($y != $last_y)) {
                        $points[] = $x;
                        $points[] = $y;
                    }
                    $c+=2;
                    $last_x = $x;
                    $last_y = $y;
                }
                if (count($points) >= 6) {
                    if ($i == 0) {
                        imagefilledpolygon($image, $points, count($points)/2, $colour);
                    }
                    //else {
                    //    imagefilledpolygon($image, $points, count($points)/2, $transparentcolour);
                    //}
                }
            }
        }
    }
}

function OutlinePolygons(
    $image,
    $color,
    $wkt,
    $mid_long,
    $mid_lat,
    $scale_horiz,
    $scale_vert
) {
    $image_width = imagesx($image);
    $image_height = imagesy($image);
    $image_horiz_center = $image_width/2;
    $image_vert_center = $image_height/2;

    // Change coordinate format
    $wkt = preg_replace("/([0-9\.\-]+) ([0-9\.\-]+),*/", "$1,$2,", $wkt);

    $wkt = substr($wkt, 15);
    $wkt = substr($wkt, 0, -3);
    $polygons = explode(')),((', $wkt);

    foreach ($polygons as $polygon) {
        $boundary = explode('),(', $polygon);
        for ($i=0; $i < count($boundary); $i++) {
            $tmp_boundary = $boundary[$i];
            if ($tmp_boundary != '') {
                if ($tmp_boundary[strlen($tmp_boundary)-1] == ',') {
                    $tmp_boundary = substr($tmp_boundary, 0, strlen($tmp_boundary)-1);
                }
                $coord = explode(',', $tmp_boundary);
                $last_x = -99999;
                $last_y = -99999;
                $points = array();
                for ($c=0; $c < count($coord);) {
                    $x = intval(($coord[$c] - $mid_long) * $scale_horiz + $image_horiz_center);
                    $y = $image_height - intval(($coord[$c+1] - $mid_lat) * $scale_vert + $image_vert_center)-1;
                    if (($x != $last_x) || ($y != $last_y)) {
                        $points[] = $x;
                        $points[] = $y;
                    }
                    $c+=2;
                    $last_x = $x;
                    $last_y = $y;
                }
                if (count($points) >= 6) {
                    imagepolygon($image, $points, count($points)/2, $color);
                }
            }
        }
    }
}

function GetRectangle($wkt)
{
    $wkt = preg_match_all("/([0-9\.\-]+) ([0-9\.\-]+)/", $wkt, $points, PREG_SET_ORDER);
    return $points;
}

function ArrayToQuoteCommaList($alist)
{
    if (!isset($alist)) {
        return '';
    } else {
        foreach ($alist as &$a) {
            $a = "'" . pg_escape_string($a) . "'";
        }
        return join(',', $alist);
    }
}

function QuoteCommaList($alist)
{
    return ArrayToQuoteCommaList(explode(',', $alist));
}

function MergeTemporaryImageIfColour($srcImage, $destImage, $unfilledColour, $ifColour)
{
    // Fill in the final image - scan the temporary image for filled pixels; only change final image pixel if it is land color
    $image_width = imagesx($srcImage);
    $image_height = imagesy($srcImage);
    for ($x=0; $x<$image_width; $x++) {
        for ($y=0; $y<$image_height; $y++) {
            $src_pix = imagecolorat($srcImage, $x, $y);
            $dest_pix = imagecolorat($destImage, $x, $y);
            if (($src_pix != $unfilledColour) && ($dest_pix == $ifColour)) {
                imagesetpixel($destImage, $x, $y, $src_pix);
            }
        }
    }
}

function MergeTemporaryImageIfNotColour($srcImage, $destImage, $unfilledColour, $ifNotColour)
{
    // Fill in the final image - scan the temporary image for filled pixels; only change final image pixel if it is land color
    $image_width = imagesx($srcImage);
    $image_height = imagesy($srcImage);
    for ($x=0; $x<$image_width; $x++) {
        for ($y=0; $y<$image_height; $y++) {
            $src_pix = imagecolorat($srcImage, $x, $y);
            $dest_pix = imagecolorat($destImage, $x, $y);
            if (($src_pix != $unfilledColour) && ($dest_pix != $ifNotColour)) {
                imagesetpixel($destImage, $x, $y, $src_pix);
            }
        }
    }
}

function scanOutline($image, $bgsearchcolor, $outlinecolour)
{
    $image_width = imagesx($image);
    $image_height = imagesy($image);
    for ($x=0; $x<$image_width; $x++) {
        for ($y=0; $y<$image_height-2; $y++) {
            $pix = imagecolorat($image, $x, $y);
            if ($pix == $bgsearchcolor) {
                if (($y > 0) && (imagecolorat($image, $x, $y-1) != $bgsearchcolor)) {
                    imagesetpixel($image, $x, $y-1, $outlinecolour);
                }
                if (($y < $image_height-1) && (imagecolorat($image, $x, $y+1) != $bgsearchcolor)) {
                    imagesetpixel($image, $x, $y+1, $outlinecolour);
                }
                if (($x > 0) && (imagecolorat($image, $x-1, $y) != $bgsearchcolor)) {
                    imagesetpixel($image, $x-1, $y, $outlinecolour);
                }
                if (($x < $image_width-1) && (imagecolorat($image, $x+1, $y) != $bgsearchcolor)) {
                    imagesetpixel($image, $x+1, $y, $outlinecolour);
                }
            }
        }
    }
}
