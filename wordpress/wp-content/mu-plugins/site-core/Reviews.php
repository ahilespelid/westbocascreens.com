<?php
/**
 * Отзывы клиентов: тип записи, публичная форма с модерацией, постраничный вывод
 * и расчёт средней оценки, который использует схема AggregateRating.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Reviews
{
    /** @var string Тип записи, в котором хранятся отзывы. */
    public const POST_TYPE = 'sc_review';

    /** @var string Мета-поле с оценкой от 1 до 5. */
    private const META_RATING = '_sc_rating';

    /** @var string Имя действия admin-post, обрабатывающего отправку формы. */
    private const ACTION = 'sc_submit_review';

    /** @var int Сколько отзывов показывать на одной странице. */
    private const PER_PAGE = 3;

    /** @var int Оценка по умолчанию, если поле почему-то пустое. */
    private const DEFAULT_RATING = 5;

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Тип записи регистрируется на init — раньше ядро к этому не готово.
        add_action('init', [self::class, 'registerPostType']);

        // Одна и та же обработка для гостей и авторизованных: форма публичная.
        add_action('admin_post_' . self::ACTION, [self::class, 'handleSubmit']);
        add_action('admin_post_nopriv_' . self::ACTION, [self::class, 'handleSubmit']);

        // Список отзывов с постраничной навигацией.
        add_shortcode('sc_reviews_list', [self::class, 'renderList']);

        // Форма отправки отзыва.
        add_shortcode('sc_review_form', [self::class, 'renderForm']);
    }

    /**
     * Регистрирует тип записи «Отзывы»: виден в админке, но не имеет своих страниц на сайте.
     *
     * @return void
     */
    public static function registerPostType(): void
    {
        // public => false и show_ui => true: отзывы редактируются в админке, но своего URL не получают.
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => 'Reviews',
                'singular_name' => 'Review',
                'all_items'     => 'Reviews',
            ],
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-star-filled',
            'supports'        => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Принимает отзыв с публичной формы и кладёт его на модерацию.
     *
     * @return void Метод всегда завершается редиректом.
     */
    public static function handleSubmit(): void
    {
        // Возвращаемся на ту же страницу, откуда пришла форма; запасной вариант — страница отзывов.
        $redirect_url = wp_get_referer() ?: home_url('/reviews/');

        // Nonce защищает от отправки формы со стороннего сайта.
        $nonce = isset($_POST['sc_review_nonce']) ? sanitize_text_field(wp_unslash($_POST['sc_review_nonce'])) : '';

        // Проверка не прошла — дальше не идём, чтобы не плодить мусорные записи.
        if (!wp_verify_nonce($nonce, self::ACTION)) {
            self::redirect($redirect_url, 'error');
        }

        // Honeypot: поле скрыто от людей, его заполняют только боты.
        if (!empty($_POST['sc_website'])) {
            // Боту показываем обычное «спасибо» — пусть считает, что всё получилось.
            self::redirect($redirect_url, 'thanks');
        }

        // Имя и текст — обязательные поля, оценка нормализуется к диапазону 1–5.
        $author_name = sanitize_text_field(wp_unslash($_POST['sc_name'] ?? ''));
        $review_text = sanitize_textarea_field(wp_unslash($_POST['sc_review_text'] ?? ''));
        $rating = self::normalizeRating($_POST['sc_rating'] ?? self::DEFAULT_RATING);

        // Пустая форма — возвращаем с ошибкой, ничего не сохраняя.
        if ($author_name === '' || $review_text === '') {
            self::redirect($redirect_url, 'error');
        }

        // Статус pending: отзыв попадёт на сайт только после одобрения в админке.
        $post_id = wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => $author_name,
            'post_content' => $review_text,
            'post_status'  => 'pending',
        ]);

        // Оценка живёт в мета-поле; сохраняем только если запись действительно создалась.
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, self::META_RATING, $rating);
        }

        // Успех: благодарим и предупреждаем, что отзыв появится после проверки.
        self::redirect($redirect_url, 'thanks');
    }

    /**
     * Список опубликованных отзывов с постраничной навигацией.
     *
     * @return string HTML списка.
     */
    public static function renderList(): string
    {
        // Номер страницы приходит в query-параметре; отрицательные и нули отсекаются.
        $current_page = isset($_GET['revpage']) ? max(1, (int) $_GET['revpage']) : 1;

        // Выборка только опубликованных отзывов, новые сверху.
        $query = new \WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => self::PER_PAGE,
            'paged'          => $current_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ob_start();

        // Ни одного отзыва — вместо пустоты приглашаем оставить первый.
        if (!$query->have_posts()) {
            echo '<p class="sc-reviews-empty">Be the first to leave a review!</p>';
            return (string) ob_get_clean();
        }

        echo '<div class="sc-reviews-grid">';

        // Стандартный цикл WordPress: the_post() подставляет текущий отзыв в глобальные функции.
        while ($query->have_posts()) {
            $query->the_post();

            // Оценка из мета-поля; пустое значение трактуем как максимальное.
            $rating = self::normalizeRating(get_post_meta(get_the_ID(), self::META_RATING, true));
            ?>
            <div class="sc-review-card">
                <?php echo Blocks::stars($rating); ?>
                <p class="sc-review-text">&ldquo;<?php echo nl2br(esc_html(get_the_content())); ?>&rdquo;</p>
                <div class="sc-review-author">- <?php echo esc_html(get_the_title()); ?></div>
            </div>
            <?php
        }

        echo '</div>';

        // Навигация нужна, только если отзывов больше, чем помещается на страницу.
        if ($query->max_num_pages > 1) {
            self::renderPagination($current_page, (int) $query->max_num_pages);
        }

        // Глобальный контекст записи обязательно возвращаем на место после своего цикла.
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    /**
     * Форма отправки отзыва с уведомлением о результате предыдущей отправки.
     *
     * @return string HTML формы.
     */
    public static function renderForm(): string
    {
        ob_start();

        // Результат предыдущей отправки приходит в query-параметре после редиректа.
        $submit_result = isset($_GET['sc_review']) ? sanitize_key($_GET['sc_review']) : '';

        // Успех и ошибка отличаются только классом и текстом — разводим их одним условием.
        if ($submit_result === 'thanks') {
            echo '<div class="sc-review-notice sc-review-success">Thank you! Your review has been submitted and will appear once approved.</div>';
        } elseif ($submit_result === 'error') {
            echo '<div class="sc-review-notice sc-review-error">Please fill in your name and review, then try again.</div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sc-review-form">
            <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
            <?php wp_nonce_field(self::ACTION, 'sc_review_nonce'); ?>
            <div class="sc-honeypot" aria-hidden="true">
                <label>Leave this field empty<input type="text" name="sc_website" tabindex="-1" autocomplete="off"></label>
            </div>
            <label>Your Name
                <input type="text" name="sc_name" required maxlength="80">
            </label>
            <label>Rating
                <select name="sc_rating">
                    <?php foreach (self::ratingOptions() as $value => $label): ?>
                        <option value="<?php echo esc_attr((string) $value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Your Review
                <textarea name="sc_review_text" required maxlength="1000" rows="5"></textarea>
            </label>
            <button type="submit" class="sc-header-cta">Submit Review</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Средняя оценка и число опубликованных отзывов.
     *
     * @return array{value: float, count: int}|null Null, если отзывов ещё нет.
     */
    public static function averageRating(): ?array
    {
        // Берём только идентификаторы: значения оценок лежат в мета-полях, сами тексты не нужны.
        $review_ids = get_posts([
            'post_type'   => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);

        // Пустой список — считать нечего.
        if ($review_ids === []) {
            return null;
        }

        // Суммируем нормализованные оценки: одна кривая запись не должна портить среднее.
        $rating_sum = array_sum(array_map(
            static fn (int $review_id): int => self::normalizeRating(get_post_meta($review_id, self::META_RATING, true)),
            $review_ids
        ));

        // Округление до десятых — ровно та точность, которую показывает поиск.
        return [
            'value' => round($rating_sum / count($review_ids), 1),
            'count' => count($review_ids),
        ];
    }

    /**
     * Постраничная навигация по отзывам.
     *
     * @param int $current_page Номер текущей страницы.
     * @param int $total_pages Всего страниц.
     * @return void
     */
    private static function renderPagination(int $current_page, int $total_pages): void
    {
        echo '<div class="sc-review-pagination">';

        // Страниц немного (3 отзыва на страницу), поэтому показываем их все без «…».
        for ($page_number = 1; $page_number <= $total_pages; $page_number++) {
            // Ссылка сохраняет остальные query-параметры и ведёт к якорю блока отзывов.
            $page_url = esc_url(add_query_arg('revpage', $page_number));

            // Текущая страница подсвечивается отдельным классом.
            $active_class = $page_number === $current_page ? ' sc-page-current' : '';

            echo '<a class="sc-page-link' . $active_class . '" href="' . $page_url . '#reviews">' . $page_number . '</a>';
        }

        echo '</div>';
    }


    /**
     * Приводит произвольное значение к оценке в диапазоне 1–5.
     *
     * @param mixed $raw_rating Значение из формы или мета-поля.
     * @return int Оценка от 1 до 5.
     */
    private static function normalizeRating(mixed $raw_rating): int
    {
        // Пустое мета-поле означает «оценки нет» — считаем её максимальной, как и раньше.
        if ($raw_rating === '' || $raw_rating === null) {
            return self::DEFAULT_RATING;
        }

        // Зажимаем в границы: из формы может прийти что угодно.
        return max(1, min(5, (int) $raw_rating));
    }

    /**
     * Варианты оценки для выпадающего списка формы.
     *
     * @return array<int, string> Оценка => подпись со звёздами.
     */
    private static function ratingOptions(): array
    {
        // Порядок от лучшей к худшей: первая опция выбрана по умолчанию.
        return [
            5 => '★★★★★ Excellent',
            4 => '★★★★☆ Great',
            3 => '★★★☆☆ Good',
            2 => '★★☆☆☆ Fair',
            1 => '★☆☆☆☆ Poor',
        ];
    }

    /**
     * Редирект с меткой результата и немедленный выход.
     *
     * @param string $redirect_url Куда возвращать посетителя.
     * @param string $result Метка результата: thanks или error.
     * @return never
     */
    private static function redirect(string $redirect_url, string $result): never
    {
        // wp_safe_redirect не выпустит посетителя на чужой домен, даже если referer подделан.
        wp_safe_redirect(add_query_arg('sc_review', $result, $redirect_url));

        // Без exit выполнение продолжится и WordPress допечатает страницу после заголовка Location.
        exit;
    }
}
