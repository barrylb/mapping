<?php
namespace Barrylb\Mapping;

/*
State and Territory boundaries:
http://www.abs.gov.au/AUSSTATS/abs@.nsf/DetailsPage/1270.0.55.001July%202011?OpenDocument
* State (S/T) ASGS Ed 2011 Digital Boundaries in ESRI Shapefile Format - 1270055001_ste_2011_aust_shape.zip
License: http://creativecommons.org/licenses/by/2.5/au/

---------------------------------------------------------

Australian federal electoral boundaries:
https://data.gov.au/dataset/psma-administrative-boundaries
* Commonwealth Electoral Boundaries MAY 2016 - Commonwealth-Electoral-Boundaries-MAY-2016.zip
* Town Points AUGUST 2013 - townpointsaugust2013.zip
License: https://creativecommons.org/licenses/by/4.0/

Preferred attribution for Adapted Material:
Incorporates or developed using Administrative Boundaries
©PSMA Australia Limited licensed by the Commonwealth of Australia under 
Creative Commons Attribution 4.0 International licence (CC BY 4.0).

---------------------------------------------------------

1. Set up VM according to VM_setup.md
2. Run gen_xxx shell scripts to generate maps for each state
*/

require 'render_funcs.php';

class MapRenderer
{
    protected $pgconn;
    protected $centre_lat;
    protected $centre_long;
    protected $scale_horz;
    protected $scale_vert;

    public function __construct()
    {
        $this->pgconn = pg_connect("host=localhost port=5432 dbname=gisdb user=maps password=spam");
    }

    /*
       Determine center and scaling
    */
    protected function determineDimensions($qryText, $zoom, $shiftlat, $shiftlong, $image_width, $image_height)
    {
        $min_long = 9999;
        $max_long = -9999;
        $min_lat = 9999;
        $max_lat = -9999;

        $q_all_mbr = pg_query($this->pgconn, $qryText);
        while ($mbr_row = pg_fetch_array($q_all_mbr)) {
            $rect = GetRectangle($mbr_row['mbr']);
            if ($rect[0][1] < $min_long) {
                $min_long = $rect[0][1];
            }
            if ($rect[2][1] > $max_long) {
                $max_long = $rect[2][1];
            }
            if ($rect[0][2] < $min_lat) {
                $min_lat = $rect[0][2];
            }
            if ($rect[2][2] > $max_lat) {
                $max_lat = $rect[2][2];
            }
        }
        $mbr = array($min_lat + $shiftlat, $max_lat + $shiftlat, $min_long + $shiftlong, $max_long + $shiftlong);

        $this->centre_lat = ($mbr[1] + $mbr[0])/2;
        $this->centre_long = ($mbr[3] + $mbr[2])/2;

        $this->scale_vert = 0.95 * ($image_height/2) / ($mbr[1] - $this->centre_lat);
        if ((($mbr[3] - $this->centre_long) * $this->scale_vert + $image_width/2) > $image_width) {
            $this->scale_horiz = 0.95 * ($image_width/2) / ($mbr[3] - $this->centre_long);
            $this->scale_vert = $this->scale_horiz;
        } else {
            $this->scale_horiz = $this->scale_vert;
        }

        $this->scale_horiz *= $zoom;
        $this->scale_vert *= $zoom;
    }

    protected function fillDivisions($image, $qryText, $ifColour, $defaultFillColour, $styles = [])
    {
        $tmp_image = imagecreatetruecolor(imagesx($image), imagesy($image));
        $tmp_bgcolour = imagecolorallocate($tmp_image, 255, 254, 253);
        imagefill($tmp_image, 0, 0, $tmp_bgcolour);
        $result = pg_query($this->pgconn, $qryText);
        while ($row = pg_fetch_array($result)) {
            if (isset($row['name']) && isset($styles['electorates'][$row['name']])) {
                $style_name = $styles['electorates'][$row['name']];
                $fillColour = $styles['styles'][$style_name]['fill'];
            } else {
                $fillColour = $defaultFillColour;
            }
            FillPolygons($tmp_image, $fillColour, $tmp_bgcolour, $row['geom'], $this->centre_long, $this->centre_lat, $this->scale_horiz, $this->scale_vert);
        }
        MergeTemporaryImageIfColour($tmp_image, $image, $tmp_bgcolour, $ifColour);
    }

