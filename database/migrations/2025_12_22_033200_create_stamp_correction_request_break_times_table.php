<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('stamp_correction_request_break_times', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stamp_correction_request_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->timestamps();

            // ★外部キー名を短くする
            $table->foreign('stamp_correction_request_id', 'scrbt_req_fk')
                ->references('id')
                ->on('stamp_correction_requests')
                ->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_request_break_times');
    }
};
