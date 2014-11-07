<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->get('one_time', 'ScrapersController@one_time');

$router->post('scrapers/fd_nba_salaries', 'ScrapersController@fd_nba_salaries');
$router->post('scrapers/br_nba_games', 'ScrapersController@br_nba_games');

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');

$router->get('scrapers/fd_nba_salaries', function() {
	return View::make('pages/scrapers/fd_nba_salaries');
});
$router->get('scrapers/br_nba_games', function() {
	return View::make('pages/scrapers/br_nba_games');
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
