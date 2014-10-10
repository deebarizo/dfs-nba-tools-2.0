<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;

use Illuminate\Http\Request;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class ScrapersController {

	public function season_form() {
		return view('scrapers.season_form');
	}

	public function season_scraper(Request $request) {
		$endYear = $request->input('end_year');

		$client = new Client();

		$crawler = $client->request('GET', 'http://www.basketball-reference.com/leagues/NBA_'.$endYear.'_games.html');

		$season = Season::where('end_year', $endYear)->first();
		$teams = Team::all();

		$status_code = $client->getResponse()->getStatus();

		if ($status_code == 200) {
			$rowCount = $crawler->filter('table#games > tbody > tr')->count();

			$rowContents = array();

			$tableNames[1] = 'date';
			$tableNames[2] = 'link_br';
			$tableNames[3] = 'road_team_id';
			$tableNames[4] = 'road_team_score';
			$tableNames[5] = 'home_team_id';
			$tableNames[6] = 'home_team_score';
			$tableNames[7] = 'ot_periods';
			$tableNames[8] = 'notes';

			for ($i=1; $i <= $rowCount; $i++) { // nth-child does not start with a zero index
				for ($n=1; $n <= 8; $n++) { // nth-child does not start with a zero index
					if ($n !== 2) {
						$rowContents[$i][$tableNames[$n]] = $crawler->filter('table#games > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
					} else {
						$rowContents[$i][$tableNames[$n]] = $crawler->filter('table#games > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->selectLink('Box Score')->link()->getUri();
					}
				}

				$scrapedDate = $rowContents[$i]['date'];
				$scrapedDate = substr($scrapedDate, 5);
				$rowContents[$i]['date'] = date('Y-m-d', strtotime(str_replace('-', '/', $scrapedDate)));

				$twoTeams = [
					'home_team_id',
					'road_team_id'
				];

				foreach ($twoTeams as $row) {
					foreach ($teams as $team)
					{				    
					    if ($rowContents[$i][$row] == $team->name_br) {
					    	$rowContents[$i][$row] = $team->id;
					    	break;
					    }
					} 
				}

				$scrapedOTField = $rowContents[$i]['ot_periods'];
				if ($scrapedOTField == '') {
					$rowContents[$i]['ot_periods'] = 0;
				} elseif ($scrapedOTField == 'OT') {
					$rowContents[$i]['ot_periods'] = 1;
				} elseif ($scrapedOTField != 'OT' && $scrapedOTField != '') {
					$rowContents[$i]['ot_periods'] = substr($scrapedOTField, 0, 1);
				}

				$scrapedNotesField = $rowContents[$i]['notes'];
				if ($scrapedNotesField == '') {
					$rowContents[$i]['notes'] = null;
				}

				$rowContents[$i]['season_id'] = $season->id;

				if ($i > 20) { 
					return $rowContents;
				}
			}	
		} else {
			return 'Status Code is not 200.';
		}

		return $rowContents;
	}

}
