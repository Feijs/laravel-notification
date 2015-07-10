<?php

namespace Tricki\Notification\Models;

use User;
use Eloquent;
use Config;
use Carbon\Carbon;
use Event;

/**
 * The main Notification class
 *
 * @package Tricki/Laravel-notification
 * @author Thomas Rickenbach
 * @author Mike Feijs <mike@feijs.nl>
 */
class Notification extends AbstractEloquent
{
	protected $isSuperType = true;

	protected $table = 'notifications';

	public static $type = '';

	/** 
	 * Users which may read this notification 
	 * @return Illuminate\Database\Eloquent\Relations\MorphToMany
	 */
	public function users()
	{
		return $this->morphedByMany(Config::get('auth.model'), 'observer', 'notification_observer', 'notification_id')->withTimestamps();
	}

	/** 
	 * Roles which give the ability to read this notification 
	 * @return Illuminate\Database\Eloquent\Relations\MorphToMany
	 */
	public function roles()
	{
		return $this->morphedByMany('Role', 'observer', 'notification_observer', 'notification_id')->withTimestamps();
	}

	/** 
	 * Permissions which give the ability to read this notification 
	 * @return Illuminate\Database\Eloquent\Relations\MorphToMany
	 */
	public function permissions()
	{
		return $this->morphedByMany('Permission', 'observer', 'notification_observer', 'notification_id')->withTimestamps();
	}

	public function sender()
	{
		return $this->morphTo();
	}

	public function object()
	{
		return $this->morphTo();
	}

	public function scopeUnread($query)
	{
		return $query->wherePivot('read_at', NULL);
	}

	public function scopeRead($query)
	{
		return $query->wherePivotNot('read_at', NULL);
	}

	public function newPivot(Eloquent $parent, array $attributes, $table, $exists)
	{
		return new NotificationObserver($parent, $attributes, $table, $exists);
	}

	protected function isSubType()
	{
		return get_class() !== get_class($this);
	}

	protected function getClass($type)
	{
		return \Notification::getClass($type);
	}

	protected function getType()
	{
		return static::$type;
	}

	/**
	 * Fetch all notifications the given user may read
	 * @param User $user
	 * @param boolean $read
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function fetch(User $user, $read = false)
	{
		return $this->fetchQuery($user, $read)->get();
	}

	/**
	 * Fetch all notifications the given user may read
	 * @param User $user
	 * @param boolean $read
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function count(User $user, $read = false)
	{
		return $this->fetchQuery($user, $read)->count();
	}

	/**
	 * Create a query for notifications the given user may read
	 * @param User $user
	 * @param boolean $read
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	protected function fetchQuery(User $user, $read = false)
	{
		/*-----------------------------------------
		 * Simple query with users only
		 */
		if(!Config::get('notification::entrust')) {
			return $this->whereHas('users', function($subq) use ($user, $read)
			{	
				$subq->where('users.id', '=', $user->id)
				     ->whereNull('read_at', 'and', $read);
			});
		}

		/*-----------------------------------------
		 * Extended query with roles & permissions
		 */

		//Subquery to fetch all role ids
		$roles = $user->roles;
		$role_ids = $roles->lists('id');

		//Subquery to fetch all permission ids
		$permission_ids = [];
		foreach($roles as $role) {
			$permission_ids = array_merge($permission_ids, $role->perms->lists('id'));
		}

		return 
			$this->where(function($mainq) use ($user, $role_ids, $permission_ids, $read)
			{
				$mainq->whereHas('users', function($subq) use ($user, $read)
				{	
					$subq->where('users.id', '=', $user->id)
					     ->whereNull('read_at', 'and', $read);
				})
				->orWhereHas('roles', function($subq) use ($role_ids, $read) 
				{
					$subq->whereIn('roles.id', $role_ids)
					     ->whereNull('read_at', 'and', $read);
				})
				->orWhereHas('permissions', function($subq) use ($permission_ids, $read) 
				{
					$subq->whereIn('permissions.id', $permission_ids)
					     ->whereNull('read_at', 'and', $read);
				});
			})->whereHas('users', function($subq) use ($user, $read) 
			{
				$subq->where('users.id', '=', $user->id)
				     ->whereNull('read_at', 'and', !$read);
			}, '<', 1);	
	}


	/**
	 * Mark this notification as read by the specified user
	 *  
	 * @param int $user_id
	 */
	public function markRead($user_id)
	{
		$this->users()->sync([ $user_id => ['read_at' => Carbon::now()] ]);
	}

	/*----------------------------
     * Accessors and Mutators
     *---------------------------*/

    /** 
     * Unserializes & returns the data array
     *
     * @return string[]
     */
    public function getDataAttribute()
    {
        return unserialize($this->attributes['data']);
    }

	/** 
     * Serializes & stores the data array
     *
     * @param string[]
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = serialize($value);
    }

}
