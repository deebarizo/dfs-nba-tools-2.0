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
		$stats2015 = DB::table('box_score_lines')
            ->join('games', 'box_score_lines.game_id', '=', 'games.id')
            ->join('seasons', 'games.season_id', '=', 'seasons.id')
			->join('players', 'box_score_lines.player_id', '=', 'players.id')
            ->select('*', 'box_score_lines.status as bs_status')
            ->whereRaw('players.id = '.$player_id.' AND seasons.end_year = 2015')
            ->orderBy('date', 'desc')
            ->get();

        $teams = Team::all();

        foreach ($stats2015 as &$row) {
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

        	$row->date_pm = preg_replace("/-/", "", $row->date);
        }

        unset($row);

        $name = $stats2015[0]->name;

		# ddAll($stats2015);

        return view('players', compact('stats2015', 'name'));
	}

}