<?php namespace App\Classes;

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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class SolverTopPlaysMlb {

	public function generateLineups($timePeriodInUrl, $date) {
		$timePeriod = urlToUcFirst($timePeriodInUrl);

		$topPlays = $this->getTopPlays($timePeriod, $date);

		$positions = $this->getPositions();

		ddAll($topPlays);
	}

	private function getPositions() {
		return [
			['name' => 'SP', 'remaining_spots' => 2],
			['name' => 'C', 'remaining_spots' => 1],
			['name' => '1B', 'remaining_spots' => 1],
			['name' => '2B', 'remaining_spots' => 1],
			['name' => '3B', 'remaining_spots' => 1],
			['name' => 'SS', 'remaining_spots' => 1],
			['name' => 'OF', 'remaining_spots' => 3]
		];
	}

	private function getTopPlays($timePeriod, $date) {
		return DB::table('player_pools')
				 ->select('buy_in', 'mlb_player_id', 'target_percentage', 'mlb_team_id', 'position', 'salary', 'name')
				 ->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
				 ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
				 ->where('time_period', $timePeriod)
				 ->where('date', $date)
				 ->where('target_percentage', '>', 0)
				 ->get();
	}

}