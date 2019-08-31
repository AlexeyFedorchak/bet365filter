<?php

namespace App\Console\Commands\Filter;

use Illuminate\Console\Command;
use App\TelegramUser;
use App\UserFilters;
use App\CheckedTelegramMessages;
use Telegram\Bot\Api;

class SetFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:filters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set filters';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $telegram = new Api(env('TELEGRAM_API_KEY_FILTER'));
        $messages = $telegram->getUpdates();
        $usersChatIds = TelegramUser::all()
            ->pluck('chat_id')
            ->toArray() ?? [];

        $lastCheckedMessageId = CheckedTelegramMessages::orderBy('id', 'DESC')
                ->first()->message_id ?? 0;

        $messages = array_filter($messages, function ($message) 
            use ($usersChatIds, $lastCheckedMessageId) {
            return 
                (in_array(($message['message']['chat']['id'] ?? 0), $usersChatIds)
                    && $message['message']['message_id'] > $lastCheckedMessageId);
        });

        foreach ($messages as $message) {
            $filterData = explode(':', $message['message']['text']);
            if (!(isset($filterData[0]) 
                && strlen($filterData[0]) === 4 
                && $filterData[0][2] === '_')) {

                $this->checkMessage($message['message']['message_id']);
                $this->sendFailMessage($telegram, $message);
                continue;
            }

            if (!(isset($filterData[1]) && is_numeric($filterData[1])))  {
                $this->checkMessage($message['message']['message_id']);
                $this->sendFailMessage($telegram, $message);
                continue;
            }

            if (!(isset($filterData[2]) && is_numeric($filterData[2]))) {
                $this->checkMessage($message['message']['message_id']);
                $this->sendFailMessage($telegram, $message);
                continue;
            }

            if (!(isset($filterData[3]) && is_numeric($filterData[3]))) {
                $this->checkMessage($message['message']['message_id']);
                $this->sendFailMessage($telegram, $message);
                continue;
            }

            $oddType = $filterData[0];
            $eventId = $filterData[1];
            $more = $filterData[2];
            $less = $filterData[3];

            UserFilters::updateOrCreate(
            [
                'chat_id' => $message['message']['chat']['id'],
                'event_id' => $eventId,
            ], 
            [
                'more' => $more,
                'less' => $less,
                'odd_type' => $oddType,
            ]);

            $this->checkMessage($message['message']['message_id']);

            $this->sendSuccessMessage($telegram, $message, $oddType, $eventId, $more, $less);
        }
    }

    private function sendFailMessage($telegram, $message)
    {
        $telegram->sendMessage([
            'chat_id' => $message['message']['chat']['id'],
            'text' => 'Oops.. I don\'t understand you..' . "\r\n"
                        . 'Please specify you filter in such structure: ' . "\r\n"
                        . '"ODD_TYPE:EVENT_ID:MORE:LESS"' . "\r\n"
                        . 'Example: 18_2:78729432:45:78',
            ]);
    }

    private function sendSuccessMessage($telegram, $message, $oddType, $eventId, $more, $less)
    {
        $telegram->sendMessage([
            'chat_id' => $message['message']['chat']['id'],
            'text' => 'I\'ve got it!' . "\r\n"
                        . 'Confirm filters structure: ' . "\r\n"
                        . 'Event id: ' . $eventId . '.' . "\r\n"
                        . 'Odd type: ' . $oddType . '.' . "\r\n"
                        . 'More then or equal: ' . $more . '.' . "\r\n"
                        . 'Less then or equal: ' . $less . '.' . "\r\n",
            ]);
    }

    private function checkMessage($messageId)
    {
            CheckedTelegramMessages::updateOrCreate(
            [
                'message_id' => $messageId,
            ],
            []);
    }
}
