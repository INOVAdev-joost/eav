<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendAttributeSetsWithFrontendLabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attribute_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('label_tag_id')->nullable();

            $table->foreign('label_tag_id')
                ->references('id')
                ->on('translation_tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attribute_sets', function (Blueprint $table) {
            $table->dropForeign(['name_tag_id']);
            $table->dropColumn('name_tag_id');
        });
    }
}
