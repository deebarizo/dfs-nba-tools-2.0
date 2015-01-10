<?php namespace App;

class SolverTopPlays {

	/****************************************************************************************
	GLOBAL VARIABLES
	****************************************************************************************/

	private $lineupBuilderIterations = 2000;
	private $targetPercentageModifier = 0;
	private $minimumTotalSalary = 59400; // 1% of cap
	private $maximumTotalSalary = 60000;


	/****************************************************************************************
	APPEND MORE METADATA TO LINEUPS
	****************************************************************************************/

	public function appendMoreMetadataToActiveLineups($metadataOfActiveLineups, $buyIn) {
		foreach ($metadataOfActiveLineups as &$metadataOfActiveLineup) {
			$metadataOfActiveLineup['css_class_blue_border'] = 'active-lineup';
			$metadataOfActiveLineup['css_class_edit_info'] = '';
			$metadataOfActiveLineup['anchor_text'] = 'Remove';
			$metadataOfActiveLineup['css_class_money_lineup'] = $this->getMoneyCssClass($metadataOfActiveLineup['money']);
			$metadataOfActiveLineup['play_or_unplay_anchor_text'] = $this->getMoneyAnchorText($metadataOfActiveLineup['money']);
			$metadataOfActiveLineup['buy_in_percentage'] = numFormat($metadataOfActiveLineup['buy_in'] / $buyIn * 100, 2);
		}

		unset($metadataOfActiveLineup);

		return $metadataOfActiveLineups;
	}


	/****************************************************************************************
	GET ACTIVE LINEUPS
	****************************************************************************************/

	public function getActiveLineups($metadataOfActiveLineups, $playerPoolId) {
		$activeLineups = [];

		foreach ($metadataOfActiveLineups as $metadataOfActiveLineup) {
			$activeLineups[] = $metadataOfActiveLineup;
		}

		# ddAll($activeLineups);

		$playersInActiveLineups = getPlayersInActiveLineups($playerPoolId);

		foreach ($playersInActiveLineups as $player) {
			foreach ($activeLineups as &$activeLineup) {
				if ($activeLineup['hash'] == $player->hash) {
					$activeLineup['roster_spots'][] = $player;
				}
			}

			unset($activeLineup);
		}

		return $activeLineups;
	}


	/****************************************************************************************
	FILTER UNSPENT PLAYERS
	****************************************************************************************/

	public function filterUnspentPlayers($players, $activeLineups, $buyIn) {
		$unspentPlayers = [];

		foreach ($players as $player) {
			$spentPercentage = 0;
			$spentPercentage = $this->getSpentPercentage($spentPercentage, $activeLineups, $player, $buyIn);

			$unspentPlayers = $this->appendIfUnspentPlayer($spentPercentage, $player, $unspentPlayers);
		}

		return $unspentPlayers;
	}

	private function appendIfUnspentPlayer($spentPercentage, $player, $unspentPlayers) {
		if ($spentPercentage + $this->targetPercentageModifier < $player->target_percentage) {
			$unspentPlayers[] = $player;
		}

		return $unspentPlayers;
	}

	private function getSpentPercentage($spentPercentage, $activeLineups, $player, $buyIn) {
		foreach ($activeLineups as $activeLineup) {
			$spentPercentage = $this->getSpentPercentageForLineup($spentPercentage, $activeLineup, $player, $buyIn);
		}

		return $spentPercentage;
	}

	private function getSpentPercentageForLineup($spentPercentage, $activeLineup, $player, $buyIn) {
		foreach ($activeLineup['roster_spots'] as $rosterSpot) {
			$spentPercentage += $this->addSpentPercentage($rosterSpot->player_id, $player->player_id, $rosterSpot->buy_in, $buyIn);
		}

		return $spentPercentage;
	}

	private function addSpentPercentage($playerId1, $playerId2, $rosterSpotBuyIn, $buyIn) {
		if ($playerId1 == $playerId2) {
			return $rosterSpotBuyIn / $buyIn * 100;
		}

		return 0;
	}


