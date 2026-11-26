<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {   
        // Schema::create('roles', function (Blueprint  $table) {
        // $table->id()->index();
        // $table->string('name', 50); // VARCHAR(50), Required
        // $table->string('slug', 50)->unique(); // VARCHAR(50), Required, unique
        // $table->string('description', 255)->nullable();
        // $table->timestamps(); 
        // });


        Schema::create('users', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('avatar')->nullable();
            // Custom fields from your fillable array
            $table->string('ip_address', 45)->nullable(); // Accommodates both IPv4 and IPv6
            $table->text('user_agent')->nullable();        // Can be long depending on the browser
            $table->timestamp('password_changed_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->unsignedInteger('last_activity')->nullable(); // Or integer('last_activity')->nullable() if storing Unix timestamps
            $table->unique(['email', 'deleted_at']);           
            
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });


        

         


    }

  
        

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        //Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
        //Schema::dropIfExists('roles');
        
       
    }
};