    protected function outlineDivisionsIfNotColour($image, $qryText, $ifNotColour, $outlineColour)
    {
        $tmp_image = imagecreatetruecolor(imagesx($image), imagesy($image));
        $tmp_bgcolour = imagecolorallocate($tmp_image, 255, 254, 253);
        imagefill($tmp_image, 0, 0, $tmp_bgcolour);
        $result = pg_query($this->pgconn, $qryText);
        while ($row = pg_fetch_array($result)) {
            OutlinePolygons($tmp_image, $outlineColour, $row['geom'], $this->centre_long, $this->centre_lat, $this->scale_horiz, $this->scale_vert);
        }
        MergeTemporaryImageIfNotColour($tmp_image, $image, $tmp_bgcolour, $ifNotColour);
    }

    protected function outlineDivisionsIfColour($image, $qryText, $ifColour, $outlineColour)
    {
        $tmp_image = imagecreatetruecolor(imagesx($image), imagesy($image));
        $tmp_bgcolour = imagecolorallocate($tmp_image, 255, 254, 253);
        imagefill($tmp_image, 0, 0, $tmp_bgcolour);
        $result = pg_query($this->pgconn, $qryText);
        while ($row = pg_fetch_array($result)) {
            OutlinePolygons($tmp_image, $outlineColour, $row['geom'], $this->centre_long, $this->centre_lat, $this->scale_horiz, $this->scale_vert);
        }
        MergeTemporaryImageIfColour($tmp_image, $image, $tmp_bgcolour, $ifColour);
    }

    protected function boxAroundDivisions($image, $queryText, $colour, $shiftlat, $shiftlong)
    {
        $image_width = imagesx($image);
        $image_height = imagesy($image);
        $result = pg_query($this->pgconn, $queryText);
        while ($row = pg_fetch_array($result)) {
            $rect1 = GetRectangle($row['mbr']);
            $min_long = $rect1[0][1] + $shiftlong - 0.15;
            $max_long = $rect1[2][1] + $shiftlong + 0.45;
            $min_lat = $rect1[0][2] + $shiftlat - 0.15;
            $max_lat = $rect1[2][2] + $shiftlat + 0.15;
          
            $points = array(
              intval(($min_long - $this->centre_long) * $this->scale_horiz + $image_width/2),
              $image_height - intval(($min_lat - $this->centre_lat) * $this->scale_vert + $image_height/2),

              intval(($max_long - $this->centre_long) * $this->scale_horiz + $image_width/2),
              $image_height - intval(($min_lat - $this->centre_lat) * $this->scale_vert + $image_height/2),

              intval(($max_long - $this->centre_long) * $this->scale_horiz + $image_width/2),
              $image_height - intval(($max_lat - $this->centre_lat) * $this->scale_vert + $image_height/2),

              intval(($min_long - $this->centre_long) * $this->scale_horiz + $image_width/2),
              $image_height - intval(($max_lat - $this->centre_lat) * $this->scale_vert + $image_height/2)
             );
            imagepolygon($image, $points, count($points)/2, $colour);
            $points[0]--;
            $points[1]++;
            $points[2]++;
            $points[3]++;
            $points[4]++;
            $points[5]--;
            $points[6]--;
            $points[7]--;
            imagepolygon($image, $points, count($points)/2, $colour);
            $points[0]--;
            $points[1]++;
            $points[2]++;
            $points[3]++;
            $points[4]++;
            $points[5]--;
            $points[6]--;
            $points[7]--;
            imagepolygon($image, $points, count($points)/2, $colour);
        }
    }

