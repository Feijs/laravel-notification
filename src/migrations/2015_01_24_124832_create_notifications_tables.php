<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('notifications', function(Blueprint $table)
		{
			$table->increments('id');
			switch(Config::get('laravel-notification::type_format')) {
				case 'integer':
					$table->integer('type')->unsigned();
					break;
				default:
				case 'class':
					$table->string('type');
			}
			//$table->morphs('sender');
			//$table->morphs('object');
			$table->integer('sender_id')->nullable();
			$table->string('sender_type')->nullable();
			$table->integer('object_id')->nullable();
			$table->string('object_type')->nullable();
			$table->text('data')->nullable();
			$table->timestamps();
		});

		Schema::create('notification_observer', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('notification_id');
			$table->morphs('observer');
			$table->timestamp('read_at')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('notifications');
		Schema::drop('notification_observer');
	}

}
