<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add avatar to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('role');
        });

        // Worker Profiles
        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('skills')->nullable();          // ["plumber","electrician"]
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->enum('availability', ['available', 'busy', 'unavailable'])->default('available');
            $table->string('profile_photo')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->timestamps();
        });

        // Jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // poster
            $table->string('title');
            $table->text('description');
            $table->string('skill_required');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->enum('budget_type', ['fixed', 'hourly'])->default('fixed');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->timestamps();
        });

        // Job Offers
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamps();
        });

        // Conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');            // job_offer, message, system
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();  // extra data
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Earnings
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('worker_profiles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
