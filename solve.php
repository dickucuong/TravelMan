<?php
// 'cities.txt' => input cities file
// 1 => select first city in the list (Beijing) as beginning city of the tour
processFindingBestRoute('cities.txt', 1);

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param string $filePath
 * @param int $startOrder
 * 
 * @description process to find the best route from begining city ($startOrder is the order (row number) of cities in cities.txt)
 */
function processFindingBestRoute($filePath, $startOrder = 1) {
	$cityArr = getCitiesFromFile($filePath);
	$totalCities = count($cityArr);
	if ($startOrder < 1 || $startOrder > $totalCities) {
		throw new Exception('Start Order should be from 1 to ' . $totalCities );
	}
	$startOrder--;
	$routeOfCurrentCity = [];
	$distanceValues = [];
	$possibleRoutes = [];
	for ($i = 0; $i < $totalCities; $i++) {
		$lasCityOfRoute = [];
		$routeOfCurrentCity[0] = [$cityArr[$i][0]];
		$routeOfCurrentCity[1] = 0;
		$routeOfCurrentCity[2] = [0];
		$inputCities = $cityArr;
		$currentCity = $inputCities[$i];
		unset($inputCities[$i]);
		getShortestDistance($currentCity,
							$inputCities,
							$distanceValues,
							$routeOfCurrentCity,
							$lasCityOfRoute);
		// get distance from the last city of the current route to the beginning city
		$newCurrentOne = [];
		$newCurrentOne[] = $currentCity;
		getShortestDistance(current($lasCityOfRoute),
							$newCurrentOne,
							$distanceValues,
							$routeOfCurrentCity,
							$lasCityOfRoute);
		$possibleRoutes[] = $routeOfCurrentCity;
	}
	printFinalRoute($cityArr[$startOrder][0], $possibleRoutes);
}

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param string $filePath
 * 
 * @return arrays return the array of cities from path file
 */
function getCitiesFromFile($filePath) {
	if (file_exists($filePath) === true) {
		$citiesData = explode("\n", file_get_contents($filePath));
		$total = count($citiesData);
		$refinedArr = [];
		for ($i = 0; $i < $total; $i++) {
			$cData = explode("\t", $citiesData[$i]);
			$refinedArr[] = $cData;
		}
		return $refinedArr;
	}
	return [];
}

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param array $currentCity
 * @param array $remainingCities
 * @param array $distanceValues
 * @param array $routeOfCurrentCity
 * @param array $lasCityOfRoute
 * @return arrays the shortest distance from current city to remaining ones (which have not reached yet).
 */
function getShortestDistance($currentCity,
								&$remainingCities,
								&$distanceValues,
								&$routeOfCurrentCity,
								&$lasCityOfRoute) {
	if (count($remainingCities) === 0) return;
	reset($remainingCities);
	$firstCityInArr = current($remainingCities);
	if (count($remainingCities) === 1) {
		$lasCityOfRoute[] = $firstCityInArr;
	}
	if (isset($distanceValues[$currentCity[0] . $firstCityInArr[0]]) === true) {
		$distance = $distanceValues[$currentCity[0] . $firstCityInArr[0]];
	} else {
		$distance = getDistanceFromGPSs($currentCity[1], $currentCity[2], $firstCityInArr[1], $firstCityInArr[2]);
		$distanceValues[$currentCity[0] . $firstCityInArr[0]] = $distance;
		$distanceValues[$firstCityInArr[0] . $currentCity[0]] = $distance;
	}
	$removedKey = -1;
	foreach ($remainingCities as $key => $value) {
		$tDistance = getDistanceFromGPSs($currentCity[1], $currentCity[2], $value[1], $value[2]);
		$distanceValues[$currentCity[0] . $value[0]] = $tDistance;
		$distanceValues[$value[0] . $currentCity[0]] = $tDistance;
		if ($tDistance <= $distance) {
			$distance = $tDistance;
			$firstCityInArr = $value;
			$removedKey = $key;
		}
	}
	unset($remainingCities[$removedKey]);
	$routeOfCurrentCity[0][] = $firstCityInArr[0];
	$routeOfCurrentCity[1] += $distance;
	$routeOfCurrentCity[2][] = $distance;
	return getShortestDistance($firstCityInArr,
								$remainingCities,
								$distanceValues,
								$routeOfCurrentCity,
								$lasCityOfRoute);
}

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param float $latitudeFrom
 * @param float $longitudeFrom
 * @param float $latitudeTo
 * @param float $longitudeTo
 * @param float $earthRadius
 * 
 * @return float the distance between 2 cities based on their GPSs
 */
function getDistanceFromGPSs($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371) {
	$latFrom = deg2rad(floatval($latitudeFrom));
	$lonFrom = deg2rad(floatval($longitudeFrom));
	$latTo = deg2rad(floatval($latitudeTo));
	$lonTo = deg2rad(floatval($longitudeTo));
	$lonDelta = $lonTo - $lonFrom;
	$deltaA = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
	$deltaB = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
	$angle = atan2(sqrt($deltaA), $deltaB);
	return round($angle * $earthRadius, 2);
}

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param string $fromCity
 * @param array $possibleRoutes
 * 
 * @return array the array of the best route from possile routes
 */
function getBestRoute ($fromCity, $possibleRoutes) {
	$shortestDistance = -1;
	$bestRoute = null;
	foreach ($possibleRoutes as $route) {
		$cRoutes = $route[0];
		$dRoutes = $route[2];
		$leng = count($cRoutes);
		$breakPos = -1;
		for ($i = 1; $i < $leng; $i++) {
			if ($cRoutes[$i] === $fromCity) {
				$breakPos = $i;
				break;
			}
		}
		$cDistance = $route[1] - $dRoutes[$breakPos];
		if ($shortestDistance < 0 || $cDistance < $shortestDistance) {
			$shortestDistance = $cDistance;
			$bestRoute = $route;
		}
	}
	return array($bestRoute, $shortestDistance);
}

/**
 * @author Cuong Le <lecaocuongspkt@gmail.com>
 *
 * @param string $fromCity
 * @param array $possibleRoutes
 */
function printFinalRoute($fromCity, $possibleRoutes) {
	$bestRoute = getBestRoute($fromCity, $possibleRoutes);
	$newLineChar = "\n";
	$cRoutes = $bestRoute[0][0];
	array_shift($cRoutes);
	$leng = count($cRoutes);
	$breakPos = -1;
	for ($i = 1; $i < $leng; $i++) {
		if ($cRoutes[$i] === $fromCity) {
			$breakPos = $i;
			break;
		}
	}
	$nOutput = array_merge(array_slice($cRoutes, $breakPos), array_slice($cRoutes, 0, $breakPos));
	for ($i = 0; $i < $leng; $i++) {
		echo $nOutput[$i] . $newLineChar;
	}
	echo $newLineChar. 'Total distance: ' . $bestRoute[1] . ' (km) ' . $newLineChar;
}