<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;
use App\DailyFdFilter;
use App\TeamFilter;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class PlayerPoolsController {

	public function home() {
		$playerPools = PlayerPool::orderBy('date', 'desc')->take(50)->get()->toArray();

		foreach ($playerPools as &$playerPool) {
			if ($playerPool['buy_in'] == '') {
				$playerPool['buy_in'] = 'N/A';

				continue;
			}

			$playerPool['buy_in'] = '$'.$playerPool['buy_in'];
			
		} unset($playerPool);

		# ddAll($playerPools);

		return view('pages/home', compact('playerPools'));
	}

}