	/****************************************************************************************
	SORT PLAYERS
	****************************************************************************************/

	public function sortPlayers($players) {
		foreach ($players as $key => $player) {
			$name[$key] = $player->name;
		}

		array_multisort($name, SORT_ASC, $players);

		# ddAll($players);

		return $players;
	}
	

	/****************************************************************************************
	PROCESS ACTIVE AND MONEY LINEUPS
	****************************************************************************************/

	public function markAndAppendActiveLineups($lineups, $playerPoolId, $buyIn) {
		$activeLineups = getMetadataOfActiveLineups($playerPoolId);

		$playersInActiveLineups = getPlayersInActiveLineups($playerPoolId);

		foreach ($lineups as &$lineup) {
			list($lineup, $activeLineups) = $this->markLineupIfActive($lineup, $activeLineups, $buyIn);
		}

		unset($lineup);

		foreach ($activeLineups as $activeLineup) {
			$activeLineupsNotInSolver = [];

			foreach ($playersInActiveLineups as $player) {
				if ($activeLineup['hash'] == $player->hash) {
					$activeLineupsNotInSolver['roster_spots'][] = $player;
				}
			}

			$activeLineupsNotInSolver['total_salary'] = $activeLineup['total_salary'];
			$activeLineupsNotInSolver['hash'] = $activeLineup['hash'];
			$activeLineupsNotInSolver['total_unspent_salary'] = $this->maximumTotalSalary - $activeLineup['total_salary'];

			$activeLineupsNotInSolver['active'] = 1;
			$activeLineupsNotInSolver['css_class_blue_border'] = 'active-lineup';
			$activeLineupsNotInSolver['css_class_edit_info'] = '';
			$activeLineupsNotInSolver['anchor_text'] = 'Remove';

			$activeLineupsNotInSolver['money'] = $activeLineup['money'];
			$activeLineupsNotInSolver['css_class_money_lineup'] = $this->getMoneyCssClass($activeLineup['money']);
			$activeLineupsNotInSolver['play_or_unplay_anchor_text'] = $this->getMoneyAnchorText($activeLineup['money']);

			$activeLineupsNotInSolver['buy_in'] = $activeLineup['buy_in'];
			$activeLineupsNotInSolver['buy_in_percentage'] = numFormat($activeLineup['buy_in'] / $buyIn * 100, 2);

			array_push($lineups, $activeLineupsNotInSolver);
		}

		foreach ($lineups as &$lineup) {
			$lineup['num_of_unique_teams'] = $this->getNumOfUniqueTeams($lineup['roster_spots']);
		}

		unset($lineup);

		foreach ($lineups as &$lineup) {
			$lineup['team_css_classes'] = $this->getTeamCssClasses($lineup['num_of_unique_teams'], $lineup['roster_spots']);
		}

		unset($lineup);

		foreach ($lineups as $key => $lineup) {
			$active[$key] = $lineup['active'];
			$money[$key] = $lineup['money'];
			$numOfUniqueTeams[$key] = $lineup['num_of_unique_teams'];
			$totalSalary[$key] = $lineup['total_salary'];
		}

		array_multisort($active, SORT_ASC, 
						$money, SORT_ASC, 
						$numOfUniqueTeams, SORT_DESC,
						$totalSalary, SORT_DESC, 
						$lineups);

		# ddAll($lineups);

        return $lineups;
	}

	private function getNumOfUniqueTeams($rosterSpots) {
		$uniqueTeams = $this->getUniqueTeamsOfLineup($rosterSpots);

		return count($uniqueTeams);
	}

	private function getTeamCssClasses($numOfUniqueTeams, $rosterSpots) {
		if ($numOfUniqueTeams == 9) {
			$teamCssClasses = $this->createEmptyTeamCssClasses($rosterSpots);

			return $teamCssClasses;
		}

		$teams = $this->getTeamsOfLineup($rosterSpots);

		foreach ($rosterSpots as $key => $rosterSpot) {
			$teamCount = 0;

			foreach ($teams as $team) {
				if ($team == $rosterSpot->abbr_br) {
					$teamCount++;
				}
			}

			if ($teamCount > 1) {
				$teamCssClasses[$key] = 'team-bold';
			}

			if ($teamCount == 1) {
				$teamCssClasses[$key] = '';
			}
		}

		return $teamCssClasses;
	}