    protected function labelElectorates($image, $qryText, $fontName, $fontSize, $defaultTextColour, $defaultTextShadowColour, $styles = [])
    {
        // using a temporary image only for line drawing purposes
        // to avoid calculating intercept with text rectangle just paint over line
        $tmp_image = imagecreatetruecolor(imagesx($image), imagesy($image));
        $tmp_bgcolour = imagecolorallocate($tmp_image, 255, 0, 0);
        imagefill($tmp_image, 0, 0, $tmp_bgcolour);
        
        $image_width = imagesx($image);
        $image_height = imagesy($image);
        $result = pg_query($this->pgconn, $qryText);
        $fontfile = '/usr/share/fonts/truetype/msttcorefonts/' . $fontName;
        $textToRender = [];
        while ($row = pg_fetch_array($result)) {
            $textColour = $defaultTextColour;
            $textShadowColour = $defaultTextShadowColour;
            $line_from_rel_lat = null;
            $line_from_rel_long = null;
            if (isset($row['name']) && isset($styles['electorates'][$row['name']])) {
                $style_name = $styles['electorates'][$row['name']];
                if (isset($styles['styles'][$style_name]['electoratetextcolour'])) {
                    $textColour = $styles['styles'][$style_name]['electoratetextcolour'];
                }
                if (isset($styles['styles'][$style_name]['electoratetextshadow'])) {
                    $textShadowColour = $styles['styles'][$style_name]['electoratetextshadow'];
                }
            }
            if (isset($row['name']) && isset($styles['electorate_labelling'][$row['name']])) {
                $line_from_rel_lat = $styles['electorate_labelling'][$row['name']]['line_from_rel_lat'];
                $line_from_rel_long = $styles['electorate_labelling'][$row['name']]['line_from_rel_long'];
            }

            $longitude = $row['longitude'];
            $latitude = $row['latitude'];

            if (($line_from_rel_lat !== null) && ($line_from_rel_long !== null)) {
                $line_from_lat = $latitude + $line_from_rel_lat;
                $line_from_long = $longitude + $line_from_rel_long;
                $line_to_lat = $latitude;
                $line_to_long = $longitude;
                $longitude = $line_from_long;
                $latitude = $line_from_lat;
                //
                $line_from_x = intval(($line_from_long - $this->centre_long) * $this->scale_horiz + $image_width/2);
                $line_from_y = $image_height - intval(($line_from_lat - $this->centre_lat) * $this->scale_vert + $image_height/2);
                $line_to_x = intval(($line_to_long - $this->centre_long) * $this->scale_horiz + $image_width/2);
                $line_to_y = $image_height - intval(($line_to_lat - $this->centre_lat) * $this->scale_vert + $image_height/2);
                imageline($tmp_image, $line_from_x, $line_from_y, $line_to_x, $line_to_y, 0);
            }
            
            $label_x = intval(($longitude - $this->centre_long) * $this->scale_horiz + $image_width/2);
            $label_y = $image_height - intval(($latitude - $this->centre_lat) * $this->scale_vert + $image_height/2);
            $labelText = $row['displayname'];
            $bbox = imagettfbbox($fontSize, 0, $fontfile, $labelText);

            $label_x-=($bbox[2] - $bbox[0])/2;
            $label_y-=($bbox[7] - $bbox[1])/2;

            imagefilledrectangle($tmp_image, $label_x, $label_y, $label_x + $bbox[2], $label_y + $bbox[7], $tmp_bgcolour);
            
            $textToRender[] = ['labelText' => $labelText, 'label_x'=>$label_x, 'label_y'=>$label_y, 'textColour'=>$textColour, 'textShadowColour'=>$textShadowColour];
        }
        MergeTemporaryImage($tmp_image, $image, $tmp_bgcolour);
        foreach ($textToRender as $params) {
            imagettftext($image, $fontSize, 0, $params['label_x']-1, $params['label_y']+1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x']-1, $params['label_y'], $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x']-1, $params['label_y']-1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x'], $params['label_y']-1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x']+1, $params['label_y']-1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x']+1, $params['label_y'], $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x']+1, $params['label_y']+1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x'], $params['label_y']+1, $params['textShadowColour'], $fontfile, $params['labelText']);
            imagettftext($image, $fontSize, 0, $params['label_x'], $params['label_y'], $params['textColour'], $fontfile, $params['labelText']);
        }
    }

    protected function renderLegend($image, $legend_x, $legend_y, $styles, $electorates)
    {
        $legend = [];
        foreach ($electorates as $electorate) {
            if (isset($styles['electorates'][$electorate])) {
                $style_name = $styles['electorates'][$electorate];
                $fill_colour = $styles['styles'][$style_name]['fill'];
                $legend[$style_name] = $fill_colour;
            }
        }
        ksort($legend);

        $box_width = 25;
        $box_height = 25;
        $font_size = 12;
        $font_name = 'verdana.ttf';
        $font_file = '/usr/share/fonts/truetype/msttcorefonts/' . $font_name;
        $border_colour = imagecolorallocate($image, 0, 0, 0);
        $text_colour = imagecolorallocate($image, 0, 0, 0);
        foreach ($legend as $label_text => $fill_colour) {
            imagefilledrectangle($image, $legend_x, $legend_y, $legend_x + $box_width, $legend_y + $box_height, $fill_colour);
            imagerectangle($image, $legend_x, $legend_y, $legend_x + $box_width, $legend_y + $box_height, $border_colour);
            $bbox = imagettfbbox($font_size, 0, $font_file, $label_text);
            $label_x = $legend_x + $box_width + 4;
            $label_y = $legend_y + $box_height/2 - ($bbox[7] - $bbox[1])/2;
            imagettftext($image, $font_size, 0, $label_x, $label_y, $text_colour, $font_file, $label_text);
            $legend_y += $box_height + 4;
        }
    }

    protected function labelLocalities($image, $localities, $fontName, $fontSize, $textColour, $textShadowColour)
    {
        $image_width = imagesx($image);
        $image_height = imagesy($image);
        $queryCond = '';
        foreach ($localities as $locality) {
            if ($queryCond != '') {
                $queryCond .= ' or ';
            }
            $queryCond .= " (town_name='" . pg_escape_string($locality[0]) . "' and state='" . pg_escape_string($locality[1]) . "') ";
        }

        $result = pg_query($this->pgconn, "SELECT town_name AS name, lat as latitude, long as longitude FROM all_town_point WHERE $queryCond");
        $ptcolor1 = imagecolorallocate($image, 0, 0, 0);
        $ptcolor2 = imagecolorallocate($image, 255, 255, 255);
        $fontfile = '/usr/share/fonts/truetype/msttcorefonts/' . $fontName;
        while ($row = pg_fetch_array($result)) {
            $longitude = $row['longitude'];
            $latitude = $row['latitude'];
            $x = intval(($longitude - $this->centre_long) * $this->scale_horiz + $image_width/2);
            $y = $image_height - intval(($latitude - $this->centre_lat) * $this->scale_vert + $image_height/2);
            imagefilledellipse($image, $x, $y, 7, 7, $ptcolor1);
            imagefilledellipse($image, $x, $y, 5, 5, $ptcolor2);
            $labelText = $row['name'];
            $bbox = imagettfbbox($fontSize, 0, $fontfile, $labelText);
              
            $x+=5;
            $y-=3;
            
            imagettftext($image, $fontSize, 0, $x-1, $y+1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x-1, $y, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x-1, $y-1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x, $y-1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x+1, $y-1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x+1, $y, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x+1, $y+1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x, $y+1, $textShadowColour, $fontfile, $labelText);
            imagettftext($image, $fontSize, 0, $x, $y, $textColour, $fontfile, $labelText);
        }
    }

    protected function fillStates($image, $landColour, $waterColour, $resolution)
    {
        /*
         *  Fill land area using the ABS state/territory boundaries.
         *  NOTE: The state and territory boundaries and electoral divisions from PSMA do not contain all rivers (eg Perth WA).
         */
        $result = pg_query($this->pgconn, "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM ste_2011_aust");
        while ($row = pg_fetch_array($result)) {
            FillPolygons(
                $image,
                $landColour,
                $waterColour,
                $row['geom'],
                $this->centre_long,
                $this->centre_lat,
                $this->scale_horiz,
                $this->scale_vert
            );
        }
    }

    protected function fillQLDWater($image, $electorate, $waterColour, $resolution)
    {
        /*
         cover
         -------------
         Water
         Ocean
         PNG
         Non-Remnant
         Remnant
         Estuary
         */
        $result = pg_query($this->pgconn, "SELECT ST_AsText(ST_Simplify(rvc13_v9_0_qld.geom,$resolution)) as geom
          FROM rvc13_v9_0_qld, qld_state_electoral elb
          WHERE ST_Intersects(rvc13_v9_0_qld.geom, elb.geom) and
            rvc13_v9_0_qld.cover in ('Estuary','Water') and adminarean in (" . ArrayToQuoteCommaList($electorate) . ")");
        while ($row = pg_fetch_array($result)) {
            FillPolygons(
                $image,
                $waterColour,
                0,
                $row['geom'],
                $this->centre_long,
                $this->centre_lat,
                $this->scale_horiz,
                $this->scale_vert
            );
        }
    }

    public function renderFederal(
        $resolution,
        $render_image_width,
        $render_image_height,
        $final_width,
        $final_height,
        $localityFontSize,
        $localityFontName,
        $electorateFontSize,
        $electorateFontName,
        $electorateFontSize2,
        $electorateFontName2,
        float $zoom,
        float $shiftlat,
        float $shiftlong,
        bool $boxAroundHi,
        array $electorateLabels,
        array $electorateLabels2,
        array $localities,
        array $mbrDivisions,
        array $electorate,
        array $electoratealt,
        array $electoratehi,
        string $filename,
        array $styles = [],
        int $legend_x = null,
        int $legend_y = null
    ) {
        // prepare some queries
        $qMBR = "SELECT ST_AsText(ST_Envelope(ST_Collect(geom))) as mbr 
            FROM comm_electoral WHERE elect_div in (" . ArrayToQuoteCommaList($mbrDivisions) . ")";
        $qMBRByDiv = "SELECT ST_AsText(ST_Envelope(ST_Collect(geom))) as mbr, elect_div FROM comm_electoral
            WHERE elect_div in (" . ArrayToQuoteCommaList($electoratehi) . ") GROUP BY elect_div";
        $qElectorates = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom, elect_div as name
            FROM comm_electoral WHERE elect_div in (" . ArrayToQuoteCommaList($electorate) . ")";
        $qElectoratesAlt = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM comm_electoral
            WHERE elect_div in (" . ArrayToQuoteCommaList($electoratealt) . ")";
        $qElectoratesHi = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM comm_electoral
            WHERE elect_div in (" . ArrayToQuoteCommaList($electoratehi) . ")";
        $qLabelElectorates = "SELECT label_latitude as latitude, label_longitude as longitude, elect_div as name, elect_div_displayname as displayname 
            FROM comm_electoral_labels WHERE elect_div in (" . ArrayToQuoteCommaList($electorateLabels) . ")";
        $qLabelElectorates2 = "SELECT label_latitude as latitude, label_longitude as longitude, elect_div as name, elect_div_displayname as displayname 
            FROM comm_electoral_labels WHERE elect_div in (" . ArrayToQuoteCommaList($electorateLabels2) . ")";

        $this->determineDimensions($qMBR, $zoom, $shiftlat, $shiftlong, $render_image_width, $render_image_height);

        // create main image
        $image = imagecreatetruecolor($render_image_width, $render_image_height);

        // allocate some colours
        $waterColour = imagecolorallocate($image, 198, 236, 255);
        $outlineLandColour = imagecolorallocate($image, 0, 0, 0);
        $outlineElectorateColour = imagecolorallocate($image, 0, 0, 0);
        $boxAroundHiColour = imagecolorallocate($image, 0, 0, 0);
        $landColour = imagecolorallocate($image, 255, 254, 233);
        $electorateColourDefault = imagecolorallocate($image, 246, 225, 185);
        $electorateHiColour = imagecolorallocate($image, 0, 128, 0);
        $electorateAltColour = imagecolorallocate($image, 255, 220, 145);
        $outlineElectorateAltColour = imagecolorallocate($image, 255, 255, 255);
        $electorateTextColour = imagecolorallocate($image, 46, 47, 57);
        $electorateTextShadowColour = imagecolorallocate($image, 255, 255, 255);
        $electorateTextColour2 = imagecolorallocate($image, 46, 47, 57);
        $electorateTextShadowColour2 = imagecolorallocate($image, 255, 255, 255);
        $localityTextColour = imagecolorallocate($image, 255, 255, 255);
        $localityTextShadowColour = imagecolorallocate($image, 46, 47, 57);

        $styles_alloc = [];
        if ($styles) {
            foreach ($styles['styles'] as $style_name => $style) {
                if (isset($style['fill'])) {
                    $styles_alloc['styles'][$style_name]['fill'] = imagecolorallocate(
                        $image,
                        hexdec(substr($style['fill'], 0, 2)),
                        hexdec(substr($style['fill'], 2, 2)),
                        hexdec(substr($style['fill'], 4, 2))
                    );
                }
                if (isset($style['electoratetextshadow'])) {
                    $styles_alloc['styles'][$style_name]['electoratetextshadow'] = imagecolorallocate(
                        $image,
                        hexdec(substr($style['electoratetextshadow'], 0, 2)),
                        hexdec(substr($style['electoratetextshadow'], 2, 2)),
                        hexdec(substr($style['electoratetextshadow'], 4, 2))
                    );
                }
                if (isset($style['electoratetextcolour'])) {
                    $styles_alloc['styles'][$style_name]['electoratetextcolour'] = imagecolorallocate(
                        $image,
                        hexdec(substr($style['electoratetextcolour'], 0, 2)),
                        hexdec(substr($style['electoratetextcolour'], 2, 2)),
                        hexdec(substr($style['electoratetextcolour'], 4, 2))
                    );
                }
            }
            $styles_alloc['electorates'] = $styles['electorates'];
            $styles_alloc['electorate_labelling'] = $styles['electorate_labelling'];
        }

        // first fill entire image with water colour
        imagefill($image, 0, 0, $waterColour);
        
        // Fill land
        $this->fillStates($image, $landColour, $waterColour, $resolution);
        
        // Outline land but without drawing state boundaries; do this by scanning entire pixel area for water color
        scanOutline($image, $waterColour, $outlineLandColour);
        
        if ($electoratealt) {
            $this->fillDivisions($image, $qElectoratesAlt, $landColour, $electorateAltColour);
            $this->outlineDivisionsIfColour($image, $qElectoratesAlt, $electorateAltColour, $outlineElectorateAltColour);
        }

        if ($electorate) {
            $this->fillDivisions($image, $qElectorates, $landColour, $electorateColourDefault, $styles_alloc);
        }

        if ($electoratehi) {
            $this->fillDivisions($image, $qElectoratesHi, $landColour, $electorateHiColour); // Paint over land
            $this->fillDivisions($image, $qElectoratesHi, $electorateColourDefault, $electorateHiColour); // Paint over electorate
        }

        if ($electorate) {
            $this->outlineDivisionsIfNotColour($image, $qElectorates, $waterColour, $outlineElectorateColour);
        }

        if ($boxAroundHi && $electoratehi) {
            $this->boxAroundDivisions($image, $qMBRByDiv, $boxAroundHiColour, $shiftlat, $shiftlong);
        }

        // Re-scale to final image size
        $final_image = imagecreatetruecolor($final_width, $final_height);
        imagecopyresampled($final_image, $image, 0, 0, 0, 0, $final_width, $final_height, $render_image_width, $render_image_height);
        $this->scale_horiz = $this->scale_horiz * $final_width / $render_image_width;
        $this->scale_vert = $this->scale_vert * $final_height / $render_image_height;

        if ($electorateLabels) {
            $this->labelElectorates(
                $final_image,
                $qLabelElectorates,
                $electorateFontName,
                $electorateFontSize,
                $electorateTextColour,
                $electorateTextShadowColour,
                $styles_alloc
            );
        }

        if ($electorateLabels2) {
            $this->labelElectorates(
                $final_image,
                $qLabelElectorates2,
                $electorateFontName2,
                $electorateFontSize2,
                $electorateTextColour2,
                $electorateTextShadowColour2
            );
        }
                  
        if ($localities) {
            $this->labelLocalities(
                $final_image,
                $localities,
                $localityFontName,
                $localityFontSize,
                $localityTextColour,
                $localityTextShadowColour
            );
        }

        if (($legend_x !== null) && ($legend_y !== null)) {
            $this->renderLegend($final_image, $legend_x, $legend_y, $styles_alloc, $electorate);
        }

        imagepng($final_image, $filename);
    }

    public function renderQLDState(
        $resolution,
        $render_image_width,
        $render_image_height,
        $final_width,
        $final_height,
        $localityFontSize,
        $localityFontName,
        $electorateFontSize,
        $electorateFontName,
        $electorateFontSize2,
        $electorateFontName2,
        $zoom,
        $shiftlat,
        $shiftlong,
        $boxAroundHi,
        $electorateLabels,
        $electorateLabels2,
        $localities,
        $mbrDivisions,
        $electorate,
        $electoratealt,
        $electoratehi,
        $filename
    ) {
        // prepare some queries
        $qMBR = "SELECT ST_AsText(ST_Envelope(ST_Collect(geom))) as mbr FROM qld_state_electoral
            WHERE adminarean in (" . ArrayToQuoteCommaList($mbrDivisions) . ")";
        $qMBRByDiv = "SELECT ST_AsText(ST_Envelope(ST_Collect(geom))) as mbr FROM qld_state_electoral
            WHERE adminarean in (" . ArrayToQuoteCommaList($mbrDivisions) . ") GROUP BY adminarean";
        $qElectorates = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM qld_state_electoral
            WHERE adminarean in (" . ArrayToQuoteCommaList($electorate) . ")";
        $qElectoratesAlt = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM qld_state_electoral
            WHERE adminarean in (" . ArrayToQuoteCommaList($electoratealt) . ")";
        $qElectoratesHi = "SELECT ST_AsText(ST_Simplify(geom,$resolution)) as geom FROM qld_state_electoral
            WHERE adminarean in (" . ArrayToQuoteCommaList($electoratehi) . ")";
        $qLabelElectorates = "SELECT label_latitude as latitude, label_longitude as longitude, adminarean as name, adminarean_displayname as displayname 
            FROM qld_state_electoral_labels WHERE adminarean in (" . ArrayToQuoteCommaList($electorateLabels) . ")";
        $qLabelElectorates2 = "SELECT label_latitude as latitude, label_longitude as longitude, adminarean as name, adminarean_displayname as displayname 
            FROM qld_state_electoral_labels WHERE adminarean in (" . ArrayToQuoteCommaList($electorateLabels2) . ")";

        $this->determineDimensions($qMBR, $zoom, $shiftlat, $shiftlong, $render_image_width, $render_image_height);

        // create main image
        $image = imagecreatetruecolor($render_image_width, $render_image_height);

        // allocate some colours
        $waterColour = imagecolorallocate($image, 198, 236, 255);
        $outlineLandColour = imagecolorallocate($image, 0, 0, 0);
        $outlineElectorateColour = imagecolorallocate($image, 0, 0, 0);
        $boxAroundHiColour = imagecolorallocate($image, 0, 0, 0);
        $landColour = imagecolorallocate($image, 255, 254, 233);
        $electorateColour = imagecolorallocate($image, 246, 225, 185);
        $electorateHiColour = imagecolorallocate($image, 0, 128, 0);
        $electorateAltColour = imagecolorallocate($image, 255, 220, 145);
        $outlineElectorateAltColour = imagecolorallocate($image, 255, 255, 255);
        $electorateTextColour = imagecolorallocate($image, 46, 47, 57);
        $electorateTextShadowColour = imagecolorallocate($image, 255, 255, 255);
        $electorateTextColour2 = imagecolorallocate($image, 46, 47, 57);
        $electorateTextShadowColour2 = imagecolorallocate($image, 255, 255, 255);
        $localityTextColour = imagecolorallocate($image, 255, 255, 255);
        $localityTextShadowColour = imagecolorallocate($image, 46, 47, 57);

        // first fill entire image with water colour
        imagefill($image, 0, 0, $waterColour);

        // Fill land
        $this->fillStates($image, $landColour, $waterColour, $resolution);
        
        $this->fillQLDWater($image, $electorate, $waterColour, $resolution);

        // Outline land but without drawing state boundaries; do this by scanning entire pixel area for water color
        scanOutline($image, $waterColour, $outlineLandColour);
        
        if ($electoratealt) {
            $this->fillDivisions($image, $qElectoratesAlt, $landColour, $electorateAltColour);
            $this->outlineDivisionsIfColour($image, $qElectoratesAlt, $electorateAltColour, $outlineElectorateAltColour);
        }

        if ($electorate) {
            $this->fillDivisions($image, $qElectorates, $landColour, $electorateColour);
        }

        if ($electoratehi) {
            $this->fillDivisions($image, $qElectoratesHi, $landColour, $electorateHiColour); // Paint over land
            $this->fillDivisions($image, $qElectoratesHi, $electorateColour, $electorateHiColour); // Paint over electorate
        }

        if ($electorate) {
            $this->outlineDivisionsIfNotColour($image, $qElectorates, $waterColour, $outlineElectorateColour);
        }

        if ($boxAroundHi && $electoratehi) {
            $this->boxAroundDivisions($image, $qMBRByDiv, $boxAroundHiColour, $shiftlat, $shiftlong);
        }

        // Re-scale to final image size
        $final_image = imagecreatetruecolor($final_width, $final_height);
        imagecopyresampled($final_image, $image, 0, 0, 0, 0, $final_width, $final_height, $render_image_width, $render_image_height);
        $this->scale_horiz = $this->scale_horiz * $final_width / $render_image_width;
        $this->scale_vert = $this->scale_vert * $final_height / $render_image_height;

        if ($electorateLabels) {
            $this->labelElectorates($final_image, $qLabelElectorates, $electorateFontName, $electorateFontSize, $electorateTextColour, $electorateTextShadowColour);
        }

        if ($electorateLabels2) {
            $this->labelElectorates($final_image, $qLabelElectorates2, $electorateFontName2, $electorateFontSize2, $electorateTextColour2, $electorateTextShadowColour2);
        }
          
        if ($localities) {
            $this->labelLocalities($final_image, $localities, $localityFontName, $localityFontSize, $localityTextColour, $localityTextShadowColour);
        }

        imagepng($final_image, $filename);
    }
}

