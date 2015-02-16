<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;

use App\Classes\StatBuilder;

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

        // Player fpts profile

        $fptsProfile['all'] = DB::table('seasons')
                ->join('games', 'games.season_id', '=', 'seasons.id')
                ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                ->join('players', 'players.id', '=', 'box_score_lines.player_id')
                ->selectRaw('FORMAT(SUM(pts) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as pts, 
                    FORMAT(SUM((fg - threep) * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as 2p,
                    FORMAT(SUM((threep) * 3) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as 3p,
                    FORMAT(SUM(ft) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as ft,
                    FORMAT(SUM(trb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as trb,
                    FORMAT(SUM(orb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as orb,
                    FORMAT(SUM(drb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as drb,
                    FORMAT(SUM(ast * 1.5) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as ast,
                    FORMAT((SUM(tov) * -1) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as tov,
                    FORMAT(SUM(stl * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as stl,
                    FORMAT(SUM(blk * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as blk')
                ->where('player_id', '=', $player_id)
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '>=', $endYears[1])
                ->first();

        foreach ($endYears as $endYear) {
            $fptsProfile[$endYear] = DB::table('seasons')
                    ->join('games', 'games.season_id', '=', 'seasons.id')
                    ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                    ->join('players', 'players.id', '=', 'box_score_lines.player_id')
                    ->selectRaw('FORMAT(SUM(pts) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as pts, 
                        FORMAT(SUM((fg - threep) * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as 2p,
                        FORMAT(SUM((threep) * 3) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as 3p,
                        FORMAT(SUM(ft) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as ft,
                        FORMAT(SUM(trb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as trb,
                        FORMAT(SUM(orb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as orb,
                        FORMAT(SUM(drb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as drb,
                        FORMAT(SUM(ast * 1.5) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as ast,
                        FORMAT((SUM(tov) * -1) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as tov,
                        FORMAT(SUM(stl * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as stl,
                        FORMAT(SUM(blk * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * 100, 2) as blk')
                    ->where('player_id', '=', $player_id)
                    ->where('box_score_lines.status', '=', 'Played')
                    ->where('seasons.end_year', '=', $endYear)
                    ->first();
        }

        $fptsProfile['view'] = [];

        foreach ($fptsProfile[$endYears[0]] as $stat) {
            $fptsProfile['view'][] = (float)$stat;
        } 

        # ddAll($fptsProfile);

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

        $teams = Team::all();

        $statBuilder = new StatBuilder;

        foreach ($endYears as $endYear) {
            $boxScoreLines[$endYear] = DB::table('games')
                    ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                    ->join('seasons', 'games.season_id', '=', 'seasons.id')
                    ->join('players', 'box_score_lines.player_id', '=', 'players.id')
                    ->join('teams', 'teams.id', '=', 'box_score_lines.team_id')
                    ->selectRaw('games.date as date, box_score_lines.team_id, abbr_br as team_of_player, home_team_id, home_team_score, road_team_id, road_team_score, vegas_home_team_score, vegas_road_team_score, link_br, DATE_FORMAT(games.date, "%Y%m%d") as date_pm, role, mp, ot_periods, fg, fga, threep, threepa, ft, fta, orb, drb, trb, ast, blk, stl, pf, tov, pts, usg, pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov as fdpts, (pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) / mp as fdppm, status')
                    ->where('box_score_lines.player_id', '=', $player_id)
                    ->where('seasons.end_year', '=', $endYear)
                    ->orderBy('games.date', 'desc')
                    ->get();

            foreach ($boxScoreLines[$endYear] as $boxScoreLine) {
                if ($boxScoreLine->team_id == $boxScoreLine->home_team_id) {
                    $oppTeamId = $boxScoreLine->road_team_id;
                    
                    $boxScoreLine->is_road_game = '';

                    $boxScoreLine->game_score = $statBuilder->createGameScore($boxScoreLine->home_team_score, $boxScoreLine->road_team_score);

                    $boxScoreLine->line = $statBuilder->createLine($boxScoreLine->vegas_home_team_score, $boxScoreLine->vegas_road_team_score);
                } else {
                    $oppTeamId = $boxScoreLine->home_team_id;
                    
                    $boxScoreLine->is_road_game = '@';

                    $boxScoreLine->game_score = $statBuilder->createGameScore($boxScoreLine->road_team_score, $boxScoreLine->home_team_score);

                    $boxScoreLine->line = $statBuilder->createLine($boxScoreLine->vegas_road_team_score, $boxScoreLine->vegas_home_team_score);
                }

                $boxScoreLine->opp_team = $statBuilder->getTeamAbbrBr($oppTeamId, $teams);

                $boxScoreLine->home_team_abbr_pm = $statBuilder->getTeamAbbrPm($boxScoreLine->home_team_id, $teams);
                $boxScoreLine->road_team_abbr_pm = $statBuilder->getTeamAbbrPm($boxScoreLine->road_team_id, $teams);

            } unset($boxScoreLine);
        }

        # ddAll($boxScoreLines);

        // Current Player Filter

        $player = new Player;

        $dailyFdFilters = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                         JOIN (
                                            SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                         ) AS t2
                                         ON t1.player_id = t2.player_id AND t1.created_at = t2.latest');

        foreach ($dailyFdFilters as $filter) {
            if ($player_id == $filter->player_id) {
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

        // Player Metadata

        $playerMetadata = Player::where('id', '=', $player_id)->first();

        $name = $playerMetadata->name;

        $playerInfo['player_id'] = $player_id;

        # ddAll($boxScoreLines);

        return view('players', compact('boxScoreLines', 'overviews', 'playerInfo', 'player', 'name', 'previousFdFilters', 'fptsProfile', 'endYears'));
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