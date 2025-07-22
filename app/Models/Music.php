<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Music extends Model
{
    use HasFactory;

    protected $table = 'music';

    protected $fillable = [
        'name',
        'picture',
        'release_date',
        'choir',
        'audio_file',
        'description',
        'genre',
        'duration',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'release_date' => 'date',
        'duration' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the full URL for the picture
     */
    public function getPictureUrlAttribute()
    {
        return $this->picture ? asset('storage/' . $this->picture) : null;
    }

    /**
     * Get the full URL for the audio file
     */
    public function getAudioFileUrlAttribute()
    {
        return $this->audio_file ? asset('storage/' . $this->audio_file) : null;
    }
}
