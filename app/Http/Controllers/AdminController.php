<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerTeam;
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
    	$input['name'] = trim($request->get('name'));
    	$input['team_id'] = $request->get('team_id');
    	$input['start_date'] = $request->get('start_date');
        $input['is_rookie'] = $request->get('is_rookie');

        if (!$input['is_rookie']) {
            $playerId = Player::where('name', $input['name'])->pluck('id');

            if (is_null($playerId)) {
                $message = 'Player ID not found.';
                Session::flash('alert', 'warning');

                return redirect('admin/nba/add_player')->with('message', $message);  
            }
        }

        if ($input['is_rookie']) {
            $player = new Player;

            $player->name = $input['name'];

            $player->save();

            $playerId = $player->id;
        }

        $playerTeam = new PlayerTeam;

        $playerTeam->player_id = $playerId;
        $playerTeam->team_id = $input['team_id'];
        $playerTeam->start_date = $input['start_date'];
        $playerTeam->end_date = '3000-01-01';

        $playerTeam->save();

        $message = 'Success!';
        Session::flash('alert', 'info');

        return redirect('admin/nba/add_player')->with('message', $message);      
    }
}