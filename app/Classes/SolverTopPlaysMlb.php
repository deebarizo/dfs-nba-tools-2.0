<?php namespace App\Classes;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\DailyFdFilter;
use App\Models\TeamFilter;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class SolverTopPlaysMlb {

	/****************************************************************************************
	GLOBAL VARIABLES
	****************************************************************************************/

	private $lineupBuilderIterations = 200;
	private $targetPercentageModifier = 0;
	private $minimumTotalSalary = 49500; 
	private $maximumTotalSalary = 50000;


	/****************************************************************************************
	GENERATE LINEUPS
	****************************************************************************************/

	public function generateLineups($timePeriodInUrl, $date) {
		$timePeriod = urlToUcFirst($timePeriodInUrl);

		$players = $this->getPlayers($timePeriod, $date);
		$buyIn = $players[0]->buy_in;

		$positions = $this->getPositions($timePeriod, $date);

		$lineups = [];

		for ($i = 0; $i < $this->lineupBuilderIterations; $i++) { 
			do {
				$lineup = $this->generateLineup($players, $positions);
			} while ($lineup['total_salary'] > $this->maximumTotalSalary || $lineup['total_salary'] < $this->minimumTotalSalary);

			ddAll($lineup);

			$lineups[] = $lineup;
		}

		ddAll($lineups);
	}	


	/****************************************************************************************
	GENERATE LINEUP
	****************************************************************************************/

	private function generateLineup($players, $positions) {
		$lineup = [
			'players' => [],
			'salary' => 0
		];

		# prf($players);
		# ddAll($positions);

		foreach ($positions as $position) {
			$lineup['players'][] = $this->generateRandomPlayerPerPosition($players, $position);
		}

		ddAll($lineup);

		$lineup['players'] = $this->sortLineup($lineup['players']);

		# prf($lineup['salary']);
		# ddAll($lineup['players']);

		return $lineup;
	}

	private function generateRandomPlayerPerPosition($players, $position) {
		$randomNumber = rand(1, $position['num_of_players']);

		$count = 0;

		foreach ($players as $player) {
			if ($player->position == $position['name']) {
				$count++;

				if ($count == $randomNumber) {
					return $player;
				}
			}
		}
	}

	private function upgradeLineupToUseSalaryCap($lineup, $positions, $players) {
		foreach ($players as &$player) {
			$player->eligible_for_lineup = 1;
		}
		unset($player);

		$salaryLeft = 50000 - $lineup['salary'];

		$eligiblePositions = [];

		for ($i = 0; $i < 1000; $i++) { 
			if ($lineup['salary'] < 49500) {
				foreach ($positions as $position) {
					if ($position['num_of_players'] > 0) {
						$eligiblePositions[] = $position;
					}
				}

				foreach ($eligiblePositions as $eligiblePosition) { 
					$selectedPosition = $eligiblePosition;

					foreach ($lineup['players'] as $lineupPlayer) {
						if ($lineupPlayer->position == $selectedPosition['name']) {
							$lineupPlayerToReplace = $lineupPlayer;
							break;
						}
					}

					# prf($lineup);
					# prf($lineupPlayerToReplace);

					foreach ($players as $player) {
						if ($player->position == $selectedPosition['name'] 
							&& $player->eligible_for_lineup == 1 
							&& $player->salary > $lineupPlayerToReplace->salary
							&& $player->salary - $lineupPlayerToReplace->salary <= 50000 - $lineup['salary']) 
						{
							if ($this->validStack($lineup, $player, $lineupPlayerToReplace)) {
								foreach ($lineup['players'] as &$lineupPlayer) {
									if ($lineupPlayerToReplace->mlb_player_id == $lineupPlayer->mlb_player_id) {
										$lineupPlayer = $player;

										$lineup['salary'] += $player->salary - $lineupPlayerToReplace->salary;

										break;
									}
								}
								unset($lineupPlayer);
							}
						}
					}
				}
			} else {
				break;
			}
		}

		return $lineup;
	}

	private function validStack($lineup, $player, $lineupPlayerToReplace) {
		if ($lineupPlayerToReplace->position == 'SP') {
			return true;
		}

		foreach ($lineup['players'] as $lineupPlayer) {
			if ($lineupPlayer->mlb_player_id == $lineupPlayerToReplace->mlb_player_id) {
				return false;
			}
		}

		$stackCount = 0;

		foreach ($lineup['players'] as $lineupPlayer) {
			if ($lineupPlayer->mlb_team_id == $lineupPlayer->mlb_team_id && $lineupPlayer->mlb_player_id != $lineupPlayerToReplace->mlb_player_id) {
				$stackCount++;
			} 
		}

		if ($stackCount == 6) {
			return false;
		}

		return true;
	}

	private function eligiblePlayerForLineup($lineup, $player) {
		if ($lineup['salary'] + $player->salary > 50000) {
			return false;
		}

		$avgSalaryLeft = (50000 - $lineup['salary']) / (10 - count($lineup['players']));

		if ($player->position != 'SP') {
			if ($player->salary > $avgSalaryLeft) {
				return false;
			}
		}

		foreach ($lineup['players'] as $lineupPlayer) {
			if ($lineupPlayer->mlb_player_id == $player->mlb_player_id) {
				return false;
			}
		}

		$stackCount = 0;

		foreach ($lineup['players'] as $lineupPlayer) {
			if ($lineupPlayer->mlb_team_id == $player->mlb_team_id && $lineupPlayer->position != 'SP') {
				$stackCount++;
			}			
		}

		if ($stackCount == 6) {
			return false;
		}

		return true;
	}

	private function sortLineup($lineupPlayers) { // http://stackoverflow.com/questions/11145393/sorting-a-php-array-of-arrays-by-custom-order
		$positionOrder = ['SP', 'C', '1B', '2B', '3B', 'SS', 'OF'];

		usort($lineupPlayers, function ($a, $b) use($positionOrder) {
			$posA = array_search($a->position, $positionOrder);
			$posB = array_search($b->position, $positionOrder);
			return $posA - $posB;
		});

		$names = [];

		$SPs = [
			$lineupPlayers[0], 
			$lineupPlayers[1]
		];

		foreach ($SPs as $key => $SP) {
			$names[$key] = $SP->name;
		}

		array_multisort($names, SORT_ASC, $SPs);

		$lineupPlayers[0] = $SPs[0];
		$lineupPlayers[1] = $SPs[1];

		# ddAll($lineupPlayers);

		$OFs = [
			$lineupPlayers[7],
			$lineupPlayers[8],
			$lineupPlayers[9]
		];

		foreach ($OFs as $key => $OF) {
			$names[$key] = $OF->name;
		}

		array_multisort($names, SORT_ASC, $OFs);

		$lineupPlayers[7] = $OFs[0];
		$lineupPlayers[8] = $OFs[1];
		$lineupPlayers[9] = $OFs[2];

		return $lineupPlayers;
	}

	private function getPositions($timePeriod, $date) {
		$positions = [
			['name' => 'SP', 'remaining_spots' => 2],
			['name' => 'C', 'remaining_spots' => 1],
			['name' => '1B', 'remaining_spots' => 1],
			['name' => '2B', 'remaining_spots' => 1],
			['name' => '3B', 'remaining_spots' => 1],
			['name' => 'SS', 'remaining_spots' => 1],
			['name' => 'OF', 'remaining_spots' => 3]
		];

		$numOfPlayersInPositions = DB::table('player_pools')
										->select(DB::raw('position as name, count(*) as number'))
										->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
										->where('time_period', $timePeriod)
										->where('date', $date)
										->where('target_percentage', '>', 0)
										->groupBy('position')
										->get();

		$slashPositions = [];

		foreach ($numOfPlayersInPositions as $key => $numOfPlayersInPosition) {
			if (preg_match('/\//', $numOfPlayersInPosition->name)) {
				$slashPositionName[0] = preg_replace('/(\w+)(\/)(\w+)/', '$1', $numOfPlayersInPosition->name);
				$slashPositionName[1] = preg_replace('/(\w+)(\/)(\w+)/', '$3', $numOfPlayersInPosition->name);

				$slashPosition = new \stdClass();
				$slashPosition->name = $slashPositionName[0];
				$slashPosition->number = $numOfPlayersInPosition->number;
				$slashPositions[] = $slashPosition;

				$slashPosition = new \stdClass();
				$slashPosition->name = $slashPositionName[1];
				$slashPosition->number = $numOfPlayersInPosition->number;
				$slashPositions[] = $slashPosition;

				unset($numOfPlayersInPositions[$key]);
			}
		}

		foreach ($numOfPlayersInPositions as &$numOfPlayersInPosition) {
			$numOfPlayersInPosition->number += $this->addSlashPosition($numOfPlayersInPosition, $slashPositions);
		} 
		unset($numOfPlayersInPosition);

		foreach ($positions as &$position) {
			$position['num_of_players'] = $this->addNumberOfPlayersPerPosition($position, $numOfPlayersInPositions);
			$position['unfilled'] = 1;
		}
		unset($position);

		# prf($numOfPlayersInPositions);
		# ddAll($positions);

		return $positions;
	}

	private function addNumberOfPlayersPerPosition($position, $numOfPlayersInPositions) {
		foreach ($numOfPlayersInPositions as $numOfPlayersInPosition) {
			if ($position['name'] == $numOfPlayersInPosition->name) {
				return $numOfPlayersInPosition->number;
			}
		}
	}

	private function addSlashPosition($numOfPlayersInPosition, $slashPositions) {
		$number = 0;

		foreach ($slashPositions as $slashPosition) {
			if ($numOfPlayersInPosition->name == $slashPosition->name) {
				$number += $slashPosition->number;
			}
		}

		return $number;
	}

	private function getPlayers($timePeriod, $date) {
		$players = DB::table('player_pools')
						->select('buy_in', 'mlb_player_id', 'target_percentage', 'mlb_team_id', 'position', 'salary', 'name')
						->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
						->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
						->where('time_period', $timePeriod)
						->where('date', $date)
						->where('target_percentage', '>', 0)
						->get();

		foreach ($players as $key => $player) {
			if (preg_match('/\//', $player->position)) {
				list($slashPlayer[0], $slashPlayer[1]) = $this->splitUpSlashPlayers($player);

				$players[] = $slashPlayer[0];
				$players[] = $slashPlayer[1];

				unset($players[$key]);
			}
		}

		foreach ($players as $key => &$player) {
			$player->eligible_for_lineup = 1;
		}

		# ddAll($players);

		return $players;
	}

	private function splitUpSlashPlayers($player) {
		$slashPositions[0] = preg_replace('/(\w+)(\/)(\w+)/', '$1', $player->position);
		$slashPositions[1] = preg_replace('/(\w+)(\/)(\w+)/', '$3', $player->position);

		foreach ($slashPositions as $key => $slashPosition) {
			$slashPlayer[$key] = new \stdClass();

			$slashPlayer[$key]->buy_in = $player->buy_in;
			$slashPlayer[$key]->mlb_player_id = $player->mlb_player_id;
			$slashPlayer[$key]->target_percentage = $player->target_percentage / 2;
			$slashPlayer[$key]->mlb_team_id = $player->mlb_team_id;
			$slashPlayer[$key]->position = $slashPosition;
			$slashPlayer[$key]->salary = $player->salary;
			$slashPlayer[$key]->name = $player->name;
		}

		# prf($slashPlayer);

		return array($slashPlayer[0], $slashPlayer[1]);
	}

}