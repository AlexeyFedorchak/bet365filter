<?php

namespace App\Console\Commands\Filter;

use Illuminate\Console\Command;
use App\UserFilters;
use Telegram\Bot\Api;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\CheckedUsersOdd;

class FilterInPlayOdds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:odds:live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter odds for in play events';  

    protected $baseLink = 'https://betsapi.com/rs/bet365/';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Running odds checker...');
        $filters = UserFilters::all();
        
        $client = new Client();
        $token = env('BETS_TOKEN');
        $sportId = env('SPORT_ID');
        $telegram = new Api(env('TELEGRAM_API_KEY_FILTER'));

        try {
            $response = $client->request('GET', 'https://api.betsapi.com/v2/events/inplay?sport_id=' . $sportId . '&token=' . $token . '&day=' . Carbon::now()->format('Ymd'));

            $events = json_decode($response->getBody()->getContents(), true)['results'];
        } catch (\Exception $e) {
                try {
                    \Log::debug('Response error: events!' . $e->getMessage());  
                } catch(\Exception $e) {}

            $events = [];
        }

        $chosenEvents = $filters->pluck('event_id')->toArray();
        $filteredEvents = array_filter($events, function ($event) use ($chosenEvents) {
            return in_array($event['id'], $chosenEvents);
        });

        $idToEvent = [];
        foreach ($filteredEvents as $event) {
            $idToEvent[$event['id']] = $event;
        }

        foreach ($filters as $filter) {
            if (!isset($idToEvent[$filter->event_id]))
                continue;

            try {
                $response = $client->request('GET', 'https://api.betsapi.com/v2/event/odds?token=' . $token . '&event_id=' . $filter->event_id . '&odds_market=2,3');

                $oddsMarkets = json_decode($response->getBody()->getContents(), true)['results']['odds'] ?? [];
            } catch(\Exception $e) {
                $this->info($e->getMessage());
                $oddsMarkets = [];
            }

            foreach ($oddsMarkets as $oddType => $oddsMarket) {
                if ($filter->odd_type != $oddType)
                    continue;

                foreach ($oddsMarket as $odd) {
                    $ifOddIsChecked = CheckedUsersOdd::where('chat_id', $filter->chat_id)
                        ->where('odd_id', $odd['id'])
                        ->exists();

                    if ($ifOddIsChecked)
                        continue;

                    if (((float) ($odd['handicap'] ?? 0)) >= $filter->more) {
                        $this->notifyUserAboutMoreValue(
                            $telegram, 
                            $filter, 
                            $oddType, 
                            $idToEvent,
                            ((float) ($odd['handicap'] ?? 0))
                        );
                    }

                    if (((float) ($odd['handicap'] ?? 0)) <= $filter->less) {
                        $this->notifyUserAboutLessValue(
                            $telegram, 
                            $filter, 
                            $oddType, 
                            $idToEvent,
                            ((float) ($odd['handicap'] ?? 0))
                        );
                    }

                    CheckedUsersOdd::updateOrCreate(
                        [
                            'chat_id' => $filter->chat_id,
                            'odd_id' => $odd['id'],
                        ], 
                        []);
                }
            }
        }
        $this->info('Finish running odds checker...');
    }

    private function notifyUserAboutMoreValue(
        $telegram, 
        $filter, 
        $oddType, 
        $idToEvent, 
        $oddValue
    ) {
        $link = $this->baseLink 
            . $filter->event_id
            . '/' 
            . str_replace(' ', '-', $idToEvent[$filter->event_id]['home']['name'])
            . '-v-'
            . str_replace(' ', '-', $idToEvent[$filter->event_id]['away']['name']);

        $telegram->sendMessage([
            'chat_id' => $filter->chat_id,
            'text' => 'The odd value of ' . $this->convertOddType($oddType) 
                . ' is more then ' . $filter->more . '.' ."\r\n"
                . 'Th odd value is ' . $oddValue . '.' ."\r\n"
                . '(<a href="' . $link . '">Link to the event</a>).',
            'parse_mode' => 'HTML',
        ]);
    }

    private function notifyUserAboutLessValue(
        $telegram, 
        $filter, 
        $oddType,
        $idToEvent, 
        $oddValue

    ) {
        $link = $this->baseLink 
            . $filter->event_id
            . '/' 
            . str_replace(' ', '-', $idToEvent[$filter->event_id]['home']['name'])
            . '-v-'
            . str_replace(' ', '-', $idToEvent[$filter->event_id]['away']['name']);

        $telegram->sendMessage([
            'chat_id' => $filter->chat_id,
            'text' => 'The odd value of ' . $this->convertOddType($oddType) 
                . ' is less then ' . $filter->less . '.' ."\r\n"
                . 'Th odd value is ' . $oddValue . '.' ."\r\n"
                . '(<a href="' . $link . '">Link to the event</a>).',
            'parse_mode' => 'HTML',
        ]);
    }

    private function convertOddType($oddType) 
    {
        if ($oddType === '18_2') return 'Spread';
        if ($oddType === '18_3') return 'Total Points';
    }
}