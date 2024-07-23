<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseInterface
{
	/**
	 * @return Collection
	 */
	public function all(): Collection;

	/**
	 * @param $id
	 * @return Model
	 */
	public function find($id): Model;

	/**
	 * @param array $attributes
	 * @return Model
	 */
	public function create(array $attributes): Model;

	/**
	 * @param $id
	 * @param array $attributes
	 * @return bool
	 */
	public function update($id, array $attributes): bool;

	/**
	 * @param $id
	 * @return void
	 */
	public function delete($id): void;
}
