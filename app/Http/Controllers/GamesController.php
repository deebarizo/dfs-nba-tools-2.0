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

use App\Classes\Formatter;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class GamesController {

	public function showNbaGames() {
		$games = Game::orderBy('date', 'desc')->take(100)->get();

		$formatter = new Formatter;

		$games = $formatter->formatNbaGames($games);

		# ddAll($games);

		return view('games/nba', compact('games'));
	}

}