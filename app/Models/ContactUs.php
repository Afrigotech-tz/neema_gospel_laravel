<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ContactUs extends Model
{
    protected $fillable = [
        'address',
        'phone',
        'email',
        'office_hours',
    ];


}

