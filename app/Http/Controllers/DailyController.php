<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;
use App\DailyFdFilter;
use App\TeamFilter;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyController {

	public function daily_fd_nba($date = 'today') {
		if ($date == 'today') {
			$date = date('Y-m-d', time());
		}

        // fetch all players for the date

		$players = DB::table('player_pools')
            ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
            ->join('players', 'players_fd.player_id', '=', 'players.id')
            ->select('*')
            ->whereRaw('player_pools.date = "'.$date.'"')
            ->get();	

        if (empty($players)) {
            $message = 'Please scrape FD and BR.';
            Session::flash('alert', 'info');

            return view('daily_fd_nba')->with('message', $message);                
        }

        // fetch DFS time period (example: all day, early, late)

        $timePeriod = $players[0]->time_period;

        // match each player to a team id

        $teams = Team::all();

        foreach ($players as &$player) {
            foreach ($teams as $team) {
                if ($player->team_id == $team->id) {
                    $player->team_name = $team->name_br;
                    $player->team_abbr = $team->abbr_br;
                }

                if ($player->opp_team_id == $team->id) {
                    $player->opp_team_name = $team->name_br;
                    $player->opp_team_abbr = $team->abbr_br;
                }

                if (isset($player->team_name) && isset($player->opp_team_name)) {
                    break;
                }
            }
        }

        unset($player);

        // fetch player filters

        $dailyFdFilters = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                         JOIN (
                                            SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                         ) AS t2
                                         ON t1.player_id = t2.player_id AND t1.created_at = t2.latest');

        foreach ($players as &$player) {
            foreach ($dailyFdFilters as $filter) {
                if ($player->player_id == $filter->player_id) {
                    $player->filter = $filter;

                    break;
                }
            }
        }

        unset($player);

        // fetch vegas scores

        set_time_limit(0);

        $client = new Client;

        $vegasScores = scrapeForOdds($client, $date);

        if ($vegasScores != 'No lines yet.') {
            foreach ($players as &$player) {
                foreach ($vegasScores as $vegasScore) {
                    if ($player->team_name == $vegasScore['team']) {
                        $player->vegas_score_team = number_format(round($vegasScore['score'], 2), 2);
                    }

                    if ($player->opp_team_name == $vegasScore['team']) {
                        $player->vegas_score_opp_team = number_format(round($vegasScore['score'], 2), 2);
                    }                
                }

                if (isset($player->vegas_score_team) === false || isset($player->vegas_score_opp_team) === false) {
                    echo 'error: no team match in SAO<br>';
                    echo $player->team_name.' vs '.$player->opp_team_name;
                    exit();
                }
            }

            unset($player);

            // fetch team filters and calculate vegas filter

            $gamesInCurrentSeason = Game::where('season_id', '=', 11)->get();

            foreach ($teams as $key => $team) {
                $gamesCount = 0;
                $totalPoints = 0;

                foreach ($gamesInCurrentSeason as $game) {
                    if (strtotime($game->date) < strtotime($date)) {
                        if ($game->home_team_id == $team->id) {
                            $gamesCount++;
                            $totalPoints += $game->home_team_score;

                            continue;
                        }

                        if ($game->road_team_id == $team->id) {
                            $gamesCount++;
                            $totalPoints += $game->road_team_score;

                            continue;
                        }
                    }
                }

                $teamPPG = $totalPoints / $gamesCount;

                $teamFilters[$key] = new \stdClass();
                $teamFilters[$key]->team_id = $team->id;
                $teamFilters[$key]->ppg = $teamPPG;
            }

            # ddAll($teamFilters);

            /*
            $teamFilters = DB::select('SELECT t1.* FROM team_filters AS t1
                                             JOIN (
                                                SELECT team_id, MAX(created_at) AS latest FROM team_filters GROUP BY team_id
                                             ) AS t2
                                             ON t1.team_id = t2.team_id AND t1.created_at = t2.latest');
            */

            foreach ($players as &$player) {
                foreach ($teamFilters as $teamFilter) {
                    if ($player->team_id == $teamFilter->team_id) {
                        $player->team_ppg = $teamFilter->ppg;

                        $player->vegas_filter = ($player->vegas_score_team - $player->team_ppg) / $player->team_ppg;

                        break;
                    }
                }
            }

            unset($player); 

            $areThereVegasScores = true;       
        }

        if ($vegasScores == 'No lines yet.') {
            foreach ($players as &$player) {   
                $player->vegas_filter = 0;
            }

            $areThereVegasScores = false;
        }

        // fetch box score lines up to the date for each player

        $endDate = $date;

        foreach ($players as $player) {
            if (isset($player->filter)) {
                if ($player->filter->filter == 1) {
                    $playerStats[$player->player_id]['cs'] = getBoxScoreLinesForPlayer(11, $player->player_id, $endDate);
                }
            }

            $playerStats[$player->player_id]['all'] = getBoxScoreLinesForPlayer(10, $player->player_id, $endDate);
        }

        // calculate stats

        foreach ($players as &$player) {
            if (isset($player->filter)) {
                if ($player->filter->filter == 1) {
                    if ($player->filter->fppg_source == 'fp cs') {
                        $player = calculateFppg($player, $playerStats[$player->player_id]['cs']);
                    }                    

                    if ($player->filter->cv_source == 'cs') {
                        $player = calculateCvForFppg($player, $playerStats[$player->player_id]['cs']);
                    }

                    if ($player->filter->cv_source == 'fppm cs') {
                        $player = calculateCvForFppm($player, $playerStats[$player->player_id]['cs']);
                    }

                    if ($player->filter->fppg_source == 'mp cs') {
                        $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['cs'], $date, $player->filter->mp_ot_filter);

                        if ($player->filter->fppm_source == 'cs') {
                            $player = calculateFppm($player, $playerStats[$player->player_id]['cs']);
                        }
                    }

                    if (is_numeric($player->filter->fppg_source))  {
                        $player->mp_mod = $player->filter->fppg_source;
                    }

                    if (is_numeric($player->filter->cv_source)) {
                        $player->cv = $player->filter->cv_source;
                    }
                } 
            }

            // FPPG
            if ( !isset($player->fppgWithVegasFilter) ) {
                $player = calculateFppg($player, $playerStats[$player->player_id]['all']);
            }

            // FPPM

            if ( !isset($player->fppmPerGameWithVegasFilter) ) {
                $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
            }

            if ( isset($player->filter->fppm_source) ) {
                if ($player->filter->fppm_source == 'cs') {
                    $player = calculateFppm($player, $playerStats[$player->player_id]['cs']);
                } 

                if ( is_numeric($player->filter->fppm_source) ) {
                    $player->fppmPerGame = $player->filter->fppm_source;
                    $player->fppmPerGameWithVegasFilter = numFormat(($player->fppmPerGame * $player->vegas_filter) + $player->fppmPerGame);               

                    if ( is_null($player->filter->fppg_source) ) {
                        $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], $date, $player->filter->mp_ot_filter);
                    }
                }
            }

            // CV

            if ( !isset($player->cv) ) {
                $player = calculateCvForFppg($player, $playerStats[$player->player_id]['all']);
            }

            // MP MOD

            if (isset($player->mp_mod)) {
                $player->fppg = $player->mp_mod * $player->fppmPerGame;
                $player->fppgWithVegasFilter = numFormat(($player->fppg * $player->vegas_filter) + $player->fppg);                
            }
        }   

        unset($player);

        foreach ($players as &$player) {
            $player->vr = numFormat( $player->fppgWithVegasFilter / ($player->salary / 1000) );

            $player->vr_minus_1sd = numFormat( ($player->fppgWithVegasFilter - ($player->fppgWithVegasFilter * ($player->cv / 100) )  ) / ($player->salary / 1000) );

            $player->fppgMinus1WithVegasFilter = numFormat($player->vr_minus_1sd * ($player->salary / 1000), 2);
        }

        unset($player);

        # ddAll($players);

        // update database table

        $dbPlayers = DB::table('player_pools')
            ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
            ->join('players', 'players_fd.player_id', '=', 'players.id')
            ->select('*')
            ->whereRaw('player_pools.date = "'.$date.'"')
            ->get();    

        foreach ($players as $player) {
            foreach ($dbPlayers as $dbPlayer) {
                if ($player->player_id == $dbPlayer->player_id) {
                    if ($player->vr_minus_1sd != $dbPlayer->vr_minus1) {
                        DB::table('players_fd')
                            ->whereRaw('player_id = '.$player->player_id.' AND player_pool_id = '.$player->player_pool_id)
                            ->update(array('vr_minus1' => $player->vr_minus_1sd, 'fppg_minus1' => $player->fppgMinus1WithVegasFilter));

                        break;
                    }
                }
            }      
        }

        // remove players that are not playing or DTD

        foreach ($players as $key => $player) {
            if (isset($player->filter)) {
                if (isset($player->filter->playing) && $player->filter->playing == 0) {
                    unset($players[$key]);
                    continue;
                }

                if (isset($player->filter->notes) && $player->filter->notes == 'DTD') {
                    $dtdPlayers[] = $player;

                    unset($players[$key]);
                    continue;                    
                }           
            }
        }

        // order DTD players array by team

        if (isset($dtdPlayers)) {
            foreach ($dtdPlayers as $key => $row) {
	            $teamId[$key]  = $row->team_id;
	        }   

	        array_multisort($teamId, SORT_ASC, $dtdPlayers); 	
        } else {
        	$dtdPlayers = [];
        }

		# ddAll($players);

		return view('daily_fd_nba', compact('date', 'timePeriod', 'players', 'dtdPlayers'));
	}

}