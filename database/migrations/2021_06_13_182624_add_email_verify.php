<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailVerify extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('verify_email')->comment('認証済:1、未認証:0')->default(0);
            $table->string('verify_token')->comment('認証用及びリマインダートークン')->nullable();
            $table->timestamp('verify_date')->comment('トークン発行日時')->nullable();
            $table->string('verify_email_address')->comment('仮登録メールアドレス')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verify_email');
            $table->dropColumn('verify_token');
            $table->dropColumn('verify_date');
            $table->dropColumn('verify_email_address');
        });
    }
}
