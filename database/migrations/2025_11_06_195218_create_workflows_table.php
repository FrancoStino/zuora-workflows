<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up () : void
    {
        Schema ::create ( 'workflows', function ( Blueprint $table ) {
            $table -> id (); // Zuora workflow ID
            $table -> string ( 'name' );
            $table -> text ( 'description' ) -> nullable ();
            $table -> enum ( 'state', [ 'Active', 'Inactive', 'Error' ] );
            $table -> timestamp ( 'created_on' );
            $table -> timestamp ( 'updated_on' );
            $table -> timestamps (); // Laravel timestamps
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down () : void
    {
        Schema ::dropIfExists ( 'workflows' );
    }
};
