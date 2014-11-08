<?php

function setActive($path, $active = 'active') {
	return Request::is($path) ? $active : '';
}

function ddAll($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';

	exit();
}





