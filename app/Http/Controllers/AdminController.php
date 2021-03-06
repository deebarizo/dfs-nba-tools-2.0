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

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Response;

date_default_timezone_set('America/Chicago');

class AdminController {

    /****************************************************************************************
    ADD PLAYER (NBA)
    ****************************************************************************************/

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


    /****************************************************************************************
    UPDATE PLAYER (NBA)
    ****************************************************************************************/

    public function getNbaPlayerNameAutocompleteAdmin(Request $request) {
        $formInput = $request->input('term');
        $formInput = Str::lower($formInput);

        $players = Player::all();

        $result = [];

        foreach ($players as $player) {
            if (strpos(Str::lower($player->name), $formInput) !== false) {
                $playerUrl = url().'/admin/nba/update_player/'.$player->id;

                $result[] = [
                    'value' => $player->name,
                    'url' => $playerUrl
                ]; 
            }
        }

        return Response::json($result);
    }

    public function updatePlayerForm($sport, $playerId) {
        if ($sport == 'nba') {
            $teams = Team::all();

            $playerTeams = DB::table('players_teams')
                            ->select('players_teams.id',
                                     'players.name', 
                                     'players_teams.player_id',
                                     'teams.abbr_br', 
                                     'players_teams.team_id',
                                     'players_teams.start_date',
                                     'players_teams.end_date')
                            ->join('players', 'players.id', '=', 'players_teams.player_id')
                            ->join('teams', 'teams.id', '=', 'players_teams.team_id')
                            ->where('player_id', $playerId)
                            ->orderBy('players_teams.start_date', 'asc')
                            ->get();

            # ddAll($playerTeams);

            return view('/admin/'.$sport.'/update_player', compact('teams', 'playerTeams'));
        }
    }

    public function updatePlayer(Request $request, $sportInUrl, $playerId) {
        if ($sportInUrl == 'nba') {
            $playerTeams = PlayerTeam::where('player_id', $playerId)->get();

            # ddAll($playerTeams);

            foreach ($playerTeams as $playerTeam) {
                $playerTeam->team_id = $request->get('team_id_'.$playerTeam->id);
                $playerTeam->start_date = $request->get('start_date_'.$playerTeam->id);
                $playerTeam->end_date = $request->get('end_date_'.$playerTeam->id);

                $playerTeam->save();
            }

            $message = 'Success!';
            Session::flash('alert', 'info');

            return redirect('admin/nba/update_player/'.$playerId)->with('message', $message);          
        }
        
    }

}