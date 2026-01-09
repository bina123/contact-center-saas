<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('plan', ['starter', 'pro', 'enterprise'])->default('starter');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->integer('max_agents')->default(5);
            $table->integer('max_concurrent_calls')->default(10);
            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Users table with tenant relationship
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['active', 'inactive', 'busy', 'away'])->default('active');
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'email']);
        });

        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // admin, supervisor, agent
            $table->string('slug')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // User roles pivot
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
        });

        // Campaigns table
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['outbound', 'inbound', 'blended']);
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Calls table
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('call_id')->unique(); // External SIP call ID
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('from_number');
            $table->string('to_number');
            $table->enum('status', ['queued', 'ringing', 'in-progress', 'completed', 'failed', 'no-answer', 'busy']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->integer('wait_time')->default(0); // in seconds
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['agent_id', 'started_at']);
        });

        // Leads table
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('company')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost'])->default('new');
            $table->integer('priority')->default(0);
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['phone']);
        });

        // Tickets table
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'in-progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('channel', ['phone', 'email', 'chat', 'web'])->default('web');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        // Ticket messages
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        // Chat conversations
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_id'); // Anonymous visitor tracking
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Chat messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('sender_type', ['agent', 'visitor']);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('stripe_id')->nullable()->unique();
            $table->string('stripe_status')->nullable();
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // Invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['draft', 'pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Call recordings table
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->onDelete('cascade');
            $table->string('storage_path');
            $table->string('file_name');
            $table->integer('duration');
            $table->integer('file_size');
            $table->string('format')->default('mp3');
            $table->timestamps();
        });

        // API keys table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('key')->unique();
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('call_recordings');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
