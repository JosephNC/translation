<?php

namespace JosephNC\Translation\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    /**
     * The translations table.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The fillable translation attributes.
     *
     * @var array
     */
    protected $fillable = [
        'text',
        'data',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
