<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION messenger.enrich_message_channel()
            RETURNS trigger AS
            $$
            DECLARE
                v_channel RECORD;
                v_msg_count INTEGER;
                v_last_msg TIMESTAMP;
                v_acc_msg_count INTEGER;
                v_cnt_year    INTEGER;
                v_cnt_month   INTEGER;
                v_cnt_week    INTEGER;
                v_cnt_day     INTEGER;
                v_cnt_hour    INTEGER;
                v_cnt_minute  INTEGER;
                v_freq        INTEGER := 1;
                v_last_freq_update TIMESTAMP;
            BEGIN
                -- 1. Ищем или создаём канал
                SELECT id, name, date_last_message, frequency, updated_at
                INTO v_channel
                FROM messenger.channels
                WHERE account_id = NEW.account_id
                  AND channel = NEW.channel
                LIMIT 1;

                IF NOT FOUND THEN
                    INSERT INTO messenger.channels (
                        messenger_id, account_id, channel, name,
                        type_channel, cnt, date_last_message,
                        frequency, created_at, updated_at
                    )
                    VALUES (
                        1,
                        NEW.account_id,
                        NEW.channel,
                        COALESCE(NEW.channel_name, 'Неизвестный канал'),
                        0,
                        0,
                        NULL,
                        1,
                        NOW(),
                        NOW()
                    )
                    RETURNING id, name, date_last_message, updated_at INTO v_channel;
                END IF;

                -- 2. Привязываем сообщения к каналу (без рекурсии)
                UPDATE messenger.messages
                SET channel_id = v_channel.id,
                    channel_name = v_channel.name
                WHERE account_id = NEW.account_id
                  AND channel = NEW.channel;

                -- 3. Считаем количество и последнюю дату
                SELECT COUNT(*), MAX(msg_date)
                INTO v_msg_count, v_last_msg
                FROM messenger.messages
                WHERE account_id = NEW.account_id
                  AND channel = NEW.channel;

                -- 4. Обновляем таблицу channels
                UPDATE messenger.channels
                SET
                    cnt = v_msg_count,
                    date_last_message = v_last_msg,
                    updated_at = NOW()
                WHERE id = v_channel.id;

                -- 5. Обновляем счётчик аккаунта
                SELECT COUNT(*) INTO v_acc_msg_count
                FROM messenger.messages
                WHERE account_id = NEW.account_id;

                UPDATE messenger.accounts
                SET cnt = v_acc_msg_count,
                    updated_at = NOW()
                WHERE id = NEW.account_id;

                -- 6. Проверяем, когда частота пересчитывалась в последний раз
                SELECT updated_at INTO v_last_freq_update
                FROM messenger.channels
                WHERE id = v_channel.id;

                IF v_last_freq_update IS NULL OR (NOW() - v_last_freq_update) >= INTERVAL '10 seconds' THEN
                    -- 7. Считаем сообщения за разные периоды
                    SELECT
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 year')   AS cnt_year,
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 month')  AS cnt_month,
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 week')   AS cnt_week,
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 day')    AS cnt_day,
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 hour')   AS cnt_hour,
                        COUNT(*) FILTER (WHERE msg_date >= NOW() - INTERVAL '1 minute') AS cnt_minute
                    INTO
                        v_cnt_year, v_cnt_month, v_cnt_week, v_cnt_day, v_cnt_hour, v_cnt_minute
                    FROM messenger.messages
                    WHERE account_id = NEW.account_id
                      AND channel = NEW.channel;

                    -- 8. Определяем частоту
                    IF v_cnt_minute > 1000 THEN
                        v_freq := 8;
                    ELSIF v_cnt_minute > 100 THEN
                        v_freq := 7;
                    ELSIF v_cnt_hour > 100 THEN
                        v_freq := 6;
                    ELSIF v_cnt_day > 100 THEN
                        v_freq := 5;
                    ELSIF v_cnt_week > 100 THEN
                        v_freq := 4;
                    ELSIF v_cnt_month > 100 THEN
                        v_freq := 3;
                    ELSIF v_cnt_year > 100 THEN
                        v_freq := 2;
                    ELSE
                        v_freq := 1;
                    END IF;

                    -- 9. Обновляем частоту, без повторного триггера
                    UPDATE messenger.channels
                    SET
                        frequency = v_freq,
                        updated_at = NOW()
                    WHERE id = v_channel.id;
                END IF;

                RETURN NEW;
            END;
            $$
            LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS messages_enrich_channel ON messenger.messages;

            CREATE TRIGGER messages_enrich_channel
            AFTER INSERT ON messenger.messages
            FOR EACH ROW
            EXECUTE FUNCTION messenger.enrich_message_channel();
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            DROP TRIGGER IF EXISTS messages_enrich_channel ON messenger.messages;
            DROP FUNCTION IF EXISTS messenger.enrich_message_channel();
        ");
    }
};
