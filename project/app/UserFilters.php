<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFilters extends Model
{
    protected $table = 'user_filters';

    protected $fillable = [
    	'chat_id',
    	'event_id',
    	'more',
    	'less',
    	'odd_type',
    ];
}
