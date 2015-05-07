<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbContest;
use App\Models\DkMlbContestLineup;
use App\Models\DkMlbContestLineupPlayer;

use App\Classes\ContestBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class ContestsController {

	public function getContests($siteInUrl, $sportInUrl, $contestTypeInUrl) {
		$site = strtoupper($siteInUrl);
		$sport = strtoupper($sportInUrl);
		$numOfContests = 10;

		$contestBuilder = new ContestBuilder;

		$contests = $contestBuilder->getOwnerships($site, $sport, $contestTypeInUrl, $numOfContests);

		# ddAll($contests);

        return view('contests/'.$siteInUrl.'/'.$sportInUrl, compact('contests', 
        															'numOfContests')); 
	}

}