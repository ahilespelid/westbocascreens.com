<?php
/**
 * Одноразовые миграции данных в базе. Код меняется коммитом, а база — нет, поэтому
 * всё, что изменено в коде и хранится в базе, догоняется здесь: при первом
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
    private const VERSION = 2;

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
        if ($current_version < 2) {
            self::syncBrandToDatabase();
        }

        // Номер пишем после успеха; autoload — чтобы проверка выше не ходила в базу.
        update_option(self::OPTION, self::VERSION, true);
    }

    /**
     * Миграция 2: база догоняет код. Сайт и так показывает всё из кода, но база уходит
     * в бэкапы, админку и к плагинам, которые читают её напрямую, — там не должно
     * остаться ни старого имени, ни устаревших текстов страниц.
     *
     * @return void
     */
    private static function syncBrandToDatabase(): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

        // Название и слоган пишем в таблицу напрямую: update_option сравнил бы новое значение
        // со «старым», а его подменяет фильтр pre_option из Layout, — и запись бы пропустил.
        foreach (['blogname' => Config::BRAND, 'blogdescription' => Config::TAGLINE] as $option_name => $option_value) {
            $wpdb->update($wpdb->options, ['option_value' => $option_value], ['option_name' => $option_name]);
        }

        // Сбрасываем кэш опций, иначе до конца запроса WordPress держал бы в памяти старое.
        wp_cache_delete('alloptions', 'options');

        // Текст каждой страницы с файлом разметки — снимок этого файла. Шорткоды остаются
        // шорткодами: формы, nonce и списки отзывов собираются в момент показа, не здесь.
        foreach (Content::slugs() as $slug) {
            // Страница с таким слагом; в базе её может и не быть — тогда писать некуда.
            $page = get_page_by_path($slug, OBJECT, 'page');
            if (!$page instanceof \WP_Post) {
                continue;
            }

            // Прямой UPDATE, а не wp_update_post: без ревизий, хуков и смены даты изменения.
            $wpdb->update($wpdb->posts, ['post_content' => (string) Content::render($slug)], ['ID' => $page->ID]);

            // Кэш объекта записи — чтобы следующее чтение увидело новый текст.
            clean_post_cache($page->ID);
        }

        // Старое имя в SEO-мета страниц: код их перекрывает, но в базе им тоже не место.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key IN ('_sc_meta_title', '_sc_meta_description', '_sc_service_name')",
            'Wow Apex Flow',
            Config::BRAND
        ));
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
