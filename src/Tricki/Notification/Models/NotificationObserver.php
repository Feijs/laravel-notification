<?php

namespace Tricki\Notification\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use \Illuminate\Database\Eloquent\SoftDeletingTrait;

/**
 * Pivot relation for entities which have or give access to read a Notification 
 *
 * @package Tricki/Laravel-notification
 * @author Thomas Rickenbach
 * @author Mike Feijs <mike@feijs.nl>
 */
class NotificationObserver extends Pivot
{

	use SoftDeletingTrait;

	protected $table = 'notification_observer';
	protected $dates = ['deleted_at', 'read_at'];
	protected $visible = ['observer_id', 'observer_type', 'notification_id', 'created_at', 'updated_at', 'read_at'];

	public function observer()
	{
		return $this->morphTo();
	}

	public function notification()
	{
		return $this->belongsTo('Tricki\Notification\Models\Notification');
	}

}
