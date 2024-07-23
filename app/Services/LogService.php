<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LogService
{
	/**
	 * @param mixed $payload
	 * @return void
	 */
	public function writeToFile(mixed $payload): void
	{
		$dateTime = Carbon::now()->format('Y.m.d_H.i.s');
		Storage::put("$dateTime.txt", json_encode($payload));
	}
}
