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
            ->select('*')
            ->whereRaw('players.id = '.$player_id.' AND seasons.end_year = 2015')
            ->orderBy('date', 'desc')
            ->get();

        $teams = Team::all();

        foreach ($stats2015 as &$row) {
        	foreach ($teams as $team) {
        		if ($row->home_team_id == $team->id) {
        			$row->home_team_abbr = $team->abbr_br;
        		}

        		if ($row->road_team_id == $team->id) {
        			$row->road_team_abbr = $team->abbr_br;
        		}
        	}
        }

        unset($row);

		ddAll($stats2015);

        return view('players', compact('stats2015'));
	}

}