	private function createEmptyTeamCssClasses($rosterSpots) {
		foreach ($rosterSpots as $key => $rosterSpot) {
			$teamCssClasses[$key] = '';
		}

		return $teamCssClasses;
	}

	private function getUniqueTeamsOfLineup($rosterSpots) {
		$teams = $this->getTeamsOfLineup($rosterSpots);

		$uniqueTeams = array_unique($teams);

		return $uniqueTeams;		
	}

	private function getTeamsOfLineup($rosterSpots) {
		$teams = [];

		foreach ($rosterSpots as $rosterSpot) {
			$teams[] = $rosterSpot->abbr_br;
		}

		return $teams;		
	}

	private function markLineupIfActive($lineup, $activeLineups, $buyIn)	{
		foreach ($activeLineups as $key => $activeLineup) {
			if ($lineup['hash'] == $activeLineup['hash']) {
				$lineup['active'] = 1;
				$lineup['css_class_blue_border'] = 'active-lineup';
				$lineup['css_class_edit_info'] = '';
				$lineup['anchor_text'] = 'Remove';

				$lineup['money'] = $activeLineup['money'];
				$lineup['css_class_money_lineup'] = $this->getMoneyCssClass($activeLineup['money']);
				$lineup['play_or_unplay_anchor_text'] = $this->getMoneyAnchorText($activeLineup['money']);
				
				$lineup['buy_in'] = $activeLineup['buy_in'];
				$lineup['buy_in_percentage'] = numFormat($activeLineup['buy_in'] / $buyIn * 100, 2);

				unset($activeLineups[$key]);

				return array($lineup, $activeLineups);
			}
		}

		$lineup['active'] = 0;
		$lineup['css_class_blue_border'] = '';
		$lineup['css_class_edit_info'] = 'edit-lineup-buy-in-hidden';
		$lineup['anchor_text'] = 'Add';

		$lineup['money'] = 0;
		$lineup['css_class_money_lineup'] = '';
		$lineup['play_or_unplay_anchor_text'] = 'Play';

		$lineup['buy_in'] = 0;
		$lineup['buy_in_percentage'] = 0;

		return array($lineup, $activeLineups);
	}

	public function areThereActiveLineups($lineups) {
		foreach ($lineups as $lineup) {
			if ($lineup['active'] == 1) {
				return 1;
			}
		}

		return 0;
	}

	public function calculateUnspentBuyIn($areThereActiveLineups, $lineups, $buyIn) {
		if ($areThereActiveLineups == 0) {
			return $buyIn;
		}

		$spentBuyIn = 0;

		foreach ($lineups as $lineup) {
			$spentBuyIn += $this->addBuyInOfActiveLineup($lineup);
		}

		return $buyIn - $spentBuyIn;
	}

	private function addBuyInOfActiveLineup($lineup) {
		if ($lineup['active'] == 0) {
			return 0;
		}

		return $lineup['buy_in'];
	}

	private function getMoneyCssClass($isThisAMoneyLineup) {
		if ($isThisAMoneyLineup == 1) {
			return 'money-lineup';
		}		

		return '';		
	}

	private function getMoneyAnchorText($isThisAMoneyLineup) {
		if ($isThisAMoneyLineup == 1) {
			return 'Unplay';
		}		

		return 'Play';
	}


	/****************************************************************************************
	BUILD LINEUPS
	****************************************************************************************/

	public function buildLineupsWithTopPlays($players) {
		$numOfPlayersPerPosition = [
			'PG' => 0,
			'SG' => 0,
			'SF' => 0,
			'PF' => 0,
			'C' => 0
		];

		foreach ($players as $player) {
			$numOfPlayersPerPosition[$player->position]++;
		}

		$duplicateLineups = [];

		for ($i=0; $i < $this->lineupBuilderIterations; $i++) { 
			$duplicateLineups[] = $this->buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition);
		}

