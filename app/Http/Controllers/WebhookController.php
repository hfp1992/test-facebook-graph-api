<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\FacebookService;
use App\Services\LogService;
use App\Services\PayloadService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
	private UserRepositoryInterface $userRepository;
	private FacebookService $facebookService;
	private LogService $logService;
	private PayloadService $payloadService;
	private User $user;

	public function __construct(UserRepositoryInterface $userRepository)
	{
		$this->userRepository = $userRepository;
		$this->facebookService = new FacebookService();
		$this->logService = new LogService();
		$this->payloadService = new PayloadService();
	}

	/**
	 * @param Request $request
	 * @return Application|Response|ResponseFactory
	 */
	public function verifyWebhook(Request $request): Application|Response|ResponseFactory
	{
		$this->logService->writeToFile($request->all());
		$mode = 'hub_mode';
		$token = 'hub_verify_token';
		$challenge = 'hub_challenge';

		if ($request->has([$mode, $token, $challenge])) {
			$mode = $request->get($mode);
			$token = $request->get($token);
			$challenge = $request->get($challenge);

			if ($mode === 'subscribe' && $token === FacebookService::WEBHOOK_VERIFY_TOKEN)
				return response($challenge);
		}

		return response('Unauthorized!', 403);
	}

	public function getNotification(Request $request): Application|Response|ResponseFactory
	{
		$this->logService->writeToFile($request->all());

		if (!$this->facebookService->isValid($request))
			return response('Invalid request!', 403);

		$payload = json_decode(json_encode($request->all()));
		$this->payloadService->setPayload($payload);

		if ($this->payloadService->getValue('object') !== 'instagram'
				|| $this->payloadService->getValue('field') !== 'messages')
			return response('Invalid payload!', 403);

		$state = (int)$this->payloadService->getValue('text');
		$this->user = $this->userRepository->firstOrCreate([
				'instagram_id' => $this->payloadService->getValue('sender_id'),
		]);

		if ($this->user->state === null
				&& $this->user->step === null
				&& in_array($state, [1, 2, 3, 4])) {
			$this->userRepository->update($this->user->id, [
					'state' => $state,
					'step' => 1,
			]);
		}

		switch ($this->user->state) {
			case 1:
				// Carousel
				$this->showCarousel();
				break;

			case 2:
				// Mobile
				$this->getMobileNumber();
				break;

			case 3:
				// Form
				$this->getPersonalInfo();
				break;

			case 4:
				// Text
				$this->getRandomText();
				break;
		}

		return response('Done!');
	}

	public function privacyAndPolicy(): Application|Response|ResponseFactory
	{
		return response('Sample privacy and policy');
	}

	private function showCarousel(): void
	{
		$body = [
				'recipient' => ['id' => $this->user->instagram_id],
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
		$this->facebookService->sendRequest($body);
		$this->userRepository->update($this->user->id, [
				'state' => null,
				'step' => null,
		]);
	}

	private function getMobileNumber(): void
	{
		if ($this->user->step === 1) {
			// Show message for getting mobile number
			$body = [
					'recipient' => ['id' => $this->user->instagram_id],
					'message' => ['text' => 'لطفا شماره موبایل خود را وارد کنید'],
			];
			$this->facebookService->sendRequest($body);

			// Increment step for saving mobile number
			$this->userRepository->update($this->user->id, [
					'step' => $this->user->step + 1,
			]);
		} else {
			// React with love to the user message
			$body = [
					'recipient' => ['id' => $this->user->instagram_id],
					'sender_action' => 'react',
					'payload' => [
							'message_id' => $this->payloadService->getValue('mid'),
							'reaction' => 'love',
					],
			];
			$this->facebookService->sendRequest($body);

			// Send successful message
			$body = [
					'recipient' => ['id' => $this->user->instagram_id],
					'message' => ['text' => 'با تشکر، شماره موبایل با موفقیت ذخیره شد'],
			];
			$this->facebookService->sendRequest($body);

			// Save the mobile number and reset state and step
			$this->userRepository->update($this->user->id, [
					'mobile' => $this->payloadService->getValue('text'),
					'state' => null,
					'step' => null,
			]);
		}
	}

	private function getPersonalInfo(): void
	{
		switch ($this->user->step) {
			case 1:
				// Show message for getting first name
				$body = [
						'recipient' => ['id' => $this->user->instagram_id],
						'message' => ['text' => 'لطفا نام خود را وارد کنید'],
				];
				$this->facebookService->sendRequest($body);
				break;

			case 2:
				// Save the first name
				$this->userRepository->update($this->user->id, [
						'first_name' => $this->payloadService->getValue('text'),
				]);

				// Show message for getting last name
				$body = [
						'recipient' => ['id' => $this->user->instagram_id],
						'message' => ['text' => 'لطفا نام خانوادگی خود را وارد کنید'],
				];
				$this->facebookService->sendRequest($body);
				break;

			case 3:
				// Save the last name
				$this->userRepository->update($this->user->id, [
						'last_name' => $this->payloadService->getValue('text'),
				]);

				// Show message for getting address
				$body = [
						'recipient' => ['id' => $this->user->instagram_id],
						'message' => ['text' => 'لطفا آدرس خود را وارد کنید'],
				];
				$this->facebookService->sendRequest($body);
				break;

			case 4:
				// Save the address and reset state and step
				$this->userRepository->update($this->user->id, [
						'address' => $this->payloadService->getValue('text'),
						'state' => null,
						'step' => null,
				]);

				// Show successful message
				$body = [
						'recipient' => ['id' => $this->user->instagram_id],
						'message' => ['text' => 'با تشکر، اطلاعات با موفقیت ذخیره شد'],
				];
				$this->facebookService->sendRequest($body);
				break;
		}

		// Increment step for next info
		if ($this->user->step !== null) {
			$this->userRepository->update($this->user->id, [
					'step' => $this->user->step + 1,
			]);
		}
	}

	private function getRandomText(): void
	{
		if ($this->user->step === 1) {
			// Show message for getting random text
			$body = [
					'recipient' => ['id' => $this->user->instagram_id],
					'message' => ['text' => 'لطفا متنی را وارد کنید'],
			];
			$this->facebookService->sendRequest($body);

			// Increment step for getting random text
			$this->userRepository->update($this->user->id, [
					'step' => $this->user->step + 1,
			]);
		} else {
			// Show input text to user
			$body = [
					'recipient' => ['id' => $this->user->instagram_id],
					'message' => ['text' => $this->payloadService->getValue('text')],
			];
			$this->facebookService->sendRequest($body);

			// Reset state and step
			$this->userRepository->update($this->user->id, [
					'state' => null,
					'step' => null,
			]);
		}
	}
}
