<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->get('one_time', 'ScrapersController@one_time');

$router->post('scrapers/fd_nba_salaries_scraper', 'ScrapersController@fd_nba_salaries_scraper');
$router->post('scrapers/season_scraper', 'ScrapersController@season_scraper');
$router->get('scrapers/player_scraper', 'ScrapersController@player_scraper');
$router->get('scrapers/box_score_line_scraper', 'ScrapersController@box_score_line_scraper');

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');

$router->get('scrapers/fd_nba_salaries', function() {
	return View::make('scrapers/fd_nba_salaries');
});
$router->get('scrapers/br_season', function() {
	return View::make('scrapers/br_season');
});
$router->get('scrapers', function() {
	return View::make('pages/scrapers');
});
$router->get('studies', function() {
	return View::make('pages/studies');
});
$router->get('/', function() {
	return View::make('pages/home');
});
