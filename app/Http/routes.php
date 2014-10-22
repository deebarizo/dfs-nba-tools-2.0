<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->get('one_time', 'ScrapersController@one_time');

$router->get('scrapers/season_form', 'ScrapersController@season_form');
$router->post('scrapers/season_scraper', 'ScrapersController@season_scraper');
$router->get('scrapers/player_scraper', 'ScrapersController@player_scraper');
$router->get('scrapers/box_score_line_scraper', 'ScrapersController@box_score_line_scraper');

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');

$router->get('/', 'PagesController@home');