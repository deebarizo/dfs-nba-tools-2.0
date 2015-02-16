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
use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\DefaultLineupBuyIn;
use App\Classes\LineupBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class NbawowyController {

	public function nbawowy() {
		$player = 'Jose Calderon';
		$startDate = '2014-10-28';
		$endDate = '2015-02';
		$off = '[Carmelo%20Anthony]';

		// Minutes

        $json = file_get_contents('http://nbawowy.com/api/both/m/poss/q/[1,2,3,4,0,5,6,7]/team/Knicks/vs/[76ers,Bobcats,Bucks,Bulls,Cavaliers,Celtics,Clippers,Grizzlies,Hawks,Heat,Hornets,Jazz,Kings,Knicks,Lakers,Magic,Mavericks,Nets,Nuggets,Pacers,Pelicans,Pistons,Raptors,Rockets,Spurs,Suns,Thunder,Timberwolves,Trail%20Blazers,Warriors,Wizards]/on/[]/off/[Carmelo%20Anthony]/from/2014-10-28/to/2015-02-11');

        $players = json_decode($json, true);

        ddAll($players);
	}



}