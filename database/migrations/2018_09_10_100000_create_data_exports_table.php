<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('file_id')->nullable()->unique()->constrained()->name('data_exports_file_id_foreign');
                ->onUpdate('restrict')->onDelete('restrict');
                

            $table->string('name')->index();

            $table->integer('entries')->nullable();
            $table->integer('total');
            $table->integer('status')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->index()->name('comments_created_by_foreign');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_exports');
    }
};
