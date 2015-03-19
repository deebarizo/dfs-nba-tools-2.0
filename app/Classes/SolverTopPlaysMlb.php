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

	public function generateLineups($timePeriodInUrl, $date) {
		$timePeriod = urlToUcFirst($timePeriodInUrl);

		$players = $this->getPlayers($timePeriod, $date);

		$positions = $this->getPositions($timePeriod, $date);

		$lineup = $this->generateLineup($players, $positions);
	}	

	private function generateLineup($players, $positions) {
		$lineup = [
			'players' => []
		];

		$positionKey = rand(0, 6);
		$randomPosition = $positions[$positionKey];

		$withinPositionRandomCount = rand(1, $randomPosition['num_of_players']);

		$count = 0;

		foreach ($players as $player) {
			if ($player->position == $randomPosition['name']) {
				$count++;

				if ($count == $withinPositionRandomCount) {
					$lineup['players'][] = $player;

					break;
				}
			}
		}

		ddAll($lineup);
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
		unset($player);

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