foreach ($argv as $idx => $arg) {
    if ($idx > 0) {
        preg_match('/(.*?)=(.*)/', $arg, $matches);
        $args[$matches[1]] = $matches[2];
    }
}

if (isset($args['electorate'])) {
    $electorate = explode(',', $args['electorate']);
} else {
    $electorate = [];
}

if (isset($args['electoratehi'])) {
    $electoratehi = explode(',', $args['electoratehi']);
} else {
    $electoratehi = [];
}
  
if (isset($args['electoratealt'])) {
    $electoratealt = explode(',', $args['electoratealt']);
} else {
    $electoratealt = [];
}

if (isset($args['electoratembr']) && ($args['electoratembr'] != '')) {
    $mbr = explode(',', $args['electoratembr']);
} else {
    $mbr = explode(',', $args['electorate']);
}

if (isset($args['resolution'])) {
    $resolution = floatval($args['resolution']);
} else {
    $resolution = 0.0001;
}

if (isset($args['width'])) {
    $image_width = $args['width'];
} else {
    $image_width = 800;
}

if (isset($args['height'])) {
    $image_height = $args['height'];
} else {
    $image_height = 600;
}

if (isset($args['final_width'])) {
    $final_width = $args['final_width'];
} else {
    $final_width = 800;
}

