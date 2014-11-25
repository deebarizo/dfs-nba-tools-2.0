<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class PlayersController {

	public function getPlayerStats($player_id) {

        // Box Score Lines

        $endYears = [2015, 2014];

        foreach ($endYears as $endYear) {
            $stats[$endYear] = DB::table('box_score_lines')
                ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                ->join('seasons', 'games.season_id', '=', 'seasons.id')
                ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                ->selectRaw('*, box_score_lines.status as bs_status, (vegas_road_team_score - vegas_home_team_score) as line')
                ->whereRaw('players.id = '.$player_id.' AND seasons.end_year = "'.$endYear.'"')
                ->orderBy('date', 'desc')
                ->get();
        }

        $teams = Team::all();

        foreach ($stats as &$year) {
        	foreach ($year as &$row) {
        		$row = $this->modStats($row, $teams);
        	}
        }
        unset($year);
        unset($row);

        // Overviews

        $statsPlayed['all'] = DB::table('box_score_lines') // All end years >= 2014
            ->join('games', 'box_score_lines.game_id', '=', 'games.id')
            ->join('seasons', 'games.season_id', '=', 'seasons.id')
            ->join('players', 'box_score_lines.player_id', '=', 'players.id')
            ->selectRaw('*, box_score_lines.status as bs_status, (vegas_road_team_score - vegas_home_team_score) as line')
            ->whereRaw('players.id = '.$player_id.' AND seasons.end_year >= 2014 AND box_score_lines.status = "Played"')
            ->orderBy('date', 'desc')
            ->get();

        foreach ($endYears as $endYear) {
            $statsPlayed[$endYear] = DB::table('box_score_lines')
                ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                ->join('seasons', 'games.season_id', '=', 'seasons.id')
                ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                ->selectRaw('*, box_score_lines.status as bs_status, (vegas_road_team_score - vegas_home_team_score) as line')
                ->whereRaw('players.id = '.$player_id.' AND seasons.end_year = "'.$endYear.'" AND box_score_lines.status = "Played"')
                ->orderBy('date', 'desc')
                ->get();
        }

        foreach ($statsPlayed as &$year) {
            foreach ($year as &$row) {
                $row = $this->modStats($row, $teams);
            }
        }
        unset($year);
        unset($row);

        foreach ($statsPlayed as $timePeriod => $boxScoreLines) {
            $gamesPlayed = count($boxScoreLines);

            if ($gamesPlayed > 0) {
                $totalMp = 0;
                $totalUsg = 0;
                $totalFp = 0;
                $totalFppm = 0;
                

                foreach ($boxScoreLines as $boxScoreLine) {
                    $totalMp += $boxScoreLine->mp;
                    $totalUsg += $boxScoreLine->usg;
                    $totalFp += $boxScoreLine->pts_fd;
                    $totalFppm += $boxScoreLine->fppm;
                }

                $overviews[$timePeriod]['mppg'] = numFormat($totalMp / $gamesPlayed);
                $overviews[$timePeriod]['usg'] = numFormat($totalUsg / $gamesPlayed);
                $overviews[$timePeriod]['fppg'] = numFormat($totalFp / $gamesPlayed);

                // CV for FPPG

                $totalSquaredDiff = 0; 

                $fppg = numFormat($totalFp / $gamesPlayed);

                foreach ($boxScoreLines as $boxScoreLine) {
                    $totalSquaredDiff += pow($boxScoreLine->pts_fd - $fppg, 2);
                }

                if ($fppg != 0) {
                    $overviews[$timePeriod]['sd'] = numFormat(sqrt($totalSquaredDiff / $gamesPlayed));
                    $overviews[$timePeriod]['cv'] = numFormat(($overviews[$timePeriod]['sd'] / $fppg) * 100);
                } else {
                    $overviews[$timePeriod]['sd'] = numFormat(0);
                    $overviews[$timePeriod]['cv'] = numFormat(0);
                } 

                // CV for FPPM

                $totalSquaredDiff = 0; 

                $overviews[$timePeriod]['fppm'] = numFormat($totalFppm / $gamesPlayed);
                $fppm = $overviews[$timePeriod]['fppm'];

                foreach ($boxScoreLines as $boxScoreLine) {
                    $totalSquaredDiff += pow($boxScoreLine->fppm - $fppm, 2);
                }                

                if ($fppm != 0) {
                    $overviews[$timePeriod]['sd_fppm'] = numFormat(sqrt($totalSquaredDiff / $gamesPlayed));
                    $overviews[$timePeriod]['cv_fppm'] = numFormat(($overviews[$timePeriod]['sd_fppm'] / $fppm) * 100);
                } else {
                    $overviews[$timePeriod]['sd_fppm'] = numFormat(0);
                    $overviews[$timePeriod]['cv_fppm'] = numFormat(0);
                }    
            }
        }

        // Player Filter

        $player = new Player;

        $dailyFdFilters = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                         JOIN (
                                            SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                         ) AS t2
                                         ON t1.player_id = t2.player_id AND t1.created_at = t2.latest');

        foreach ($dailyFdFilters as $filter) {
            if ($statsPlayed['all'][0]->player_id == $filter->player_id) {
                $player->filter = $filter;

                break;
            }
        }

        // Player Info

        $playerInfo['name'] = $statsPlayed['all'][0]->name;
        $playerInfo['player_id'] = $statsPlayed['all'][0]->player_id;

        # ddAll($stats);

        return view('players', compact('stats', 'overviews', 'playerInfo', 'player'));
	}

	private function modStats($row, $teams) {
    	foreach ($teams as $team) {
    		if ($row->home_team_id == $team->id) {
    			$row->home_team_abbr_br = $team->abbr_br;
    			$row->home_team_abbr_pm = $team->abbr_pm;
    		}

    		if ($row->road_team_id == $team->id) {
    			$row->road_team_abbr_br = $team->abbr_br;
    			$row->road_team_abbr_pm = $team->abbr_pm;
    		}
    	}

    	$row->pts_fd = $row->pts + 
    				   ($row->trb * 1.2) +
    				   ($row->ast * 1.5) +
    				   ($row->stl * 2) +
    				   ($row->blk * 2) +
    				   ($row->tov * -1);
        $row->pts_fd = number_format(round($row->pts_fd, 2), 2);

        if ($row->mp != 0) {
            $row->fppm = $row->pts_fd / $row->mp;
        } else {
            $row->fppm = 0;
        }

    	$row->date_pm = preg_replace("/-/", "", $row->date);

        # ddAll($row);

    	return $row;
	}

}