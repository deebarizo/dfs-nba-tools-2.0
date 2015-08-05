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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class PlayerPoolsController {

	public function home() {
		$playerPools = DB::table('player_pools')
						->select('player_pools.date as date',
								 'player_pools.sport', 
								 'player_pools.time_period', 
								 'player_pools.site', 
								 'player_pools.buy_in',
								 'dk_mlb_contests.id as dk_mlb_contest_id', 
								 'dk_mlb_contests.name as dk_mlb_contest_name')
						->leftJoin('dk_mlb_contests', 'dk_mlb_contests.player_pool_id', '=', 'player_pools.id')
						->orderBy('player_pools.date', 'desc')
						->take(100)
						->get();

		# dd($playerPools);

		foreach ($playerPools as $playerPool) {
			if (is_null($playerPool->dk_mlb_contest_id)) {
				$playerPool->contest_name = 'None';
				$playerPool->contest_in_url = 'nc';
			} else {
				$playerPool->contest_name = $playerPool->dk_mlb_contest_name;
				$playerPool->contest_in_url = $playerPool->dk_mlb_contest_id;
			}

			$playerPool->site_in_url = strtolower($playerPool->site);

			$playerPool->sport_in_url = strtolower($playerPool->sport);

			$timePeriodInUrl = strtolower($playerPool->time_period);
			$playerPool->time_period_in_url = preg_replace('/\s/', '-', $timePeriodInUrl);

			if ($playerPool->buy_in == '') {
				$playerPool->buy_in = 'N/A';

				continue;
			}

			$playerPool->buy_in = '$'.$playerPool->buy_in;
		}

		# dd($playerPools);

		return view('pages/home', compact('playerPools'));
	}

}