if (isset($args['final_height'])) {
    $final_height = $args['final_height'];
} else {
    $final_height = 947;
}

$localityFontSize = 8;
$electorateFontSize = 8;
$electorateFontSize2 = 8;
$localityFontName = 'verdana.ttf';
$electorateFontName = 'verdana.ttf';
$electorateFontName2 = 'verdana.ttf';
if (isset($args['label_font_size'])) {
    $localityFontSize = $args['label_font_size'];
    $electorateFontSize = $args['label_font_size'];
}
if (isset($args['locality_font_size'])) {
    $localityFontSize = $args['locality_font_size'];
}
if (isset($args['electorate_font_size'])) {
    $electorateFontSize = $args['electorate_font_size'];
}
if (isset($args['electorate_font_size2'])) {
    $electorateFontSize2 = $args['electorate_font_size2'];
}
if (isset($args['locality_font_name'])) {
    $localityFontName = $args['locality_font_name'];
}
if (isset($args['electorate_font_name'])) {
    $electorateFontName = $args['electorate_font_name'];
}
if (isset($args['electorate_font_name2'])) {
    $electorateFontName2 = $args['electorate_font_name2'];
}

if (isset($args['z'])) {
    $zoom = floatval($args['z']);
} else {
    $zoom = 1.0;
}

