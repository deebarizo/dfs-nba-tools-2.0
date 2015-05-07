<?php namespace App\Http\Controllers;

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

class OneOfController {

	public function run() {
        $lastTenContests = DB::table('dk_mlb_contests')
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
									->where('dk_mlb_contests.name', 'LIKE', '%$5 DOUBLE UP%')
                                    ->get();

        # ddAll($lastTenContests);

        foreach ($lastTenContests as $contest) {
        	$contest->players = DB::table('dk_mlb_contests')
				                  ->select(DB::raw('mlb_players.name as player_name,
					                  				dk_mlb_contest_lineup_players.dk_mlb_player_id, 
				                  					dk_mlb_contest_lineup_players.mlb_player_id, 
				                  					format(count(dk_mlb_contest_lineup_players.dk_mlb_player_id) / '.$contest->num_of_lineups.' * 100, 1) as ownership'))
				                  ->join('dk_mlb_contest_lineups', 'dk_mlb_contest_lineups.dk_mlb_contest_id', '=', 'dk_mlb_contests.id')
				                  ->join('dk_mlb_contest_lineup_players', 'dk_mlb_contest_lineup_players.dk_mlb_contest_lineup_id', '=', 'dk_mlb_contest_lineups.id')
				                  ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_contest_lineup_players.mlb_player_id')
				                  ->groupBy('dk_mlb_contest_lineup_players.dk_mlb_player_id')
				                  ->where('dk_mlb_contests.id', '=', $contest->id)
				                  ->having('ownership', '>=', 10)
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

			foreach ($contest->players as $player) {
				foreach ($contestMlbPlayers as $contestMlbPlayer) {
					if ($contestMlbPlayer->mlb_player_id == $player->mlb_player_id) {
						$player->total_ownership = $contestMlbPlayer->total_ownership;
						break;
					}
				}

				$player->other_ownership = $player->total_ownership - $player->ownership;
			}

			$dkMlbPlayers = DB::table('player_pools')
							  ->select('dk_mlb_players.id as dk_mlb_player_id',
							  			'dk_mlb_players.position', 
							  			'dk_mlb_players.salary')
			                  ->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
			                  ->where('player_pools.id', '=', $contest->player_pool_id)
			                  ->get();

			foreach ($contest->players as $player) {
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

		ddAll($lastTenContests);		
	}

}