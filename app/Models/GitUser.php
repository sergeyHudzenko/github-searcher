<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\GitUserRepository;

class GitUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'login',
        'avatar_url',
        'total_repos',
        'email',
        'location',
        'created_at',
        'updated_at',
        'followers',
        'following',
        'bio',
        'full_loaded',
        'popularity',
        'popularity_by_date',
        'popularity_date'
    ];

    protected $dates = ['popularity_date'];

    protected $hidden = [
        'full_loaded'
    ];

    /**
     * Get the a repos for the user.
     */
    public function repos()
    {
        return $this->hasMany(GitUserRepository::class, 'user_id');
    }
}