		$uniqueLineups = $this->removeDuplicateLineups($duplicateLineups);

		return $uniqueLineups;
	}

	public function removeDuplicateLineups($duplicateLineups) {
		$duplicateHashes = [];

		foreach ($duplicateLineups as $duplicateLineup) {
			$duplicateHashes[] = $duplicateLineup['hash'];
		}

		$uniqueHashes = array_unique($duplicateHashes);

		$uniqueLineups = [];

		foreach ($uniqueHashes as $uniqueHash) {
			$uniqueLineups[] = $this->getLineupByHash($uniqueHash, $duplicateLineups);
		}

		return $uniqueLineups;
	}

	public function getLineupByHash($uniqueHash, $duplicateLineups) {
		foreach ($duplicateLineups as $duplicateLineup) {
			if ($uniqueHash == $duplicateLineup['hash']) {
				return $duplicateLineup;
			}
		}
	}


	/****************************************************************************************
	BUILD ONE LINEUP
	****************************************************************************************/

	private function buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition) {
		do {
			$lineup = $this->loopThroughPlayersToBuildOneLineup($players, $numOfPlayersPerPosition);
		} while ($lineup['total_salary'] > 60000 || 
				 $lineup['total_salary'] < $this->minimumTotalSalary);

		# ddAll($lineup);

		return $lineup;
	}

	private function loopThroughPlayersToBuildOneLineup($players, $numOfPlayersPerPosition) {
		$randomNumPerPosition = [
			'PG' => array(0, 0),
			'SG' => array(0, 0),
			'SF' => array(0, 0),
			'PF' => array(0, 0),
			'C' => array(0)
		];

		foreach ($numOfPlayersPerPosition as $position => $num) {
			$randomNumPerPosition = $this->generateRandomNumPerPosition($position, $num, $randomNumPerPosition);
		}

		foreach ($randomNumPerPosition as $position => $rosterSpotsWithinPosition) {
			list($lineup['roster_spots'][$position.'1'], $lineup['roster_spots'][$position.'2']) = 
				$this->getPlayersPerPosition($players, $position, $rosterSpotsWithinPosition);
		}

		unset($lineup['roster_spots']['C2']); // only one center per lineup

		$lineup['total_salary'] = 0;
		$lineup['hash'] = '';

		foreach ($lineup['roster_spots'] as $rosterSpot) {
			$lineup['total_salary'] += $rosterSpot->salary;
			$lineup['hash'] .= $rosterSpot->player_id;
		}

		$lineup['total_unspent_salary'] = $this->maximumTotalSalary - $lineup['total_salary'];

		return $lineup;
	}

	private function generateRandomNumPerPosition($position, $num, $randomNumPerPosition) {
		if ($position == 'C' && $num == 1) {
			$randomNumPerPosition['C'] = array($num);

			return $randomNumPerPosition;
		}

		$firstNum = rand(1, $num);

		do {
			$secondNum = rand(1, $num);
		} while ($firstNum == $secondNum);

		if ($position == 'C') {
			$randomNumPerPosition['C'] = array($firstNum);

			return $randomNumPerPosition;			
		}

		$randomNumPerPosition[$position] = array($firstNum, $secondNum);

		return $randomNumPerPosition;
	}

	private function getPlayersPerPosition($players, $position, array $rosterSpotsWithinPosition) {
		$topPlaysOfPosition = [];

		foreach ($players as $player) {
			$topPlaysOfPosition = $this->checkForMatchingPosition($player, $position, $topPlaysOfPosition);
		}

		if ($position == 'C') { // only one center per lineup
			$randomNum = $rosterSpotsWithinPosition[0];

			$playersWithinPosition[] = $topPlaysOfPosition[$randomNum - 1];
			$playersWithinPosition[] = $topPlaysOfPosition[$randomNum - 1];

			return array($playersWithinPosition[0], $playersWithinPosition[1]);
		}

		foreach ($rosterSpotsWithinPosition as $randomNum) {
			$playersWithinPosition[] = $topPlaysOfPosition[$randomNum - 1]; // because index starts at 0
		}

		foreach ($playersWithinPosition as $key => $player) {
			$salary[$key] = $player->salary;
		}

		array_multisort($salary, SORT_DESC, $playersWithinPosition);

		# dd($playersWithinPosition);

		return array($playersWithinPosition[1], $playersWithinPosition[0]); 
			// flip the order because of how the list function works
	}

	private function checkForMatchingPosition($player, $position, $topPlaysOfPosition) {
		if ($player->position == $position) {
			$topPlaysOfPosition[] = $player;

			return $topPlaysOfPosition;
		}

		return $topPlaysOfPosition;
	}


	/****************************************************************************************
	VALIDATE TOP PLAYS
	****************************************************************************************/
	
	private $numInPositions = [
		'PG' => ['required_num' => 2, 'current_num' => 0],
		'SG' => ['required_num' => 2, 'current_num' => 0],
		'SF' => ['required_num' => 2, 'current_num' => 0],
		'PF' => ['required_num' => 2, 'current_num' => 0],
		'C'  => ['required_num' => 1, 'current_num' => 0]
	];

	public function validateTopPlays($players, $activeLineups) {
		if (!empty($activeLineups)) {
			return true;
		}

        if (!$this->validateFdPositions($players)) {
            echo 'You are missing one or more positions.'; 
            exit();
        }

        if (!$this->validateMinimumTotalSalary($players)) {
            echo 'The least expensive lineup is more than $60000.';
            exit();
        }

        if (!$this->validateMaximumTotalSalary($players)) {
            echo 'The most expensive lineup is less than $59400.';
            exit();
        }
	}


	/********************************************
	POSITION
	********************************************/

	private function validateFdPositions($players) {
		$numInPositions = $this->numInPositions;

		foreach ($players as $player) {
			$numInPositions[$player->position]['current_num']++;
		}

		foreach ($numInPositions as $num) {
			if ($num['required_num'] > $num['current_num']) {
				return false;
			}			
		}

		return true;
	}


	/********************************************
	SALARY
	********************************************/

	public function validateMinimumTotalSalary($players) {
		$totalSalary = $this->getTotalSalary($players, 'Minimum');

		if ($totalSalary > 60000) {
			return false;
		}

		return true;
	}

	public function validateMaximumTotalSalary($players) {
		$totalSalary = $this->getTotalSalary($players, 'Maximum');

		if ($totalSalary < $this->minimumTotalSalary) {
			return false;
		}

		return true;
	}

	private function getTotalSalary($players, $minimumOrMaximum) {
		foreach ($players as $key => $player) {
			$position[$key] = $player->position;
			$salary[$key] = $player->salary;
		}

		switch ($minimumOrMaximum) {
			case 'Minimum':
				array_multisort($position, SORT_ASC, $salary, SORT_ASC, $players);
				break;
			
			case 'Maximum':
				array_multisort($position, SORT_ASC, $salary, SORT_DESC, $players);
				break;
		}

		$numInPositions = $this->numInPositions;

		$playersSortedByPosition = [
			'PG' => [],
			'SG' => [],
			'SF' => [],
			'PF' => [],
			'C'  => []
		];

		foreach ($players as $player) {
			$playersSortedByPosition[$player->position][] = $player;
		}

		$totalSalary = 0;

		foreach ($numInPositions as $position => $num) {
			$totalSalary += $this->getSalariesOfCheapestPlayers($playersSortedByPosition, $position, $num);
		}

		return $totalSalary;
	}

	private function getSalariesOfCheapestPlayers($playersSortedByPosition, $position, $num) {
		$totalSalaryWithinPosition = 0;

		for ($i=0; $i < $num['required_num']; $i++) { 
			$totalSalaryWithinPosition += $playersSortedByPosition[$position][$i]->salary;
		}	

		return $totalSalaryWithinPosition;
	}

}