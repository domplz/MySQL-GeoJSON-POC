<?php


namespace App\Services;

use App\Models\GeoJSONModel;
use InvalidArgumentException;
use PDO;

class GeoJSONService
{
    function __construct()
    {

    }

    public function getLocationsGeoJsonArray(): array
    {
        $jsonString = file_get_contents('assets/gemeinden_95_geo.json');

        $decodedJson = json_decode($jsonString);
        $geoJsonArray = [];

        if ($decodedJson && $decodedJson->type === 'FeatureCollection' && isset($decodedJson->features)) {
            foreach ($decodedJson->features as $feature) {
                $geoJsonModel = new GeoJSONModel();
                $geoJsonModel->name = $feature->properties->name;
                $geoJsonModel->iso = $feature->properties->iso;
                $geoJsonModel->type = $feature->type;
                $geoJsonModel->properties = $feature->properties;
                $geoJsonModel->geometry = $feature->geometry;
                $geoJsonArray[] = $geoJsonModel;
            }
        }

        return $geoJsonArray;
    }

    public function getLocationJsonForIso(string $iso): GeoJSONModel|null {
        $locations = $this->getLocationsGeoJsonArray();

        $elementsForIso = array_filter($locations, function ($geoJsonModel) use ($iso) {
            return isset($geoJsonModel->properties->iso) && $geoJsonModel->properties->iso === $iso;
        });

        if(!empty($elementsForIso)) {
            return array_values($elementsForIso)[0];
        }

        return null;
    }

    public function combineGeoJsonAreas(array $geoJsons): GeoJSONModel {

        $combinedGeoJson = $geoJsons[0];
        for ($i = 0; $i < count($geoJsons) - 1; $i++) {
            $combinedGeoJson = $this->combineGeoJsons($combinedGeoJson, $geoJsons[$i + 1]);
        }

        return $combinedGeoJson;
    }

    public function combineGeoJsons(GeoJSONModel $geoJson1, GeoJSONModel $geoJson2): GeoJSONModel
    {
        $pdo = new PDO("mysql:host=db;dbname=db", "db", "db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
        SELECT ST_AsGeoJSON(
            ST_Union(
                ST_GeomFromGeoJSON(:geojson1),
                ST_GeomFromGeoJSON(:geojson2)
            )
        ) AS combined_geojson;
    ";

        $stmt = $pdo->prepare($sql);
        $geoJson1String = json_encode($geoJson1->geometry);
        $geoJson2String = json_encode($geoJson2->geometry);
        $stmt->bindParam(':geojson1', $geoJson1String);;
        $stmt->bindParam(':geojson2', $geoJson2String);
        $stmt->execute();


        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $combinedModel = new GeoJSONModel();
        $combinedModel->name = $geoJson1->name . ' + ' . $geoJson2->name;
        $combinedModel->iso = $geoJson1->iso . ' + ' . $geoJson2->iso;
        $combinedModel->geometry = json_decode($result['combined_geojson']);

        return $combinedModel;
    }
}