<?php

function fd_name_fix($rawName) {
	switch ($rawName) {
		case 'Brad Beal':
			return 'Bradley Beal';

		case 'Jakarr Sampson':
			return 'JaKarr Sampson';

		case 'Luc Richard Mbah a Moute':
			return 'Luc Mbah a Moute';
		
		default:
			return $rawName;
	}
}