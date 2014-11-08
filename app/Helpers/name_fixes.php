<?php

function fd_name_fix($rawName) {
	switch ($rawName) {
		case 'Brad Beal':
			return 'Bradley Beal';
		
		default:
			return $rawName;
	}
}