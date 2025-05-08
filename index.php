<?php


require 'vendor/autoload.php';

use App\Services\GeoJSONService;


$isCalculation = false;

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['towns'] ?? '')) {
    $isCalculation = true;

    $geoJsonService = new GeoJSONService();
    $geoJsonArray = $geoJsonService->getLocationsGeoJsonArray();

    $isoCodes = explode(',', $_GET['towns']);

    $townGeoJsons = [];
    foreach ($isoCodes as $isoCode) {
        $isoCodeInt = (int)$isoCode;
        if ($isoCodeInt > 0) {
            $townGeoJsons[] = $geoJsonService->getLocationJsonForIso($isoCode);
        }
    }

    $result = $geoJsonService->combineGeoJsonsArray($townGeoJsons);

    if (!empty($_GET['towns2'] ?? '')) {
        $isoCodesTowns2 = explode(',', $_GET['towns2']);

        $townGeoJsonsTowns2 = [];
        foreach ($isoCodesTowns2 as $isoCode) {
            $isoCodeInt = (int)$isoCode;
            if ($isoCodeInt > 0) {
                $townGeoJsonsTowns2[] = $geoJsonService->getLocationJsonForIso($isoCode);
            }
        }

        $resultTowns2 = $geoJsonService->combineGeoJsonsArray($townGeoJsonsTowns2);

        $overlap = $geoJsonService->getOverlappingAreaGeoJson($result, $resultTowns2);
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GeoJSON POC</title>
    <script>
        function copyPreTagContent(selector) {
            const pre = document.querySelector(selector);
            const text = pre.innerText;

            navigator.clipboard.writeText(text)
                .then(() => alert('Copied to clipboard!'))
                .catch(err => alert('Failed to copy: ' + err));
        }
    </script>
</head>
<body>
<div>
    <h1>GeoJSON POC</h1>
    <div>
        View results with
        <a href="https://geojson.io/">geojson.io</a>
    </div>
    <form action="" method="get">
        <h2>Cobine towns</h2>
        <label for="towns">
            Town ISO codes (comma separated (,)): <br>
            <input type="text" id="towns" name="towns" value="31528,31541,31552,31511,30521"
                   placeholder="Input town ISO codes (comma separated (,))">
        </label>
        <br>

        <h2>OPTIONAL</h2>
        <h2>Intersect with towns</h2>
        <label for="towns2">
            Town ISO codes (comma separated (,)): <br>
            <input type="text" id="towns2" name="towns2" value="30521,30503"
                   placeholder="Input town ISO codes (comma separated (,))">
        </label>

        <br>
        <input type="submit" value="Submit">

    </form>

    <?php if ($isCalculation && !empty($result)) { ?>

        <h2>Result Towns 1</h2>
        <button onclick="copyPreTagContent('#townsResults')">Copy to Clipboard</button>
        <pre id="townsResults">
                <?php
                echo json_encode($result->geometry);
                ?>
            </pre>

    <?php } ?>


    <?php if ($isCalculation && !empty($resultTowns2)) { ?>

        <h2>Result Towns 2</h2>
        <button onclick="copyPreTagContent('#townsResults2')">Copy to Clipboard</button>
        <pre id="townsResults2">
                <?php
                echo json_encode($resultTowns2->geometry);
                ?>
            </pre>

    <?php } ?>


    <?php if ($isCalculation && !empty($overlap)) { ?>
        <h2>Overlapping Area</h2>
        <button onclick="copyPreTagContent('#overlap')">Copy to Clipboard</button>
        <pre id="overlap">
                <?php
                echo json_encode($overlap->geometry);
                ?>
            </pre>

    <?php } ?>
</div>
</body>
</html>