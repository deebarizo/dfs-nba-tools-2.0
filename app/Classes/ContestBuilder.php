<?php namespace App\Classes;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;
use App\Models\MlbGame;
use App\Models\MlbGameLine;
use App\Models\MlbBoxScoreLine;
use App\Models\DkMlbContest;
use App\Models\DkMlbContestLineup;
use App\Models\DkMlbContestLineupPlayer;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class ContestBuilder {

	public function getOwnershipsOver($ownershipPercentage) {
        $contests = DB::table('dk_mlb_contests')
									->select(DB::raw('dk_mlb_contests.id as id, 
														dk_mlb_contests.name as name, 
														dk_mlb_contests.player_pool_id,
														dk_mlb_contests.entry_fee,
														dk_mlb_contests.time_period,
														count(*) as num_of_lineups, 
														date'))
                                    ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
									->groupBy('date')
									->orderBy('date', 'desc')
									->where('dk_mlb_contests.name', 'NOT LIKE', '%DOUBLE UP%')
                                    ->get();	

        foreach ($contests as $contest) {
        	$contest->players = DB::table('dk_mlb_contests')
				                  ->select(DB::raw('mlb_players.name as name,
					                  				dk_mlb_contest_lineup_players.dk_mlb_player_id, 
				                  					dk_mlb_contest_lineup_players.mlb_player_id, 
				                  					format(count(dk_mlb_contest_lineup_players.dk_mlb_player_id) / '.$contest->num_of_lineups.' * 100, 1) as ownership'))
				                  ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
				                  ->join('dk_mlb_contest_lineup_players', 'dk_mlb_contest_lineup_players.dk_mlb_contest_lineup_id', '=', 'dk_mlb_contest_lineups.id')
				                  ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->where('dk_mlb_contests.id', '=', $contest->id)
				                  ->groupBy('dk_mlb_contest_lineup_players.dk_mlb_player_id')
				                  ->get();

        	$contestMlbPlayers = DB::table('dk_mlb_contests')
				                  ->select(DB::raw('mlb_players.name as player_name,
				                  					dk_mlb_contest_lineup_players.dk_mlb_player_id,
				                  					dk_mlb_contest_lineup_players.mlb_player_id, 
				                  					format(count(dk_mlb_contest_lineup_players.mlb_player_id) / '.$contest->num_of_lineups.' * 100, 1) as total_ownership'))
				                  ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
				                  ->join('dk_mlb_contest_lineup_players', 'dk_mlb_contest_lineup_players.dk_mlb_contest_lineup_id', '=', 'dk_mlb_contest_lineups.id')
				                  ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->groupBy('dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->where('dk_mlb_contests.id', '=', $contest->id)
				                  ->get();		

			# ddAll($contestMlbPlayers);

			foreach ($contest->players as $key => $player) {
				foreach ($contestMlbPlayers as $contestMlbPlayer) {
					if ($contestMlbPlayer->mlb_player_id == $player->mlb_player_id) {
						if ($contestMlbPlayer->total_ownership >= $ownershipPercentage) {
							$player->total_ownership = $contestMlbPlayer->total_ownership;
							$player->other_ownership = numFormat($player->total_ownership - $player->ownership, 1);
						} else {
							unset($contest->players[$key]);
						}
	
						break;
					}
				}
			}

			$dkMlbPlayers = DB::table('player_pools')
							  ->select('dk_mlb_players.id as dk_mlb_player_id',
							  			'dk_mlb_players.position', 
							  			'dk_mlb_players.salary')
			                  ->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
			                  ->where('player_pools.id', '=', $contest->player_pool_id)
			                  ->get();

			foreach ($contest->players as $key => $player) {
				foreach ($dkMlbPlayers as $dkMlbPlayer) {
					if ($dkMlbPlayer->dk_mlb_player_id == $player->dk_mlb_player_id) {
						$player->position = $dkMlbPlayer->position;
						$player->salary = $dkMlbPlayer->salary;

						break;
					}
				}
			}

			# ddAll($dkMlbPlayers);
        }	

        # ddAll($contests);

        return $contests;
	}

	public function getOwnerships($site, $sport, $contestTypeInUrl, $numOfContests) {
		switch ($contestTypeInUrl) {
			case '5du':
				$contestType = '%$5 DOUBLE UP%';
				break;

			case '3moonshot':
				$contestType = '%MOONSHOT%';
				break;
			
			default:
				echo 'Error: invalid contest type'; exit();
				break;
		}

        $contests = DB::table('dk_mlb_contests')
									->select(DB::raw('dk_mlb_contests.id as id, 
														dk_mlb_contests.name as name, 
														dk_mlb_contests.player_pool_id,
														dk_mlb_contests.entry_fee,
														dk_mlb_contests.time_period,
														count(*) as num_of_lineups, 
														date'))
                                    ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
									->groupBy('date')
									->orderBy('date', 'desc')
									->where('date', '>=', '2015-05-04')
									->where('dk_mlb_contests.name', 'LIKE', $contestType)
									->take($numOfContests)
                                    ->get();

        foreach ($contests as $contest) {
        	$contest->players = DB::table('dk_mlb_contests')
				                  ->select(DB::raw('mlb_players.name as name,
					                  				dk_mlb_contest_lineup_players.dk_mlb_player_id, 
				                  					dk_mlb_contest_lineup_players.mlb_player_id, 
				                  					format(count(dk_mlb_contest_lineup_players.dk_mlb_player_id) / '.$contest->num_of_lineups.' * 100, 1) as ownership'))
				                  ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
				                  ->join('dk_mlb_contest_lineup_players', 'dk_mlb_contest_lineup_players.dk_mlb_contest_lineup_id', '=', 'dk_mlb_contest_lineups.id')
				                  ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->where('dk_mlb_contests.id', '=', $contest->id)
				                  ->groupBy('dk_mlb_contest_lineup_players.dk_mlb_player_id')
				                  ->get();

        	$contestMlbPlayers = DB::table('dk_mlb_contests')
				                  ->select(DB::raw('mlb_players.name as player_name,
				                  					dk_mlb_contest_lineup_players.dk_mlb_player_id,
				                  					dk_mlb_contest_lineup_players.mlb_player_id, 
				                  					format(count(dk_mlb_contest_lineup_players.mlb_player_id) / '.$contest->num_of_lineups.' * 100, 1) as total_ownership'))
				                  ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
				                  ->join('dk_mlb_contest_lineup_players', 'dk_mlb_contest_lineup_players.dk_mlb_contest_lineup_id', '=', 'dk_mlb_contest_lineups.id')
				                  ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->groupBy('dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->where('dk_mlb_contests.id', '=', $contest->id)
				                  ->get();		

			# ddAll($contestMlbPlayers);

			foreach ($contest->players as $key => $player) {
				foreach ($contestMlbPlayers as $contestMlbPlayer) {
					if ($contestMlbPlayer->mlb_player_id == $player->mlb_player_id) {
						if ($contestMlbPlayer->total_ownership >= 10) {
							$player->total_ownership = $contestMlbPlayer->total_ownership;
							$player->other_ownership = numFormat($player->total_ownership - $player->ownership, 1);
						} else {
							unset($contest->players[$key]);
						}
	
						break;
					}
				}
			}

			$dkMlbPlayers = DB::table('player_pools')
							  ->select('dk_mlb_players.id as dk_mlb_player_id',
							  			'dk_mlb_players.position', 
							  			'dk_mlb_players.salary')
			                  ->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
			                  ->where('player_pools.id', '=', $contest->player_pool_id)
			                  ->get();

			foreach ($contest->players as $key => $player) {
				foreach ($dkMlbPlayers as $dkMlbPlayer) {
					if ($dkMlbPlayer->dk_mlb_player_id == $player->dk_mlb_player_id) {
						$player->position = $dkMlbPlayer->position;
						$player->salary = $dkMlbPlayer->salary;

						break;
					}
				}
			}

			# ddAll($dkMlbPlayers);
        }

		return $contests;	
	}

}