<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CheckedUsersOdd extends Model
{
    protected $table = 'checked_users_odds';

    protected $fillable = [
    	'chat_id',
    	'odd_id',
    ];
}
