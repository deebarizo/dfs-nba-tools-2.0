<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyController {

	public function daily_fd_nba($date = 'today') {
		if ($date == 'today') {
			$date = date('Y-m-d', time());
		}

		

		return view('daily_fd_nba');
	}

}