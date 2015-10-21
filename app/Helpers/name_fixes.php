<?php

/****************************************************************************************
FD NBA NAME FIX
****************************************************************************************/

function fd_name_fix($rawName) {
	switch ($rawName) {
		case 'Bryce Jones':
			return 'Bryce Dejean-Jones';

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


/****************************************************************************************
FANGRAPHS MLB NAME FIX
****************************************************************************************/

function fgNameFix($fgName, $positionType) {
	switch ($fgName) {
		case 'Ivan De Jesus':
			return 'Ivan De Jesus Jr.';

		case 'Melvin Upton':
			return 'Melvin Upton Jr.';

		case 'Michael Montgomery':
			return 'Mike Montgomery';

		case 'Joshua Ravin':
			return 'Josh Ravin';

		case 'Zachary Heathcott':
			return 'Slade Heathcott';

		case 'Edward Easley':
			return 'Ed Easley';

		case 'Jung-ho Kang':
			return 'Jung Ho Kang';

		case 'Dennis Tepera':
			return 'Ryan Tepera';

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

		case 'Steven Souza':
			return 'Steven Souza Jr.';

		case 'Tom Layne':
			return 'Tommy Layne';

		case 'Nate Karns':
			return 'Nathan Karns';

		case 'Delino Deshields Jr.':
			return 'Delino DeShields';

		case 'Robbie Ross':
			return 'Robbie Ross Jr.';

		case 'JR Murphy':
			return 'John Ryan Murphy';

		case 'Jon Niese':
			return 'Jonathon Niese';

		case 'Matthew Tracy':
			return 'Matt Tracy';

		case 'Andrew Schugel':
			return 'A.J. Schugel';

		case 'Sugar Marimon':
			return 'Sugar Ray Marimon';

		case 'Daniel Muno':
			return 'Danny Muno';

		case 'Nicholas Tropeano':
			return 'Nick Tropeano';

		case 'Enrique Hernandez':
			return 'Kike Hernandez';

		case 'Kenneth Roberts':
			return 'Ken Roberts';

		case 'Shin-Soo Choo':
			return 'Shin-soo Choo';

		case 'Chris Young':
			if ($positionType == 'hitter') {
				return 'Chris Young (OF)';
			}

			if ($positionType == 'pitcher') {
				return 'Chris Young (SP)';
			}
		
		default:
			return $fgName;
	}
}


/****************************************************************************************
DK NAME TO BAT NAME MLB
****************************************************************************************/

function changeDkNameToBatName($dkName, $playerType) {
    if ($dkName == 'Joe Ross') {
        return 'Joseph Ross';
    }

    if ($dkName == 'Ivan De Jesus Jr.') {
        return 'Ivan De Jesus';
    }

    if ($dkName == 'John Mayberry Jr.') {
        return 'John Mayberry';
    }

    if ($dkName == 'Steven Souza Jr.') {
        return 'Steven Souza';
    }

    if ($dkName == 'Kike Hernandez') {
        return 'Enrique Hernandez';
    }

    if ($dkName == 'Lance McCullers') {
        return 'Lance McCullers';
    }

    if ($dkName == 'Jorge De La Rosa') {
        return 'Jorge de la Rosa';
    }

    if ($dkName == 'Thomas Field') {
        return 'Tommy Field';
    }

    if ($dkName == 'Jonathon Niese') {
        return 'Jon Niese';
    }

    if ($dkName == 'Jung Ho Kang') {
        return 'Jung-ho Kang';
    }      

    if ($dkName == 'Nathan Karns') {
        return 'Nate Karns';
    }

    if ($dkName == 'Sean O\'Sullivan') {
        // return 'Sean O`Sullivan';
    }

    if ($dkName == 'Travis d\'Arnaud') {
        // return 'Travis d`Arnaud';
    }

    if ($dkName == 'Eric Young Jr.') {
        return 'Eric Young';
    }

    if ($dkName == 'Michael A. Taylor') {
        return 'Michael Taylor';
    }

    if ($dkName == 'Rubby De La Rosa') {
        return 'Rubby de la Rosa';
    }

    if ($dkName == 'Shin-soo Choo') {
        return 'Shin-Soo Choo';
    }

    if ($dkName == 'Delino DeShields') {
        return 'Delino Deshields Jr.';
    }

    if ($dkName == 'Chris Young (OF)') {
    	if ($playerType == 'hitters') {
    		return 'Chris Young';
    	}
    }

    if ($dkName == 'Chris Young (SP)') {
		if ($playerType == 'pitchers') {
    		return 'Chris Young';
    	}
    }

    return $dkName;
}


/****************************************************************************************
FIX MLB PLAYERS WITH SAME NAME
****************************************************************************************/

function fixMlbPlayersWithSameName($name, $position) {
	if ($name == 'Chris Young' && $position == 'OF') {
		return 'Chris Young (OF)';
	}

	if ($name == 'Chris Young' && $position == 'SP') {
		return 'Chris Young (SP)';
	}

	return $name;
}