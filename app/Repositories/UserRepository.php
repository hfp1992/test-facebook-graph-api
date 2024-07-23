<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
	public function all(): Collection
	{
		return User::all();
	}

	public function find($id): Model
	{
		return User::find($id);
	}

	public function create(array $attributes): Model
	{
		return User::create($attributes);
	}

	public function update($id, array $attributes): bool
	{
		$user = $this->find($id);
		return $user->update($attributes);
	}

	public function delete($id): void
	{
		$user = $this->find($id);
		$user->delete();
	}

	public function firstOrCreate(array $attributes): User
	{
		return User::firstOrCreate($attributes);
	}
}
