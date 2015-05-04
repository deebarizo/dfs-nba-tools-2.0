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
		// $playerPools = PlayerPool::orderBy('date', 'desc')->take(50)->get()->toArray();

		$playerPools = DB::table('player_pools')
						->select('*')
						->leftJoin
						->orderBy('date', 'desc')
						->take(50)
						->get();

		# dd($playerPools);

		foreach ($playerPools as $playerPool) {
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

		ddAll($playerPools);

		return view('pages/home', compact('playerPools'));
	}

}