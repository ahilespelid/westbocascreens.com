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

    /**
     * @var array<string, string> Старые упоминания бренда и их замена. Порядок важен:
     * длинные фразы идут раньше коротких, иначе «Apex Flow» внутри них заменился бы первым.
     */
    private const OLD_BRAND = [
        'Wow, the Apex Flow isn&#039;t changing.' => '',
        'Wow, the Apex Flow isn’t changing.' => '',
        "Wow, the Apex Flow isn't changing." => '',
        'Wow Apex Flow' => Config::BRAND,
        'Apex Flow' => Config::BRAND,
    ];

    /**
     * @var array<string, string> Длинные тире и их замена. На сайте принят обычный дефис:
     * так текст одинаково выглядит в любом шрифте и не зависит от кодировки.
     */
    private const LONG_DASH = ['&mdash;' => '-', '&ndash;' => '-', '—' => '-', '–' => '-'];

    /** @var int Номер миграции, до которой должна быть доведена база. */
    private const VERSION = 8;

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
        if ($current_version < 3) {
            self::purgePageRevisions();
            self::scrubStrings(self::OLD_BRAND, ['Apex Flow']);
        }
        if ($current_version < 4) {
            self::syncPagesFromFiles();
            self::scrubStrings(self::LONG_DASH, array_keys(self::LONG_DASH));
        }
        if ($current_version < 5) {
            // Блок видео перестроен под несколько роликов (новая разметка,
            // другие width/height) — обновлённый снимок должен попасть в базу.
            self::syncPagesFromFiles();
        }
        if ($current_version < 6) {
            // Главная перестроена: видео виллы поднято наверх, в «See It In Action»
            // остался только новый ролик, перед отзывами добавлено фото семьи.
            self::syncPagesFromFiles();
        }
        if ($current_version < 7) {
            // Автозапуск у ролика виллы наверху выключен: играет только по клику
            // посетителя, автозапуск остался только у ролика в «See It In Action».
            self::syncPagesFromFiles();
        }
        if ($current_version < 8) {
            // Автозапуск у ролика виллы включён обратно: теперь оба ролика на странице
            // играют со звуком по очереди - тот, что в зоне видимости, остальные на паузе
            // (логика в video.js), плюс на страницу добавлено фото навеса.
            self::syncPagesFromFiles();
        }

        // Номер пишем после успеха; autoload — чтобы проверка выше не ходила в базу.
        update_option(self::OPTION, self::VERSION, true);
    }

    /**
     * Миграция 3, часть 1: ревизии и автосохранения страниц. Тексты страниц живут в git
     * с полной историей, а ревизии в базе хранили старые версии со старым брендом.
     *
     * @return void
     */
    private static function purgePageRevisions(): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

        // Идентификаторы всех ревизий и автосохранений, чей родитель — страница.
        $revision_ids = $wpdb->get_col(
            "SELECT r.ID FROM {$wpdb->posts} r JOIN {$wpdb->posts} p ON p.ID = r.post_parent WHERE r.post_type = 'revision' AND p.post_type = 'page'"
        );

        // wp_delete_post_revision, а не DELETE: ядро заодно уберёт мета-поля и кэш ревизии.
        array_map('wp_delete_post_revision', array_map('intval', $revision_ids));
    }

    /**
     * Замена текста по всей базе: в записях любых типов, их мета-полях и настройках.
     * Сериализованные значения идут через API WordPress: SQL REPLACE поменял бы длину
     * строки и сломал сериализацию.
     *
     * @param array<string, string> $map Что на что менять.
     * @param string[] $needles Подстроки для поиска строк-кандидатов в базе.
     * @return void
     */
    private static function scrubStrings(array $map, array $needles): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

        // По одному проходу на подстроку: проще одного запроса с OR и так же идемпотентно.
        foreach ($needles as $needle) {
            self::scrubByNeedle($map, '%' . $wpdb->esc_like($needle) . '%');
        }
    }

    /**
     * Один проход замены по строкам базы, содержащим заданную подстроку.
     *
     * @param array<string, string> $map Что на что менять.
     * @param string $like Готовый шаблон LIKE.
     * @return void
     */
    private static function scrubByNeedle(array $map, string $like): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

        // Текстовые поля записей: сериализации там нет, поэтому строковая замена безопасна.
        $post_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_title LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s",
            $like,
            $like,
            $like
        ), ARRAY_A);
        foreach ($post_rows as $post_row) {
            // ID отделяем, остальное — поля для замены.
            $post_id = (int) array_shift($post_row);
            $wpdb->update($wpdb->posts, self::replaceStrings($post_row, $map), ['ID' => $post_id]);
            clean_post_cache($post_id);
        }

        // Мета-поля записей: чтение и запись через API, чтобы массивы пересериализовались.
        $meta_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
            $like
        ));
        foreach ($meta_rows as $meta_row) {
            // Каждое значение ключа — отдельно: у одного ключа их может быть несколько.
            foreach (get_post_meta((int) $meta_row->post_id, $meta_row->meta_key) as $meta_value) {
                update_post_meta((int) $meta_row->post_id, $meta_row->meta_key, self::replaceStrings($meta_value, $map), $meta_value);
            }
        }

        // Настройки: то же самое через get_option / update_option.
        $option_names = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s",
            $like
        ));
        foreach ($option_names as $option_name) {
            update_option($option_name, self::replaceStrings(get_option($option_name), $map));
        }
    }

    /**
     * Применяет карту замен к строке или рекурсивно ко всем строкам массива/объекта.
     *
     * @param mixed $value Значение любого типа.
     * @param array<string, string> $map Что на что менять.
     * @return mixed То же значение с заменой; нестроковые скаляры — без изменений.
     */
    private static function replaceStrings(mixed $value, array $map): mixed
    {
        // Строка — основной случай: str_replace с массивами применяет пары по порядку.
        if (is_string($value)) {
            return str_replace(array_keys($map), array_values($map), $value);
        }

        // Массив — рекурсивно по всем элементам с сохранением ключей.
        if (is_array($value)) {
            return array_map(static fn (mixed $item): mixed => self::replaceStrings($item, $map), $value);
        }

        // Объект — по публичным свойствам; сам объект остаётся тем же экземпляром.
        if (is_object($value)) {
            foreach (get_object_vars($value) as $property => $property_value) {
                $value->$property = self::replaceStrings($property_value, $map);
            }
        }

        // Числа, булевы и null не меняются.
        return $value;
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

        // Снимок текстов страниц из файлов.
        self::syncPagesFromFiles();

        // Старое имя в SEO-мета страниц: код их перекрывает, но в базе им тоже не место.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key IN ('_sc_meta_title', '_sc_meta_description', '_sc_service_name')",
            'Wow Apex Flow',
            Config::BRAND
        ));
    }

    /**
     * Переписывает текст страниц в базе снимком их файлов разметки. Сайт и так
     * показывает страницы из файлов, но копия в базе уходит в бэкапы и в админку,
     * поэтому она не должна отставать. Шорткоды остаются шорткодами: формы, nonce
     * и списки отзывов собираются в момент показа, не здесь.
     *
     * @return void
     */
    private static function syncPagesFromFiles(): void
    {
        // Объект доступа к базе WordPress с уже подставленными префиксами таблиц.
        global $wpdb;

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
