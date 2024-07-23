<?php

namespace App\Services;

class PayloadService
{
	private mixed $payload;

	/**
	 * @param mixed $payload
	 * @return void
	 */
	public function setPayload(mixed $payload): void
	{
		$this->payload = $payload;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function getValue(string $field): string
	{
		return match ($field) {
			'object' => $this->payload->object,
			'field' => $this->payload->entry[0]->changes[0]->field,
			'sender_id' => $this->payload->entry[0]->changes[0]->value->sender->id,
			'mid' => $this->payload->entry[0]->changes[0]->value->message->mid,
			'text' => $this->payload->entry[0]->changes[0]->value->message->text,
		};
	}
}
