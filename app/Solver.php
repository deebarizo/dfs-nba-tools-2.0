<?php namespace App;

class Solver {

	private $originalPlayers;
	private $lineups;
	private $lineup;
	private $algorithmOrder;

	public function buildFdNbaLineups($players) {

		$originalPlayers = $players;

		$lineups = array();

		for ($i=0; $i < 10; $i++) { 
			$algorithmOrders = $this->setAlgorithmOrders($i);

			ddAll($algorithmOrders);

			foreach ($algorithmOrders as $order) {
				foreach ($order as $value) {
					list($players, $lineups) = $this->addPlayertoLineup($players, $players[$key], $key, $lineups);
				}
			}
		}

		ddAll($lineups);

		return $lineups;
	}

	private function addPlayertoLineup($players, $player, $key, $lineups) {
		$lineups[] = array($player);

		unset($players[$key]);

		return array ($players, $lineups);
	}

	private function setAlgorithmOrders($key) {
		$i = $key;

		$algorithmOrders = [
			1 => [$i, 0, 0, 0, 0, 0, 0, 0, 0],
			2 => [$i, 1, 0, 0, 0, 0, 0, 0, 0],
			3 => [$i, 0, 1, 0, 0, 0, 0, 0, 0],
			4 => [$i, 0, 0, 1, 0, 0, 0, 0, 0],
			5 => [$i, 0, 0, 0, 1, 0, 0, 0, 0],
			6 => [$i, 0, 0, 0, 0, 1, 0, 0, 0],
			7 => [$i, 0, 0, 0, 0, 0, 1, 0, 0],
			8 => [$i, 0, 0, 0, 0, 0, 0, 1, 0],
			9 => [$i, 0, 0, 0, 0, 0, 0, 0, 1]
		];		

		return $algorithmOrders;
	}

}