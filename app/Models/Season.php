<?php namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Season extends Eloquent {

	public function games() {
		return $this->hasMany('Game');
	}

}