if (isset($args['legend_x'])) {
    $legend_x = intval($args['legend_x']);
} else {
    $legend_x = null;
}
 
if (isset($args['legend_y'])) {
    $legend_y = intval($args['legend_y']);
} else {
    $legend_y = null;
}

if (isset($args['shiftlat'])) {
    $shiftlat = floatval($args['shiftlat']);
} else {
    $shiftlat = 0.0;
}
  
if (isset($args['shiftlong'])) {
    $shiftlong = floatval($args['shiftlong']);
} else {
    $shiftlong = 0.0;
}
  
if (isset($args['boxaroundhi'])) {
    $boxAroundHi = ($args['boxaroundhi'] == 'true');
} else {
    $boxAroundHi = false;
}
  
if (isset($args['electoratelabels'])) {
    $electorateLabels = explode(',', $args['electoratelabels']);
} else {
    $electorateLabels = [];
}

if (isset($args['electoratelabels2'])) {
    $electorateLabels2 = explode(',', $args['electoratelabels2']);
} else {
    $electorateLabels2 = [];
}
  
if (isset($args['localities'])) {
    $tmpLocalities = explode(',', $args['localities']);
    $localities = [];
    foreach ($tmpLocalities as $locality) {
        if (preg_match('/([^\(]+)\(([^\)]+)\)/', $locality, $matchLocalityParts)) {
            $localities[] = [$matchLocalityParts[1], $matchLocalityParts[2]];
        } else {
            die("Could not parse localities");
        }
    }
} else {
    $localities = [];
}

