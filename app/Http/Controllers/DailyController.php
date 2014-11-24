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
/*
        set_time_limit(0);

        $client = new Client;

        $vegasScores = scrapeForOdds($client, $date);

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

        $teamFilters = DB::select('SELECT t1.* FROM team_filters AS t1
                                         JOIN (
                                            SELECT team_id, MAX(created_at) AS latest FROM team_filters GROUP BY team_id
                                         ) AS t2
                                         ON t1.team_id = t2.team_id AND t1.created_at = t2.latest');

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
*/
        // fetch box score lines up to the date for each player

        $endDate = $date;

        foreach ($players as $player) {
            if (isset($player->filter)) {
                if ($player->filter->filter == 1 && $player->filter->fppg_source == 'fp cs') {
                    $playerStats[$player->player_id]['cs'] = getBoxScoreLinesForPlayer($startingSeasonId = 11, $player->player_id, $endDate);

                    continue;
                }
            }

            $playerStats[$player->player_id]['all'] = getBoxScoreLinesForPlayer($startingSeasonId = 10, $player->player_id, $endDate);
        }

        // calculate stats

        foreach ($players as &$player) {
            if ( !isset($player->filter) !! ( isset($player->filter) && $player->filter->filter == 0) ) {

                calculateCV($player);

                $gamesPlayed = count($playerStats[$player->player_id]['all']);

                // CV for Fppg

                $totalFp = 0;

                foreach ($playerStats[$player->player_id] as $gameLog) {
                    $totalFp += $gameLog->fd_score;
                }

                if ($gamesPlayed > 0) {
                    $player->fppg = numFormat($totalFp / $gamesPlayed);
                    # $player->fppgWithVegasFilter = numFormat(($player->fppg * $player->vegas_filter) + $player->fppg);
                } else {
                    $player->fppg = numFormat(0, 2);
                    $player->fppgWithVegasFilter = numFormat(0, 2);
                }

                $totalSquaredDiff = 0; // For SD

                foreach ($playerStats[$player->player_id] as $gameLog) {
                    $totalSquaredDiff = $totalSquaredDiff + pow($gameLog->fd_score - $player->fppg, 2);
                }

                if ($player->fppg != 0) {
                    $player->sd = sqrt($totalSquaredDiff / $gamesPlayed);
                    $player->cv = number_format(round(($player->sd / $player->fppg) * 100, 2), 2);
                } else {
                    $player->sd = number_format(0, 2);
                    $player->cv = number_format(0, 2);
                }

                // CV for Fppm

                $totalFppm = 0;

                foreach ($playerStats[$player->player_id] as $gameLog) {
                    $totalFppm += $gameLog->fppm;
                }

                if ($gamesPlayed > 0) {
                    $player->fppmPerGame = numFormat($totalFppm / $gamesPlayed);
                    # $player->fppmPerGameWithVegasFilter = numFormat(($player->fppmPerGame * $player->vegas_filter) + $player->fppmPerGame);
                } else {
                    $player->fppmPerGame = number_format(0, 2);
                    $player->fppmPerGameWithVegasFilter = number_format(0, 2);
                }

                $totalSquaredDiff = 0; // For SD

                foreach ($playerStats[$player->player_id] as $gameLog) {
                    $totalSquaredDiff = $totalSquaredDiff + pow($gameLog->fppm - $player->fppmPerGame, 2);
                }

                if ($player->fppmPerGame != 0) {
                    $player->sdFppm = sqrt($totalSquaredDiff / $gamesPlayed);
                    $player->cvFppm = number_format(round(($player->sdFppm / $player->fppmPerGame) * 100, 2), 2);
                } else {
                    $player->sdFppm = 0;
                    $player->cvFppm = number_format(0, 2);
                }                
            }

            if (isset($player->filter)) {
                if ($player->filter->filter == 1 && $player->filter->fppg_source[0] == 'm' && $player->filter->fppg_source[1] == 'p') {
                    if ($player->filter->fppg_source == 'mp cs') {
                        if (is_null($player->filter->fppm_source)) {
                            $mpCs = calculateMpCs($playerStats[$player->player_id], $date);

                            $player->fppg = $mpCs * $player->fppmPerGame;
                            # $player->fppgWithVegasFilter = numFormat(($player->fppg * $player->vegas_filter) + $player->fppg);
                        }
                    }
                }
            }
        }   

        unset($player);

        foreach ($players as &$player) {
            # $player->vr = $player->fppgWithVegasFilter / ($player->salary / 1000);

            # $player->vr_minus_1sd = ($player->fppgWithVegasFilter - $player->sd) / ($player->salary / 1000);

            # $player->fppg_minus_1sd = $player->fppgWithVegasFilter - $player->sd;

            # $player->fppm_minus_1sd = $player->fppmPerGameWithVegasFilter - $player->sdFppm;
        }

        unset($player);

        ddAll($playerStats);

        // fetch DFS time period (example: all day, early, late)

        $timePeriod = $players[0]->time_period;

		return view('daily_fd_nba', compact('date', 'timePeriod', 'players'));
	}

}