<?php

function fgNameFix($rawName) {
	switch ($rawName) {
		case 'T.J. House':
			return 'TJ House';

		case 'Daniel Dorn':
			return 'Danny Dorn';

		case 'Rubby de la Rosa':
			return 'Rubby De La Rosa';

		case 'Jorge de la Rosa':
			return 'Jorge De La Rosa';

		case 'Mitchell Harris':
			return 'Mitch Harris';

		case 'Steven Souza';
			return 'Steven Souza Jr.';
		
		default:
			return $rawName;
	}
}

function fd_name_fix($rawName) {
	switch ($rawName) {
		case 'Brad Beal':
			return 'Bradley Beal';

		case 'Jakarr Sampson':
			return 'JaKarr Sampson';

		case 'Luc Richard Mbah a Moute':
			return 'Luc Mbah a Moute';

		case 'Dennis Schroeder':
			return 'Dennis Schroder';

		case 'Dennis Schroder':
			return 'Dennis Schroder';

		case 'Tim Hardaway Jr.':
			return 'Tim Hardaway';

		case 'Perry Jones III':
			return 'Perry Jones';

		case 'Ronald Roberts, Jr.':
			return 'Ronald Roberts';

		case 'Jose Juan Barea':
			return 'Jose Barea';
		
		default:
			return $rawName;
	}
}