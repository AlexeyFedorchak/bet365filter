<?php

/**
 * Class namespace
 */
namespace App\Console\Commands\Filter;

/**
 * Used packages
 */

use App\CheckedTelegramMessages;
use App\TelegramUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

/**
 * Class for adding users
 *
 * Class TelegramUpdateFilter
 * @package App\Console\Commands
 */
class TelegramUpdateFilter extends Command
{

    /**
     * key phrase to auth new user
     *
     * @var string
     */
    protected $password = 'I wanna be lucky!';

    /**
     * what bot may answer if password is incorrect
     *
     * @var array
     */
    protected $declinedResponses = [
        'Oops.. but I don\'t understand what are you talking about..',
        'It seems, bro, you\'ve missed something..',
        'Hey? Oh no.. of course no..',
        'Tricky attempt, but I don\'t like this option',
        'Wanna be my friend? Try again!',
        'New attempt.. new fail.. so boring bot\'s life!',
        'Now I\'m in bad mood, please damage my nerves later..',
    ];

    /**
     * success bot's answers
     *
     * @var array
     */
    protected $successAnswers = [
        'Thanks bro, now we are friends!',
        'Seems you know the trick! Welcome!',
        'Luck is a good choice! I\'m ready to be your genie, bro!',
        'Aaa.. Welcome!.. Welcome!',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:update:filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adding users to telegram';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle()
    {
        Log::info('###');

        $telegram = new Api(env('TELEGRAM_API_KEY_LIVE'));
        $response = $telegram->getUpdates();
        $usersChatIds = TelegramUser::all()->pluck('chat_id')->toArray() ?? collect();

        $collected = collect($response)->pluck('message') ?? collect();

        $usersMessages = [];
        $usersMessagesIds = [];
        foreach ($collected as $message) {
            $usersMessages[$message['chat']['id']] = $message['text'];
            $usersMessagesIds[$message['chat']['id']] = $message['message_id'];
        }

        $usedKeys = [];
        $lastCheckedMessageId = CheckedTelegramMessages::orderBy('id', 'DESC')
                ->first()->message_id ?? 0;

        foreach ($usersMessages as $key => $userMessage) {
            if ($usersMessagesIds[$key] <= $lastCheckedMessageId)
                continue;

            if (in_array($key, $usersChatIds)) continue;
            if (in_array($key, $usedKeys)) continue;

            if ($userMessage != $this->password) {
                $telegram->sendMessage([
                    'chat_id' => $key,
                    'text' => $this->declinedResponses[rand(0, 6)],
                ]);

                CheckedTelegramMessages::updateOrCreate(
                    [
                        'message_id' => $usersMessagesIds[$key]
                    ],
                    []);

                continue;
            }

            TelegramUser::updateOrCreate(
                [
                    'chat_id' => $key,
                ],
                [
                    'chat_id' => $key,
                ]);

            $telegram->sendMessage([
                'chat_id' => $key,
                'text' => $this->successAnswers[rand(0, 3)] ,
            ]);

            $usedKeys[] = $key;
            $this->info('Added/updated user (ID: ' . $key . ').');
            Log::info('New user: ' . $key);
        }
    }
}
