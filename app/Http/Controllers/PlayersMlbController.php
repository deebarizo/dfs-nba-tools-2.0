<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\MlbTeam;
use App\Models\MlbGame;
use App\Models\MlbPlayer;
use App\Models\MlbBoxScoreLine;

use App\Models\PlayerPool;

use App\Classes\StatBuilder;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Response;

date_default_timezone_set('America/Chicago');

class PlayersMlbController {

	public function getPlayerStats($playerId) {
        $statBuilder = new StatBuilder;

        $seasons = $statBuilder->getMlbPlayerStats($playerId);

        $name = MlbPlayer::where('id', $playerId)->pluck('name');

        # ddAll($seasons);

        return view('players/mlb', compact('seasons', 'name'));
	}

}