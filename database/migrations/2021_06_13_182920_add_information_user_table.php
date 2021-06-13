<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInformationUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 都道府県テーブル登録
        Schema::create('prefs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment("都道府県名");
            $table->integer('area_id')->comment("エリアID");
            $table->string('area_name')->comment("エリア名");
            $table->timestamps();
        });

        // ユーザーテーブル拡張
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_kana',128)->nullable()->comment("名前（カナ）");
            $table->date('birthday')->nullable()->comment("生年月日");
            $table->enum('gender',['male','female'])->nullable()->comment("性別");
            $table->string('zip_cd',7)->nullable()->comment("郵便番号");
            $table->BigInteger('pref_id')->nullable()->unsigned()->comment("県");
            $table->string('address1',255)->nullable()->comment("市区町村");
            $table->string('address2',255)->nullable()->comment("それ以下");
            $table->string('address3',255)->nullable()->comment("建物等");
            $table->string('phone_number',11)->nullable()->comment("電話番号");
            $table->text('memo')->nullable()->comment("備考");
            $table->softDeletes();

            $table->foreign('pref_id')->references('id')->on('prefs')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 外部キー削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_pref_id_foreign');
        });
        // テーブル削除
        Schema::dropIfExists('prefs');

        // カラム削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_kana');
            $table->dropColumn('birthday');
            $table->dropColumn('gender');
            $table->dropColumn('zip_cd');
            $table->dropColumn('pref_id');
            $table->dropColumn('address1');
            $table->dropColumn('address2');
            $table->dropColumn('address3');
            $table->dropColumn('phone_number');
            $table->dropColumn('memo');
        });
    }
}
