<?php

/**
 * Class namespace
 */
namespace App;

/**
 * Used packages
 */
use Illuminate\Database\Eloquent\Model;

/**
 * Class for managing checked telegram messages
 *
 * Class CheckedTelegramMessages
 * @package App
 */
class CheckedTelegramMessages extends Model
{
    /**
     * Model table
     *
     * @var string
     */
    protected $table = 'checked_telegram_messages';

    /**
     * Fillable properties of the model
     *
     * @var array
     */
    protected $fillable = [
        'message_id'
    ];
}
