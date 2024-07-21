<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
	const APP_ACCESS_TOKEN = 'EAALzyVMlD3sBOwVCfGWRscxVzRfWzntw5sTADmt6X2X7ubpZAV4jQsMKRxc5IOYEl2Sdu6gwj2rBdRrwoM2LfjR5ZBOjlA5tZA3wqdNS35tJLpybOHnao8PyKg1hXsPQG7ZA2rdLNdNJhYn6Alhil3dkPs05w05VY4AK8NaVvkwHAkI8H6VTRnF42gLYkHMSAKbFZBLoiUjkJZBqDjh7obE00qywZDZD';
	const APP_SECRET = '486e26737c17c27bf2070e2062b028ee';
	const FIRST_PAGE_ID = '396258183564514';
	const FIRST_PAGE_ACCESS_TOKEN = 'EAALzyVMlD3sBOxHpZBMqTbi7NECMOjp2C3AXz9A61u2TtZA3VXh5mCpeb3FCMcFgPn2fMtVnYExrHTKf7xI42scy00ydKMZCM7lijJxTRBYW7zMhtu7GzSI5T1zGetDNbpBlhaZA0U9qfZCbhC3oQEIK53i8sXJ72vLIvzoenpnERTcxn7LAcOEjyDHZAi22B005ycV6yywHvvq0a6qYA9glMEPLaM3z0xaQZDZD';
	const HFPTUBE_ACCOUNT_ID = '17841439584947955';
	const H_FAZILATPOUR_ACCOUNT_ID = '17841401176894011';
	const WEBHOOK_VERIFY_TOKEN = '12345';

	public function verifyWebhook(Request $request)
	{
		$this->logToFile($request->all(), 'verify');
		$mode = 'hub_mode';
		$token = 'hub_verify_token';
		$challenge = 'hub_challenge';

		if ($request->has([$mode, $token, $challenge])) {
			$mode = $request->get($mode);
			$token = $request->get($token);
			$challenge = $request->get($challenge);

			if ($mode === 'subscribe' && $token === self::WEBHOOK_VERIFY_TOKEN)
				return response($challenge);
		}

		return response('Unauthorized!', 403);
	}

	public function getNotification(Request $request)
	{
		$this->logToFile($request->all());

		if (!$this->isFromFacebook($request))
			return response('Invalid request!', 403);

		$payload = json_decode(json_encode($request->all()));
		if ($payload->field !== 'messages')
			return response('Unhandled field!', 403);

		$state = (int)$payload->value->message->text;
		$user = User::firstOrCreate([
				'instagram_id' => $payload->value->sender->id,
		]);

		if ($user->state === null
				&& $user->step === null
				&& in_array($state, [1, 2, 3, 4])) {
			$user->update([
					'state' => $state,
					'step' => 1,
			]);
		}

		switch ($user->state) {
			case 1:
				// Carousel
				$this->showCarousel($user);
				break;

			case 2:
				// Mobile
				$this->getMobileNumber($user, $payload);
				break;

			case 3:
				// Form
				$this->getPersonalInfo($user, $payload);
				break;

			case 4:
				// Text
				$this->getRandomText($user, $payload);
				break;
		}

		return response('Done!');
	}

	private function sendRequest($body)
	{
		$client = new Client(['base_uri' => 'https://graph.facebook.com/v20.0/']);
		try {
			return $client->post('me/messages', [
					'query' => ['access_token' => self::FIRST_PAGE_ACCESS_TOKEN],
					'json' => $body,
			]);
		} catch (GuzzleException $e) {
			return response($e->getMessage(), 500);
		}
	}

	private function showCarousel($user): void
	{
		$body = [
				'recipient' => ['id' => $user->instagram_id],
				'message' => [
						'attachment' => [
								'type' => 'template',
								'payload' => [
										'template_type' => 'generic',
										'elements' => [
												[
														'title' => 'Title 1',
														'subtitle' => 'Subtitle 1',
														'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
														'default_action' => [
																'type' => 'web_url',
																'url' => 'https://www.example.com/',
														],
														'buttons' => [
																[
																		'title' => 'Button 1',
																		'type' => 'web_url',
																		'url' => 'https://www.example.com/',
																],
																[
																		'title' => 'Button 2',
																		'type' => 'postback',
																		'payload' => 'Button 2',
																],
														],
												],
												[
														'title' => 'Title 2',
														'subtitle' => 'Subtitle 2',
														'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
														'default_action' => [
																'type' => 'web_url',
																'url' => 'https://www.example.com/',
														],
														'buttons' => [
																[
																		'title' => 'Button 1',
																		'type' => 'web_url',
																		'url' => 'https://www.example.com/',
																],
																[
																		'title' => 'Button 2',
																		'type' => 'postback',
																		'payload' => 'Button 2',
																],
														],
												],
												[
														'title' => 'Title 3',
														'subtitle' => 'Subtitle 3',
														'image_url' => 'https://images.unsplash.com/photo-1524289286702-f07229da36f5',
														'default_action' => [
																'type' => 'web_url',
																'url' => 'https://www.example.com/',
														],
														'buttons' => [
																[
																		'title' => 'Button 1',
																		'type' => 'web_url',
																		'url' => 'https://www.example.com/',
																],
																[
																		'title' => 'Button 2',
																		'type' => 'postback',
																		'payload' => 'Button 2',
																],
														],
												],
										],
								],
						],
				],
		];
		$this->sendRequest($body);
		$user->update([
				'state' => null,
				'step' => null,
		]);
	}

	private function getMobileNumber($user, $payload): void
	{
		if ($user->step === 1) {
			// Show message for getting mobile number
			$body = [
					'recipient' => ['id' => $user->instagram_id],
					'message' => ['text' => 'لطفا شماره موبایل خود را وارد کنید'],
			];
			$this->sendRequest($body);

			// Increment step for saving mobile number
			$user->update([
					'step' => $user->step + 1,
			]);
		} else {
			// React with love to the user message
			$body = [
					'recipient' => ['id' => $user->instagram_id],
					'sender_action' => 'react',
					'payload' => [
							'message_id' => $payload->value->message->mid,
							'reaction' => 'love',
					],
			];
			$this->sendRequest($body);

			// Send successful message
			$body = [
					'recipient' => ['id' => $user->instagram_id],
					'message' => ['text' => 'با تشکر، شماره موبایل با موفقیت ذخیره شد'],
			];
			$this->sendRequest($body);

			// Save the mobile number and reset state and step
			$user->update([
					'mobile' => $payload->value->message->text,
					'state' => null,
					'step' => null,
			]);
		}
	}

	private function getPersonalInfo($user, $payload): void
	{
		switch ($user->step) {
			case 1:
				// Show message for getting first name
				$body = [
						'recipient' => ['id' => $user->instagram_id],
						'message' => ['text' => 'لطفا نام خود را وارد کنید'],
				];
				$this->sendRequest($body);
				break;

			case 2:
				// Save the first name
				$user->update([
						'first_name' => $payload->value->message->text,
				]);

				// Show message for getting last name
				$body = [
						'recipient' => ['id' => $user->instagram_id],
						'message' => ['text' => 'لطفا نام خانوادگی خود را وارد کنید'],
				];
				$this->sendRequest($body);
				break;

			case 3:
				// Save the last name
				$user->update([
						'last_name' => $payload->value->message->text,
				]);

				// Show message for getting address
				$body = [
						'recipient' => ['id' => $user->instagram_id],
						'message' => ['text' => 'لطفا آدرس خود را وارد کنید'],
				];
				$this->sendRequest($body);
				break;

			case 4:
				// Save the address and reset state and step
				$user->update([
						'address' => $payload->value->message->text,
						'state' => null,
						'step' => null,
				]);

				// Show successful message
				$body = [
						'recipient' => ['id' => $user->instagram_id],
						'message' => ['text' => 'با تشکر، اطلاعات با موفقیت ذخیره شد'],
				];
				$this->sendRequest($body);
				break;
		}

		// Increment step for next info
		if ($user->step !== null) {
			$user->update([
					'step' => $user->step + 1,
			]);
		}
	}

	private function getRandomText($user, $payload): void
	{
		if ($user->step === 1) {
			// Show message for getting random text
			$body = [
					'recipient' => ['id' => $user->instagram_id],
					'message' => ['text' => 'لطفا متنی را وارد کنید'],
			];
			$this->sendRequest($body);

			// Increment step for getting random text
			$user->update([
					'step' => $user->step + 1,
			]);
		} else {
			// Show input text to user
			$body = [
					'recipient' => ['id' => $user->instagram_id],
					'message' => ['text' => $payload->value->message->text],
			];
			$this->sendRequest($body);

			// Reset state and step
			$user->update([
					'state' => null,
					'step' => null,
			]);
		}
	}

	private function isFromFacebook(Request $request): bool
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

	private function logToFile($payload, $method = 'notify'): void
	{
		$dateTime = Carbon::now()->format('Y.m.d_H.i.s');
		$fileName = $method === 'verify'
				? "verify_$dateTime"
				: $dateTime;
		Storage::put("$fileName.txt", json_encode($payload));
	}

	public function test(Request $request)
	{
		$body = [
//				'recipient' => ['id' => 17841401176894011],
				'recipient' => ['id' => '760470453988880'],
				'message' => ['text' => 'Enter text'],
		];
//		dd(json_encode($body));
//		$this->sendRequest($body);


		$this->logToFile($request->all());
		dd('Done!');
	}
}
