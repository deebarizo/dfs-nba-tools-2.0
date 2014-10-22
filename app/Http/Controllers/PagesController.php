<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

class PagesController {

	public function home() {
		return view('pages/home');
	}

}