if (isset($args['type'])) {
    $type = $args['type'];
} else {
    $type = 'federal';
}

if (isset($args['filename'])) {
    $filename = $args['filename'];
} else {
    $filename = 'render.png';
}

if (isset($args['stylefile'])) {
    $styleFileContents = file_get_contents($args['stylefile']);
    $styles = json_decode($styleFileContents, true);
} else {
    $styles = [];
}
/////////////

/*echo 
  "resolution=$resolution\n" .
  "image_width=$image_width\n" .
  "image_height=$image_height\n" .
  "final_width=$final_width\n" .
  "final_height=$final_height\n" .
  "zoom=$zoom\n" .
  "shiftlat=$shiftlat\n" .
  "shiftlong=$shiftlong\n" .
  "boxAroundHi=$boxAroundHi\n" .
  "electorateLabels=$electorateLabels\n" .
  "localities=" . print_r($localities, true) . "\n" .
  "mbr=$mbr\n" .
  "electorate=$electorate\n" .
  "electoratealt=$electoratealt\n" .
  "electoratehi=$electoratehi\n" .
  "filename=$filename\n";
*/

echo "Rendering $filename\n";
/////////////

$renderer = new MapRenderer();

if ($type == 'federal') {
    $renderer->renderFederal(
        $resolution,
        $image_width,
        $image_height,
        $final_width,
        $final_height,
        $localityFontSize,
        $localityFontName,
        $electorateFontSize,
        $electorateFontName,
        $electorateFontSize2,
        $electorateFontName2,
        $zoom,
        $shiftlat,
        $shiftlong,
        $boxAroundHi,
        $electorateLabels,
        $electorateLabels2,
        $localities,
        $mbr,
        $electorate,
        $electoratealt,
        $electoratehi,
        $filename,
        $styles,
        $legend_x,
        $legend_y
    );
} elseif ($type == 'qld_state') {
    $renderer->renderQLDState(
        $resolution,
        $image_width,
        $image_height,
        $final_width,
        $final_height,
        $localityFontSize,
        $localityFontName,
        $electorateFontSize,
        $electorateFontName,
        $electorateFontSize2,
        $electorateFontName2,
        $zoom,
        $shiftlat,
        $shiftlong,
        $boxAroundHi,
        $electorateLabels,
        $electorateLabels2,
        $localities,
        $mbr,
        $electorate,
        $electoratealt,
        $electoratehi,
        $filename
    );
}
