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

    public function getNbaPlayerNameAutocomplete(Request $request) {
        $formInput = $request->input('term');
        $formInput = Str::lower($formInput);

        $players = Player::all();

        $result = [];

        foreach ($players as $player) {
            if (strpos(Str::lower($player->name), $formInput) !== false) {
                $playerUrl = url().'/players/nba/'.$player->id;

                $result[] = [
                    'value' => $player->name,
                    'url' => $playerUrl
                ]; 
            }
        }

        return Response::json($result);
    }

	public function getPlayerStats($sportInUrl, $playerId) {
        $statBuilder = new StatBuilder;

        if ($sportInUrl == 'nba') {
            list($boxScoreLines, $overviews, $playerInfo, $player, $name, $previousFdFilters, $fptsProfile, $endYears) = $statBuilder->getNbaPlayerStats($playerId);
        }

        return view('players/nba', compact('boxScoreLines', 'overviews', 'playerInfo', 'player', 'name', 'previousFdFilters', 'fptsProfile', 'endYears'));
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