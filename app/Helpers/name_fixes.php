<?php

function fd_name_fix($rawName) {
	switch ($rawName) {
		case 'Brad Beal':
			return 'Bradley Beal';

		case 'Jakarr Sampson':
			return 'JaKarr Sampson';

		case 'Luc Richard Mbah a Moute':
			return 'Luc Mbah a Moute';

		case 'Dennis Schroeder':
			return 'Dennis Schröder';

		case 'Tim Hardaway Jr.':
			return 'Tim Hardaway';

		case 'Perry Jones III':
			return 'Perry Jones';
		
		default:
			return $rawName;
	}
}