<?php namespace App;

class SolverTopPlays {

	private $minimumTotalSalary = 59400; // 1% of cap


	/********************************************
	PROCESS ACTIVE LINEUPS
	********************************************/

	public function markActiveLineups($lineups, $playerPoolId, $buyIn) {
		$activeLineups = getActiveLineups($playerPoolId);

		foreach ($lineups as &$lineup) {
			$lineup = $this->markLineupIfActive($lineup, $activeLineups, $buyIn);
		}

		unset($lineup);

        return $lineups;
	}

	public function markLineupIfActive($lineup, $activeLineups, $buyIn)	{
		foreach ($activeLineups as $activeLineup) {
			if ($lineup['hash'] == $activeLineup->hash) {
				$lineup['active'] = 1;
				$lineup['css_class_blue_border'] = 'active-lineup';
				$lineup['css_class_edit_info'] = '';
				$lineup['anchor_text'] = 'Remove';
				$lineup['buy_in'] = $activeLineup->buy_in;
				$lineup['buy_in_percentage'] = numFormat($activeLineup->buy_in / $buyIn * 100, 2);

				return $lineup;
			}
		}

		$lineup['active'] = 0;
		$lineup['css_class_blue_border'] = '';
		$lineup['css_class_edit_info'] = 'edit-lineup-buy-in-hidden';
		$lineup['anchor_text'] = 'Add';
		$lineup['buy_in'] = 0;
		$lineup['buy_in_percentage'] = 0;

		return $lineup;
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


	/********************************************
	BUILD LINEUPS
	********************************************/

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

		for ($i=0; $i < 250; $i++) { 
			$duplicateLineups[] = $this->buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition);
		}

		$uniqueLineups = $this->removeDuplicateLineups($duplicateLineups);

		foreach ($uniqueLineups as $key => $uniqueLineup) {
			$totalSalary[$key] = $uniqueLineup['total_salary'];
		}

		array_multisort($totalSalary, SORT_DESC, $uniqueLineups);

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


	/********************************************
	BUILD ONE LINEUP
	********************************************/

	private function buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition) {
		do {
			$lineup = $this->loopThroughPlayersToBuildOneLineup($players, $numOfPlayersPerPosition);
		} while ($lineup['total_salary'] > 60000 || $lineup['total_salary'] < $this->minimumTotalSalary);

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

		$lineup['total_unspent'] = 60000 - $lineup['total_salary'];

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


	/********************************************
	VALIDATE TOP PLAYS
	********************************************/

	private $numInPositions = [
		'PG' => ['required_num' => 2, 'current_num' => 0],
		'SG' => ['required_num' => 2, 'current_num' => 0],
		'SF' => ['required_num' => 2, 'current_num' => 0],
		'PF' => ['required_num' => 2, 'current_num' => 0],
		'C'  => ['required_num' => 1, 'current_num' => 0]
	];

	// Positions

	public function validateFdPositions($players) {
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

	// Salary

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