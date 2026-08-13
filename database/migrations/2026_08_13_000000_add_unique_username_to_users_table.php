<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->renameDuplicates();

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
    }

    /**
     * Usernames were first come, no questions asked, so a database that has been
     * running a while can already hold collisions the unique index would refuse.
     * The earliest registration keeps the name; every later one gets a suffix.
     */
    private function renameDuplicates(): void
    {
        $taken = [];

        foreach (DB::table('users')->orderBy('id')->get(['id', 'username']) as $user) {
            $username = $user->username;

            for ($suffix = 2; isset($taken[$username]); $suffix++) {
                $username = $user->username.'-'.$suffix;
            }

            $taken[$username] = true;

            if ($username !== $user->username) {
                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        }
    }
};
