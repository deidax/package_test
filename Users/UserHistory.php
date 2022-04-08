<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHistory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'last_permissions' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getOldUserableType()
    {
        return $this->getUserableType($this->old_userable_type);
    }

    public function getCurrentUserableType()
    {
        return $this->getUserableType($this->current_userable_type);
    }

    public function getOldUserableId()
    {
        return $this->old_userable_id;
    }

    public function restoreRecorde()
    {
        // Get the user in the users table to restore
        $user = User::find($this->user_id)->userable;
        $user->restoreToLatest(true, $this);

        return true;
    }

    private function getUserableType(string $userable_type)
    {
        return __NAMESPACE__."\\".ucfirst($userable_type);
    }
}
