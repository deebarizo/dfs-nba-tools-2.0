<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\DailyFdFilter;
use App\Models\TeamFilter;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;
use App\Models\DKMlbContest;

use App\Classes\StatBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class AdminController {

	public function addPlayerForm($sport) {
        if ($sport == 'nba') {
        	$teams = Team::all();

        	$startDate = date('Y-m-d', time());

            return view('/admin/'.$sport.'/add_player', compact('teams', 'startDate'));
        }
    }

    public function addPlayer(Request $request) {
    	$player['name'] = $request->get('name');
    	$player['team_id'] = $request->get('team_id');
    	$player['start_date'] = $request->get('start_date');

    	ddAll($player);
    }
}