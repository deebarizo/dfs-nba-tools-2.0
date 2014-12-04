<?php namespace App;

class SolverTopPlays {

	public function buildLineupsWithTopPlays($players) {
		$numOfPlayersPerPosition = $this->calculateNumOfPlayersPerPosition($players);

		for ($i=0; $i < 10000; $i++) { 
			$validLineups[] = $this->buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition);
		}
	}

	private function buildOneLineupWithTopPlays($players, $numOfPlayersPerPosition) {
		do {
			$validLineup = $this->loopThroughPlayersToBuildOneLineup($players, $numOfPlayersPerPosition);
		} while ($lineup['total_salary'] > 60000);

		return $validLineup;
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

		unset($lineup['C2']); // only one center per lineup

		$lineup['total_salary'] = 0;

		foreach ($lineup['roster_spots'] as $rosterSpot) {
			$lineup['total_salary'] += $rosterSpot->salary;
		}

		ddAll($lineup);

		return $lineup;
	}

	private function getPlayersPerPosition($players, $position, array $rosterSpotsWithinPosition) {
		$topPlaysOfPosition = [];

		foreach ($players as $player) {
			$topPlaysOfPosition = $this->checkForMatchingPosition($player, $position, $topPlaysOfPosition);
		}

		if ($position == 'C') {
			$randomNum = $rosterSpotsWithinPosition[0];

			$playerWithinPosition[] = $topPlaysOfPosition[$randomNum - 1];
			$playerWithinPosition[] = $topPlaysOfPosition[$randomNum - 1];

			return array($playerWithinPosition[0], $playerWithinPosition[1]);
		}

		foreach ($rosterSpotsWithinPosition as $randomNum) {
			$playerWithinPosition[] = $topPlaysOfPosition[$randomNum - 1]; // because index starts at 0
		}

		return array($playerWithinPosition[0], $playerWithinPosition[1]);
	}

	private function checkForMatchingPosition($player, $position, $topPlaysOfPosition) {
		if ($player->position == $position) {
			$topPlaysOfPosition[] = $player;

			return $topPlaysOfPosition;
		}

		return $topPlaysOfPosition;
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

	private function calculateNumOfPlayersPerPosition($players) {
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

		return $numOfPlayersPerPosition;
	}

	////// Validation of top plays

	private $numInPositions = [
		'PG' => ['required_num' => 2, 'current_num' => 0],
		'SG' => ['required_num' => 2, 'current_num' => 0],
		'SF' => ['required_num' => 2, 'current_num' => 0],
		'PF' => ['required_num' => 2, 'current_num' => 0],
		'C'  => ['required_num' => 1, 'current_num' => 0]
	];

	////

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

	////

	public function validateMinimumTotalSalary($players) {
		foreach ($players as $key => $player) {
			$position[$key] = $player->position;
			$salary[$key] = $player->salary;
		}

		array_multisort($position, SORT_ASC, $salary, SORT_ASC, $players);

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

		if ($totalSalary > 60000) {
			return false;
		}

		return true;
	}

	private function getSalariesOfCheapestPlayers($playersSortedByPosition, $position, $num) {
		$totalSalaryWithinPosition = 0;

		for ($i=0; $i < $num['required_num']; $i++) { 
			$totalSalaryWithinPosition += $playersSortedByPosition[$position][$i]->salary;
		}	

		return $totalSalaryWithinPosition;
	}

}