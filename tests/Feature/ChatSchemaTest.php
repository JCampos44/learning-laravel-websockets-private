<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('chat schema supports private and group conversations', function () {
    $tableColumns = fn (string $table): array => array_map(
        fn (object $column): string => $column->name,
        DB::select("PRAGMA table_info('$table')")
    );

    $tableIndexes = fn (string $table): array => array_map(
        fn (object $index): string => $index->name,
        DB::select("PRAGMA index_list('$table')")
    );

    $foreignKeys = fn (string $table): array => DB::select("PRAGMA foreign_key_list('$table')");

    expect(Schema::hasTable('conversations'))->toBeTrue();
    expect(Schema::hasTable('conversation_participants'))->toBeTrue();
    expect(Schema::hasTable('messages'))->toBeTrue();

    expect($tableColumns('conversations'))->toEqualCanonicalizing([
        'id',
        'type',
        'title',
        'created_by_user_id',
        'direct_key',
        'last_message_at',
        'created_at',
        'updated_at',
        'last_message_id',
    ]);

    expect($tableColumns('conversation_participants'))->toEqualCanonicalizing([
        'id',
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'last_read_at',
        'archived_at',
        'muted_until',
        'created_at',
        'updated_at',
        'last_read_message_id',
    ]);

    expect($tableColumns('messages'))->toEqualCanonicalizing([
        'id',
        'conversation_id',
        'sender_id',
        'body',
        'metadata',
        'edited_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);

    expect($tableIndexes('conversations'))->toContain('conversations_type_index');
    expect($tableIndexes('conversations'))->toContain('conversations_last_message_at_index');
    expect($tableIndexes('conversations'))->toContain('conversations_direct_key_unique');

    expect($tableIndexes('conversation_participants'))->toContain('conversation_participants_user_id_archived_at_left_at_index');
    expect($tableIndexes('conversation_participants'))->toContain('conversation_participants_conversation_id_user_id_unique');

    expect($tableIndexes('messages'))->toContain('messages_conversation_id_created_at_index');

    expect(collect($foreignKeys('conversations'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'users'
        && $foreignKey->from === 'created_by_user_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'SET NULL'))->toBeTrue();

    expect(collect($foreignKeys('conversations'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'messages'
        && $foreignKey->from === 'last_message_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'SET NULL'))->toBeTrue();

    expect(collect($foreignKeys('conversation_participants'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'conversations'
        && $foreignKey->from === 'conversation_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'CASCADE'))->toBeTrue();

    expect(collect($foreignKeys('conversation_participants'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'users'
        && $foreignKey->from === 'user_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'CASCADE'))->toBeTrue();

    expect(collect($foreignKeys('conversation_participants'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'messages'
        && $foreignKey->from === 'last_read_message_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'SET NULL'))->toBeTrue();

    expect(collect($foreignKeys('messages'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'conversations'
        && $foreignKey->from === 'conversation_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'CASCADE'))->toBeTrue();

    expect(collect($foreignKeys('messages'))->contains(fn (object $foreignKey): bool => $foreignKey->table === 'users'
        && $foreignKey->from === 'sender_id'
        && $foreignKey->to === 'id'
        && $foreignKey->on_delete === 'SET NULL'))->toBeTrue();
});
