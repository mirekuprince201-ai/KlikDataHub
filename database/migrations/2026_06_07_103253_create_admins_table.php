<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {

            $table->uuid('admin_id')->primary();

            $table->string('admin_code', 50)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('other_name', 100)->nullable();

            $table->string('username', 100)->unique();

            $table->string('email')->unique();
            $table->string('phone', 20)->unique();

            $table->string('profile_image')->nullable();

            $table->string('password');

            $table->enum('role', [
                'Super Admin',
                'Administrator',
                'Manager',
                'Staff'
            ])->default('Administrator');

            $table->enum('status', [
                'Active',
                'Inactive',
                'Suspended'
            ])->default('Active');

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};