<?php
/**
 * Одноразовые миграции данных в базе. Код меняется коммитом, а база — нет, поэтому
 * всё, что переименовано в коде и хранится в базе, переименовывается здесь: при первом
 * запросе после деплоя, ровно один раз. Номер выполненной миграции хранится в опции.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Migration
{
    /** @var string Опция с номером последней выполненной миграции. */
    private const OPTION = 'sc_schema_version';

    /** @var int Номер миграции, до которой должна быть доведена база. */
    private const VERSION = 1;

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Приоритет 0 — раньше регистрации типа записи отзывов и любых запросов к ним.
        add_action('init', [self::class, 'run'], 0);
    }

    /**
     * Доводит базу до актуальной версии. Уже доведённая — выход за одно чтение опции,
     * которая WordPress держит в памяти, так что на каждом запросе это бесплатно.
     *
     * @return void
     */
    public static function run(): void
    {
        // Версия базы; до первой миграции опции нет — считаем нулём.
        $current_version = (int) get_option(self::OPTION, 0);

        // Самый частый случай — делать нечего.
        if ($current_version >= self::VERSION) {
            return;
        }

        // Миграции идут по порядку: так база догонит код с любой старой версии.
        if ($current_version < 1) {
            self::renamePrefix();
        }

        // Номер пишем после успеха; autoload — чтобы проверка выше не ходила в базу.
        update_option(self::OPTION, self::VERSION, true);
    }

    /**
     * Миграция 1: старый префикс afn → sc. Тип записи отзывов, мета-ключи страниц
     * и отзывов, шорткоды в тексте страниц. Все запросы идемпотентны: повторный
     * запуск при гонке двух запросов ничего не испортит.
     *
     * @return void
     */
    private static function renamePrefix(): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

        // Отзывы: меняется только имя типа записи, сами записи и их id остаются.
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sc_review' WHERE post_type = 'afn_review'");

        // Мета-ключи вида _afn_rating → _sc_rating: отрезаем пять символов «_afn_», добавляем «_sc_».
        $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_key = CONCAT('_sc_', SUBSTRING(meta_key, 6)) WHERE meta_key LIKE '\\_afn\\_%'");

        // Шорткоды в тексте страниц из базы: открывающие и закрывающие теги.
        $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE(REPLACE(post_content, '[afn_', '[sc_'), '[/afn_', '[/sc_') WHERE post_content LIKE '%[afn\\_%'");
    }
}
