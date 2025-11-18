<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('posisi');
            $table->string('no_telepon', 20)->nullable()->after('alamat');
            $table->string('foto_profil')->nullable()->after('no_telepon');
        });
    }

    public function down()
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'no_telepon', 'foto_profil']);
        });
    }
};
