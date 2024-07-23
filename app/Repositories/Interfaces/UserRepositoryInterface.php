<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface extends BaseInterface
{
	/**
	 * @param array $attributes
	 * @return User
	 */
	public function firstOrCreate(array $attributes): User;
}
