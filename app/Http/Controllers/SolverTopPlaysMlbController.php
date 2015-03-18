<?php namespace App\Http\Controllers;

use App\Models\PlayerPool;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;

use App\Classes\SolverTopPlaysMlb;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverTopPlaysMlbController {

	public function solverTopPlaysMlb($siteInUrl, $timePeriodInUrl, $date) {
		$solverTopPlaysMlb = new SolverTopPlaysMlb;

		if ($siteInUrl == 'dk') {
			$solverTopPlaysMlb->generateLineups($timePeriodInUrl, $date);
		}
	}

}