<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Response;

date_default_timezone_set('America/Chicago');

class PlayersController {

    public function getPlayerNameAutocomplete(Request $request) {
        $formInput = $request->input('term');
        $formInput = Str::lower($formInput);

        $players = Player::all();

        $result = [];

        foreach ($players as $player) {
            if ( strpos(Str::lower($player->name), $formInput ) !== false) {
                $playerUrl = url().'/players/'.$player->id;

                $result[] = [
                    'value' => $player->name,
                    'url' => $playerUrl
                ]; 
            }
        }

        return Response::json($result);
    }

	public function getPlayerStats($player_id) {

        $endYears = [2015, 2014];

        // MPG, FPPG, FPPM

        $overviews['all']['mppg'] = DB::table('box_score_lines')
                ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                ->join('seasons', 'games.season_id', '=', 'seasons.id')
                ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                ->selectRaw('AVG(mp) as mppg')
                ->where('player_id', '=', $player_id)
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '>=', $endYears[1])
                ->pluck('mppg');

        $overviews['all']['fppg'] = DB::table('box_score_lines')
                ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                ->join('seasons', 'games.season_id', '=', 'seasons.id')
                ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                ->selectRaw('AVG(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as fppg')
                ->where('player_id', '=', $player_id)
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '>=', $endYears[1])
                ->pluck('fppg'); 

        $overviews['all']['fppm'] = DB::table('box_score_lines')
                ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                ->join('seasons', 'games.season_id', '=', 'seasons.id')
                ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                ->selectRaw('SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) / SUM(mp) as fppm')
                ->where('player_id', '=', $player_id)
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '>=', $endYears[1])
                ->pluck('fppm'); 

        foreach ($endYears as $endYear) {
            $overviews[$endYear]['mppg'] = DB::table('box_score_lines')
                    ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                    ->join('seasons', 'games.season_id', '=', 'seasons.id')
                    ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                    ->selectRaw('AVG(mp) as mppg')
                    ->where('player_id', '=', $player_id)
                    ->where('box_score_lines.status', '=', 'Played')
                    ->where('seasons.end_year', '=', $endYear)
                    ->pluck('mppg');

            $overviews[$endYear]['fppg'] = DB::table('box_score_lines')
                    ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                    ->join('seasons', 'games.season_id', '=', 'seasons.id')
                    ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                    ->selectRaw('AVG(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as fppg')
                    ->where('player_id', '=', $player_id)
                    ->where('box_score_lines.status', '=', 'Played')
                    ->where('seasons.end_year', '=', $endYear)
                    ->pluck('fppg');

            $overviews[$endYear]['fppm'] = DB::table('box_score_lines')
                    ->join('games', 'box_score_lines.game_id', '=', 'games.id')
                    ->join('seasons', 'games.season_id', '=', 'seasons.id')
                    ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                    ->selectRaw('SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) / SUM(mp) as fppm')
                    ->where('player_id', '=', $player_id)
                    ->where('box_score_lines.status', '=', 'Played')
                    ->where('seasons.end_year', '=', $endYear)
                    ->pluck('fppm'); 
        }

        # ddAll($overviews);

        // Box Score Lines

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
        $playersFd = DB::table('players_fd')
            ->join('player_pools', 'players_fd.player_pool_id', '=', 'player_pools.id')
            ->select('*')
            ->get();

        foreach ($stats as &$year) {
        	foreach ($year as &$row) {
        		$row = $this->modStats($row, $teams, $playersFd);
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
                $row = $this->modStats($row, $teams, $playersFd);
            }
        } unset($year); unset($row);

        // Current Player Filter

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

        // Previous Player Filters

        $previousFdFilters = DB::table('daily_fd_filters')
            ->select('*')
            ->where('player_id', '=', $player_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $previousFdFilters = array_slice($previousFdFilters, 1, 5);

        // Player Info

        $playerInfo['name'] = $statsPlayed['all'][0]->name;
        $playerInfo['player_id'] = $statsPlayed['all'][0]->player_id;

        $name = $playerInfo['name'];

        # ddAll($stats);

        return view('players', compact('stats', 'overviews', 'playerInfo', 'player', 'name', 'previousFdFilters'));
	}

	private function modStats($row, $teams, $playersFd) {
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

        foreach ($playersFd as $playerFd) {
            if ($row->player_id == $playerFd->player_id && $row->date == $playerFd->date) {
                $row->salary = $playerFd->salary;
                $row->vr = numFormat($row->pts_fd / $row->salary * 1000);

                break;
            }
        }

        if (!isset($row->salary)) {
            $row->salary = 'N/A';
            $row->vr = 'N/A';
        }

    	$row->date_pm = preg_replace("/-/", "", $row->date);

        # ddAll($row);

    	return $row;
	}

}