<?php

namespace Tricki\Notification;

use Illuminate\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Class for creation of Notification models
 *
 * @package Tricki/Laravel-notification
 * @author Thomas Rickenbach
 * @author Mike Feijs <mike@feijs.nl>
 */
class Notification
{
	protected $events;

	public function __construct(Dispatcher $events)
	{
	  	$this->events = $events;
	}

	/**
	 * Creates a notification and assigns it to some users
	 *
	 * @param string $class The full notification class
	 * @param mixed $observers The user(s) which should receive this notification.
	 * @param Model|NULL $sender The object that initiated the notification (a user, a group, a web service etc.)
	 * @param Model|NULL $object An object that was changed (a post that has been liked).
	 * @param mixed|NULL $data Any additional data
	 *
	 * @return \Tricki\Notification\Models\Notification
	 */
	public function create($class, $observers = array(), Model $sender = null, Model $object = NULL, $data = NULL)
	{
		$notification = new $class();

		if ($data)
		{
			$notification->data = $data;
		}
		if ($sender) 
		{
			$notification->sender()->associate($sender);
		}
		if ($object)
		{
			$notification->object()->associate($object);
		}
		$notification->save();

		foreach($observers as $observer) 
		{
			switch(get_class($observer)) 
			{
				case 'User':
					$notification->users()->attach($observer->id);
					break;
				case 'Role':
					$notification->roles()->attach([$observer->id]);
					break;
				case 'Permission':
					$notification->permissions()->attach([$observer->id]);
					break;
			}
		}

		//Notify app about the new notification
		$this->fireNotificationAssigned($notification, $observers);

		return $notification;
	}

	protected function fireNotificationAssigned($notification, $observers = array())
	{
		$event = $this->events->fire('notification.assigned', [
			'notification' => $notification,
			'observers' => $observers
		]);
	}
}
