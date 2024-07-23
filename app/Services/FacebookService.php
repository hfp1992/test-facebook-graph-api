<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;

class FacebookService
{
	private const APP_SECRET = '486e26737c17c27bf2070e2062b028ee';
	private const PAGE_ACCESS_TOKEN = 'EAALzyVMlD3sBO4PBYcry7QB9zOPgZAKYnxuIVdcz21gMkTss7LQcaEMCleDmZBNlf3SgFyEAlBIwbIPqDHPguSAmECWuMUZBEeLcThxkGojUXlHDLOH3n7NxgRwulXarTZA7V2u8YxYdeGVZCybwZAPXuoupWLrkcUIW9zQ9OPHMkabggtjd2rS5hIKZBZA0H3Um1XRwAhy73AZDZD';
	public const WEBHOOK_VERIFY_TOKEN = '12345';

//	const APP_ACCESS_TOKEN = 'EAALzyVMlD3sBOwVCfGWRscxVzRfWzntw5sTADmt6X2X7ubpZAV4jQsMKRxc5IOYEl2Sdu6gwj2rBdRrwoM2LfjR5ZBOjlA5tZA3wqdNS35tJLpybOHnao8PyKg1hXsPQG7ZA2rdLNdNJhYn6Alhil3dkPs05w05VY4AK8NaVvkwHAkI8H6VTRnF42gLYkHMSAKbFZBLoiUjkJZBqDjh7obE00qywZDZD';
//	const FIRST_PAGE_ID = '396258183564514';
//	const HFPTUBE_ACCOUNT_ID = '17841439584947955';
//	const H_FAZILATPOUR_ACCOUNT_ID = '17841401176894011';

	/**
	 * @param array $body
	 * @return Application|Response|ResponseInterface|ResponseFactory
	 */
	public function sendRequest(array $body): Application|Response|ResponseInterface|ResponseFactory
	{
		try {
			$client = new Client(['base_uri' => 'https://graph.facebook.com/v20.0/']);
			return $client->post('me/messages', [
					'query' => ['access_token' => self::PAGE_ACCESS_TOKEN],
					'json' => $body,
			]);
		} catch (GuzzleException $e) {
			return response($e->getMessage(), 500);
		}
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	public function isValid(Request $request): bool
	{
		$header = 'x-hub-signature-256';
		if (!$request->hasHeader($header))
			return false;

		$signature = explode(
				'=',
				$request->header($header),
		)[1];
		$payload = json_encode($request->all());
		$sha256 = hash_hmac('sha256', $payload, self::APP_SECRET);

		return hash_equals($signature, $sha256);
	}
}
