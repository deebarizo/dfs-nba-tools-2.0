<?php

function numFormat($number, $decimalPlaces = 2) {
	$number = number_format(round($number, $decimalPlaces), $decimalPlaces);

	return $number;
}

function setActive($path, $active = 'active') {
	return Request::is($path) ? $active : '';
}

function ddAll($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';

	exit();
}

function prf($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

function urlToUpper($string) {
	$string = strtoupper($string);

	$string = preg_replace('/-/', ' ', $string);

	return $string;
}






