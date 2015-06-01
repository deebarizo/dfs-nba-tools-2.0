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
use App\Models\Lineup;
use App\Models\LineupDkMlbPlayer;

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

	private $lineupBuilderIterations = 1;
	private $targetPercentageModifier = -100;
	private $minimumTotalSalary = 49500; // change 
	private $maximumTotalSalary = 50000;
	private $minimumStack = 1;


	/****************************************************************************************
	GLOBAL FUNCTIONS
	****************************************************************************************/

	private function getActiveLineupHashes($timePeriod, $date) {
		return DB::table('lineups')
					->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
					->where('time_period', $timePeriod)
					->where('date', $date)
					->lists('hash');
	}


	/****************************************************************************************
	CALCULATE SPENT BUY IN
	****************************************************************************************/

	public function calculateUnspentBuyIn($timePeriod, $date, $buyIn) {
		$activeLineups = $this->getActiveLineups($timePeriod, $date);

		$spentBuyIn = 0;

		foreach ($activeLineups as $activeLineup) {
			$spentBuyIn += $activeLineup['buy_in'];
		}

		return $buyIn - $spentBuyIn;
	}


	/****************************************************************************************
	GET ACTIVE LINEUPS
	****************************************************************************************/

	public function getActiveLineups($timePeriod, $date) {
		$activeLineupPlayers = DB::table('player_pools')
							->select(DB::raw('player_pools.buy_in as daily_buy_in, lineup_dk_mlb_players.mlb_player_id, lineup_dk_mlb_players.position, name, lineups.player_pool_id, total_salary, hash, money, lineups.buy_in as lineup_buy_in, CONCAT_WS("", lineup_dk_mlb_players.mlb_player_id, lineup_dk_mlb_players.position) as id_position'))
							->join('lineups', 'player_pools.id', '=', 'lineups.player_pool_id')
							->join('lineup_dk_mlb_players', 'lineup_dk_mlb_players.lineup_id', '=', 'lineups.id')
							->leftJoin('mlb_players', 'mlb_players.id', '=', 'lineup_dk_mlb_players.mlb_player_id')
							->where('player_pools.time_period', $timePeriod)
							->where('player_pools.date', $date)
							->where('lineups.active', 1)
							->get();

		$dkMlbPlayers = DB::table('player_pools')
						->select('*')
						->leftJoin('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
						->leftJoin('mlb_teams', 'mlb_teams.id', '=', 'dk_mlb_players.mlb_team_id')
						->where('player_pools.time_period', $timePeriod)
						->where('player_pools.date', $date)
						->get();

		foreach ($activeLineupPlayers as $activeLineupPlayer) {
			foreach ($dkMlbPlayers as $dkMlbPlayer) {
				if ($activeLineupPlayer->mlb_player_id == $dkMlbPlayer->mlb_player_id && $activeLineupPlayer->position == $dkMlbPlayer->position) {
					$activeLineupPlayer->target_percentage = $dkMlbPlayer->target_percentage;
					$activeLineupPlayer->abbr_dk = $dkMlbPlayer->abbr_dk;
					$activeLineupPlayer->mlb_team_id = $dkMlbPlayer->mlb_team_id;
					$activeLineupPlayer->salary = $dkMlbPlayer->salary;

					break;
				}
			}
		}

		# prf($dkMlbPlayers);
		# ddAll($activeLineupPlayers);

		$activeLineupHashes = $this->getActiveLineupHashes($timePeriod, $date);

		$activeLineups = [];

		foreach ($activeLineupHashes as $hash) {
			foreach ($activeLineupPlayers as $player) {
				if ($player->hash == $hash) {
					if ($player->money == 0) {
						$moneyLineupCss = '';
						$playOrUnplayAnchorText = 'Play';
					}

					if ($player->money == 1) {
						$moneyLineupCss = 'money-lineup';
						$playOrUnplayAnchorText = 'Unplay';
					}

					$activeLineups[] = [
						'salary' => $player->total_salary,
						'hash' => $hash,
						'css_class_edit_info' => '',
						'css_class_active_lineup' => 'active-lineup',
						'css_class_money_lineup' => $moneyLineupCss,
						'add_or_remove_anchor_text' => 'Remove',
						'buy_in' => $player->lineup_buy_in,
						'buy_in_percentage' => numFormat($player->lineup_buy_in / $player->daily_buy_in * 100, 2),
						'play_or_unplay_anchor_text' => $playOrUnplayAnchorText
					];	

					break;				
				}
			}
		}

		foreach ($activeLineups as &$lineup) {
			foreach ($activeLineupPlayers as $player) {
				if ($player->hash == $lineup['hash']) {
					$lineup['players'][] = $player;
				}
			}
		}
		unset($lineup);

		$activeLineups = $this->sortActiveLineups($activeLineups);

		# ddAll($activeLineups);

		return $activeLineups;
	}

	private function sortActiveLineups($activeLineups) {
		foreach ($activeLineups as $key => $activeLineup) {
			$moneyLineup[$key] = $activeLineup['css_class_money_lineup'];
			$buyIn[$key] = $activeLineup['buy_in'];
		}		

		array_multisort($moneyLineup, SORT_ASC, $buyIn, SORT_DESC, $activeLineups);

		return $activeLineups;
	}


	/****************************************************************************************
	GENERATE LINEUPS
	****************************************************************************************/

	public function generateLineups($timePeriod, $date, $activeLineups) {
		$activeLineupPlayers = $this->getActiveLineupPlayers($activeLineups); // to create unspent target percentages for players

		# ddAll($activeLineupPlayers);

		$players = $this->getPlayers($timePeriod, $date);

		# ddAll($players);

		foreach ($players as $key => $player) {
			$player->unspent_target_percentage = $this->addUnspentTargetPercentage($player, $activeLineupPlayers);

			if ($player->unspent_target_percentage <= $this->targetPercentageModifier) {
				unset($players[$key]);
			}
		}

		# ddAll($players);

		foreach ($players as $player) {
			$buyIn = $player->buy_in;
			break;
		}

		$positions = $this->getPositions($players);

		$activeLineupHashes = $this->getActiveLineupHashes($timePeriod, $date);

		# ddAll($activeLineupHashes);

		$lineups = [];

		// not using solver

		/*

		for ($i = 0; $i < $this->lineupBuilderIterations; $i++) { 
			do {
				$lineup = $this->generateLineup($players, $positions);
			} while ($lineup['salary'] > $this->maximumTotalSalary || 
				$lineup['salary'] < $this->minimumTotalSalary || 
				$this->getNumberOfDuplicatePlayers($lineup['players']) > 0 || 
				$this->isActiveLineup($lineup['hash'], $activeLineupHashes) || 
				$lineup['biggest_stack'] > 6 || 
				$lineup['biggest_stack'] < $this->minimumStack || 
				$lineup['num_of_teams_among_hitters'] < 3 || 
				$this->invalidPitcher($lineup));

			# ddAll($lineup);

			$lineups[] = $lineup;
		}

		$lineups = $this->removeDuplicateLineups($lineups); 

		$lineups = $this->sortLineups($lineups); */

		$activeLineups = $this->getActiveLineups($timePeriod, $date);

		foreach ($activeLineups as $activeLineup) {
			$lineups[] = $activeLineup;
		}

		# ddAll($lineups);

		return array($lineups, $players);
	}	

	private function invalidPitcher($lineup) {
		# ddAll($lineup);

		$pitcherOppTeamIds = [
			$lineup['players'][0]->mlb_opp_team_id,
			$lineup['players'][1]->mlb_opp_team_id
		];

		foreach ($pitcherOppTeamIds as $pitcherOppTeamId) {
			foreach ($lineup['players'] as $lineupPlayer) {
				if ($lineupPlayer->mlb_team_id == $pitcherOppTeamId) {
					return true;
				}
			}
		}

		return false;
	}

	private function addUnspentTargetPercentage($player, $activeLineupPlayers) {
		foreach ($activeLineupPlayers as $activeLineupPlayer) {
			if ($activeLineupPlayer['id_position'] == $player->id_position) {
				return $activeLineupPlayer['unspent_target_percentage'];
			}
		}

		return $player->target_percentage;
	}

	private function getActiveLineupPlayers($activeLineups) {
		$activeLineupPlayerIdPositions = [];

		foreach ($activeLineups as $activeLineup) {
			foreach ($activeLineup['players'] as $activeLineupPlayer) {
				$activeLineupPlayerIdPositions[] = $activeLineupPlayer->id_position;
			}
		}

		$activeLineupPlayerIdPositions = array_unique($activeLineupPlayerIdPositions);

		$activeLineupPlayers = [];

		foreach ($activeLineupPlayerIdPositions as $idPosition) {
			$activeLineupPlayers[] = [
				'id_position' => $idPosition,
				'unspent_target_percentage' => $this->calculateUnspentTargetPercentage($idPosition, $activeLineups)
			];
		}

		return $activeLineupPlayers;
	}

	private function calculateUnspentTargetPercentage($idPosition, $activeLineups) {
		$targetPercentage = $this->getTargetPercentage($idPosition, $activeLineups);

		$unspentTargetPercentage = $targetPercentage;

		foreach ($activeLineups as $activeLineup) {
			foreach ($activeLineup['players'] as $activeLineupPlayer) {
				if ($activeLineupPlayer->id_position == $idPosition) {
					$unspentTargetPercentage -= numFormat($activeLineupPlayer->lineup_buy_in / $activeLineupPlayer->daily_buy_in * 100, 0);
				}
			}
		}

		return $unspentTargetPercentage;
	}

	private function getTargetPercentage($idPosition, $activeLineups) {
		foreach ($activeLineups as $activeLineup) {
			foreach ($activeLineup['players'] as $activeLineupPlayer) {
				if ($activeLineupPlayer->id_position == $idPosition) {
					return $activeLineupPlayer->target_percentage;
				}
			}
		}	
	}

	private function isActiveLineup($lineupHash, $activeLineupHashes) {
		foreach ($activeLineupHashes as $key => $activeLineupHash) {
			if ($activeLineupHash == $lineupHash) {
				return true;
			}
		}

		return false;
	}

	private function sortLineups($lineups) {
		foreach ($lineups as $key => $lineup) {
			$salaries[$key] = $lineup['salary'];
			$unspentTargetPercentages[$key] = $lineup['unspent_target_percentage'];
			$biggestStacks[$key] = $lineup['biggest_stack'];
		}

		array_multisort($biggestStacks, SORT_DESC, $salaries, SORT_DESC, $unspentTargetPercentages, SORT_DESC, $lineups);

		return $lineups;
	}

	private function removeDuplicateLineups($lineups) {
		$hashes = [];

		foreach ($lineups as $lineup) {
			$hashes[] = $lineup['hash'];
		}

		$uniqueHashes = array_unique($hashes);

		$uniqueLineups = [];

		foreach ($uniqueHashes as $uniqueHash) {
			$uniqueLineups[] = $this->getLineupByHash($uniqueHash, $lineups);
		}

		return $uniqueLineups;
	}

	public function getLineupByHash($uniqueHash, $lineups) {
		foreach ($lineups as $lineup) {
			if ($uniqueHash == $lineup['hash']) {
				return $lineup;
			}
		}
	}


	/****************************************************************************************
	GENERATE LINEUP
	****************************************************************************************/

	private function generateLineup($players, $positions) {
		$lineup['players'] = [];

		# prf($players);
		# ddAll($positions);

		foreach ($positions as $position) {
			$lineup['players'][] = $this->generateRandomPlayerPerPosition($players, $position);
		}

		$lineup['players'] = $this->sortLineup($lineup['players']);

		$lineup['salary'] = 0;

		foreach ($lineup['players'] as $player) {
			$lineup['salary'] += $player->salary;
		}

		$lineup['hash'] = '';

		foreach ($lineup['players'] as $player) {
			$lineup['hash'] .= $player->mlb_player_id;
		}

		$lineup['unspent_target_percentage'] = 0;

		foreach ($lineup['players'] as $player) {
			$lineup['unspent_target_percentage'] += $player->unspent_target_percentage;
		}

		$lineup['biggest_stack'] = $this->calculateBiggestStack($lineup);
		$lineup['num_of_teams_among_hitters'] = $this->calculateNumOfTeamsAmongHitters($lineup);

		$lineup['css_class_edit_info'] = 'edit-lineup-buy-in-hidden';
		$lineup['css_class_active_lineup'] = '';
		$lineup['css_class_money_lineup'] = '';
		$lineup['add_or_remove_anchor_text'] = 'Add';

		$lineup['buy_in'] = 0;
		$lineup['buy_in_percentage'] = 0;

		$lineup['play_or_unplay_anchor_text'] = 'Play';

		# prf($lineup['salary']);
		# ddAll($lineup['players']);

		return $lineup;
	}

	private function calculateNumOfTeamsAmongHitters($lineup) {
		$teamIds = [];

		foreach ($lineup['players'] as $player) {
			if ($player->position != 'SP') {
				$teamIds[] = $player->mlb_team_id;
			}
		}

		$teamIds = array_unique($teamIds);

		return count($teamIds);
	}

	private function calculateBiggestStack($lineup) {
		$teamIds = [];

		foreach ($lineup['players'] as $player) {
			if ($player->position != 'SP') {
				$teamIds[] = $player->mlb_team_id;
			}
		}

		$stacksByTeamId = array_count_values($teamIds);

		rsort($stacksByTeamId);

		return $stacksByTeamId[0];
	}

	private function getNumberOfDuplicatePlayers($lineupPlayers) {
		$mlbPlayerIds = [];

		foreach ($lineupPlayers as $lineupPlayer) {
			$mlbPlayerIds[] = $lineupPlayer->mlb_player_id;
		}

		$countMlbPlayerIds = array_count_values($mlbPlayerIds);

		foreach ($countMlbPlayerIds as $countMlbPlayerId) {
			if ($countMlbPlayerId > 1) {
				return 1;
			}
		}

		return 0;
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

		# ddAll($lineupPlayers);

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

	private function getPositions($players) {
		$positions = [
			['name' => 'SP'],
			['name' => 'SP'],
			['name' => 'C'],
			['name' => '1B'],
			['name' => '2B'],
			['name' => '3B'],
			['name' => 'SS'],
			['name' => 'OF'],
			['name' => 'OF'],
			['name' => 'OF']
		];

		foreach ($positions as &$position) {
			$position['num_of_players'] = $this->addNumberOfPlayersPerPosition($position, $players);
		}
		unset($position);

		# ddAll($players);

		return $positions;
	}

	private function addNumberOfPlayersPerPosition($position, $players) {
		$numberOfPlayers = 0;

		foreach ($players as $player) {
			if ($player->position == $position['name']) {
				$numberOfPlayers++;
			}
		}

		return $numberOfPlayers;
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
						->select('buy_in', 'mlb_player_id', 'target_percentage', 'mlb_team_id', 'mlb_opp_team_id', 'position', 'salary', 'name', 'player_pool_id', 'abbr_dk')
						->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
						->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
						->join('mlb_teams', 'mlb_teams.id', '=', 'dk_mlb_players.mlb_team_id')
						->where('time_period', $timePeriod)
						->where('date', $date)
						->where('target_percentage', '>', 0)
						->orderBy('name', 'asc')
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
			$player->id_position = $player->mlb_player_id . $player->position;
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
			$slashPlayer[$key]->abbr_dk = $player->abbr_dk;
			$slashPlayer[$key]->position = $slashPosition;
			$slashPlayer[$key]->salary = $player->salary;
			$slashPlayer[$key]->name = $player->name;
		}

		# prf($slashPlayer);

		return array($slashPlayer[0], $slashPlayer[1]);
	}


    /****************************************************************************************
    AJAX
    ****************************************************************************************/	

	public function addLineup($playerPoolId, $hash, $totalSalary, $buyIn, $players) {
	    $lineup = new Lineup; 

	    $lineup->player_pool_id = $playerPoolId;
	    $lineup->hash = $hash;
	    $lineup->total_salary = $totalSalary; 
	    $lineup->buy_in = $buyIn;
	    $lineup->active = 1;

	    $lineup->save();    

	    foreach ($players as $player) {
	        $lineupPlayer = new LineupDkMlbPlayer;

	        $lineupPlayer->lineup_id = $lineup->id;
	        $lineupPlayer->mlb_player_id = $player['id'];
	        $lineupPlayer->position = $player['position'];

	        $lineupPlayer->save();
	    }
	}

	public function removeLineup($playerPoolId, $hash) {
	    $lineupId = DB::table('lineups')
	        ->where('player_pool_id', $playerPoolId)
	        ->where('hash', $hash)
	        ->pluck('id');

	    DB::table('lineup_dk_mlb_players')
	        ->where('lineup_id', $lineupId)
	        ->delete();

	    DB::table('lineups')
	        ->where('id', $lineupId)
	        ->delete();
	}

}