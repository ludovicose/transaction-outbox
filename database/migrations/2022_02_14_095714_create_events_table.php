<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('transaction-outbox.table_name');

        if (empty($tableName)) {
            throw new \Exception('Error: config/transaction-outbox.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('event_id');
            $table->text('payload');
            $table->string('channel');
            $table->string('type');
            $table->dateTime('success_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('transaction-outbox.table_name');

        if (empty($tableName)) {
            throw new \Exception('Error: config/transaction-outbox.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::dropIfExists($tableName);
    }
}
