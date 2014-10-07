<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class ScrapersController {

	public function season()
	{
		$heading = 'Scrapers - Season';

		$end_year = 2014;

		$season = Season::where('end_year', $end_year)->first();

		return view('scrapers.season', compact('heading', 'season'));

		

		$client = new Client();

		$crawler = $client->request('GET', 'http://www.basketball-reference.com/leagues/NBA_2014_games.html');

		$season_id = 1;

		$status_code = $client->getResponse()->getStatus();
		
		if ($status_code == 200) 
		{
			$rowCount = $crawler->filter('table#games > tbody > tr')->count();

			$rowContents = array();

			for ($i=1; $i <= $rowCount; $i++) // nth-child does not start with a zero index
			{ 
				for ($n=1; $n <= 8; $n++) // nth-child does not start with a zero index
				{ 
					$rowContents[$i][$n] = $crawler->filter('table#games > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
				}

				return $rowContents;
			}	

						
		}
		else
		{
			return 'Status Code is not 200.';
		}

		return $rowContents;
	}

}
