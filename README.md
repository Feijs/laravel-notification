# Laravel 4 Notification
======

A basic starting point for a flexible user notification system in Laravel 4.

It is easily extendable with new notification types and leaves rendering completely up to you.

This package only provides an extendable notification system without any controllers or views
since they are often very use case specific.

I'm open to ideas for extending this package.

## Fork 
This fork allows for:
* Notifications which can be read by any user with a specific role or permission
* Different namespaces for specific notification implementations 
* Array or object as attached data

## Installation

### 1. Install with Composer

Add the following to your `composer.json` and run **composer update**

```json
	"require": {
		//...
		"tricki/laravel-notification": "dev-master"
	},
	"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Feijs/laravel-notification"
        }
    ],
```

### 2. Add to Providers in `config/app.php`

```php
    'providers' => [
        // ...
        'Tricki\Notification\NotificationServiceProvider',
    ],
```

This registers the package with Laravel and automatically creates an alias called
`Notification`.

### 3. Publishing config

You may specify whether your project uses `Role` and `Permission` classes as defined in `Zizaco\Entrust`. This is on by default. 
If your project does not, or you want to use only users as observers, you need to change this in the config.

Publish the package configuration using Artisan:

```bash
php artisan config:publish tricki/laravel-notification
```

Set the `entrust` property of the newly created `app/config/packages/tricki/laravel-notification/config.php`
to false

### 4. Executing migration

```bash
php artisan migrate --package="tricki/laravel-notification"
```

This will create the tables **notifications** and **notification_observer**

## Usage

### 1. Define notification models

The class `Models\Notification` may be extended for application specific functionality. 
Some examples would be `PostLikedNotification` or `CommentPostedNotification`.

These models define the unique behavior of each notification type, such as it's actions
and rendering.

A minimal notification model looks like this:

```php
<?php namespace MyApp;

class PostLikedNotification extends \Tricki\Notification\Models\Notification
{
	protected $isSuperType = false;
    protected $isSubType = true;
	public static $type = 'MyApp\PostLikedNotification';
}
```

The type variable is used to differentiate the various notification types while retaining a single database table 
(single-table inheritance). The class name **must** be the namespaced class.

### 2. Create a notification

Notifications can be created using `Notification::create`.

The function takes 5 parameters:

 * **$type** string
   The notification type (see [Define notification models](#1-define-notification-models))
 * **$observers** array | Collection
   Any users, roles and permissions which may read this notification
 * **$sender** Model | Null
   An object that initiated the notification (a user, a group, a web service etc.)
 * **$object** Model | Null
   An object that was changed (a post that has been liked).
 * **$data** mixed | Null
   Any additional data you want to attach. This will be serialized into the database.


### 3. Get an instance of `Model\Notification`

You will need one to fetch or update notifications

```php
use \Tricki\Notification\Models\Notification as NotificationModel;
public function __construct(NotificationModel $notification)
{
	 $this->notification = $notification;
}

// Or

$this->notification = App::make('Tricki\Notification\Model\Notification');

//Or 

use \Tricki\Notification\Model\Notification as NotificationModel;

$this->notification = new NotificationModel;
```

### 4. Retrieving notifications

To get a collection of notifications a user may read:

```php
$user = User::find($id);

$read 	= $this->notification->fetch($user, true);
$unread = $this->notification->fetch($user, false);
```

This will return any notifications the user may read, based on itself and it's attached roles and permissions.

### 5. Mark as read

A notification may be marked as read with the `markRead` method.

```php
	$instance = $this->notification->find($notification_id);
	if(!is_null($instance)) {
		$instance->markRead($user->id);
	}
```

It will only be marked as read for this specific user. If attached to a role or permission, 
other users with this role or permission will still see this notification as unread.

## Example:

```php
<?php namespace MyApp;

class PostLikedNotification extends \Tricki\Notification\Models\Notification
{
	public static $type = 'MyApp\PostLikedNotification';

	/** 
     * Return the main title for this message 
     * @return string
     */
    public function getTitleAttribute() 
    {
    	return isset($this->data['title']) ? $this->data['title'] : "Post liked";
    }

    /** 
     * Return the url for a corresponding action 
     * @return string
     */
    public function getActionUrl()
    {
    	return app()['url']->to('posts/' . $this->object->id);
    }
}

```