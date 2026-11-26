<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name', 150)->nullable();
            $table->text('summary')->nullable();
            $table->string('location', 100)->nullable();
            $table->text('skills')->nullable(); // JSON array of skills (or use ->text('skills')->nullable() if storing comma-separated text)
            $table->text('education')->nullable(); // JSON array of education entries
            $table->text('experience')->nullable(); // JSON array of work experience entries
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('candidate_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
        Schema::dropIfExists('candidate_cvs');
    }
};