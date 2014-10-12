<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->get('scrapers/season_form', 'ScrapersController@season_form');
$router->post('scrapers/season_scraper', 'ScrapersController@season_scraper');

$router->get('studies/correlations/ScoresAndVegasScores', 'StudiesController@correlationScoresAndVegasScores');
