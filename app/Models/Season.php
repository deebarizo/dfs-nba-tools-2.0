<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Season extends Eloquent {

	public function games() {
		return $this->hasMany('Game');
	}

}