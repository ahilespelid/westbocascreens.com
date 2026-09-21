/*
 * Демо-видео на главной: грузится только когда посетитель доскроллил до него,
 * поэтому не тормозит первую отрисовку. Стартует без звука — браузеры запрещают
 * автовоспроизведение со звуком, — и включает звук по первому клику посетителя.
 */
(function () {
    // Видео на странице одно; нет его — скрипт молча завершается.
    var video = document.getElementById('sc-demo-video');
    if (!video) {
        return;
    }

    // Кнопка включения и выключения звука поверх видео.
    var toggle = document.querySelector('.sc-sound-toggle');

    // Флаг, чтобы источники подставлялись ровно один раз.
    var loaded = false;

    /**
     * Подставляет реальные адреса файлов и запускает воспроизведение.
     */
    function loadVideo() {
        // Повторный вызов из другого обработчика ничего не делает.
        if (loaded) {
            return;
        }
        loaded = true;

        // До этого момента в data-src, чтобы браузер не начинал качать видео заранее.
        video.querySelectorAll('source[data-src]').forEach(function (source) {
            source.src = source.getAttribute('data-src');
        });

        // load() заставляет видео перечитать источники после подстановки адресов.
        video.load();

        // play() возвращает промис; отказ браузера — нормальная ситуация, гасим его.
        video.play().catch(function () {});
    }

    // Загрузка по факту появления видео в зоне видимости с запасом в 200 пикселей.
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadVideo();
                    observer.unobserve(video);
                }
            });
        }, { rootMargin: '200px' });
        observer.observe(video);
    } else {
        // Старый браузер без IntersectionObserver — грузим сразу, лучше так, чем никак.
        loadVideo();
    }

    // Кнопка переключает звук и меняет иконку на противоположную.
    if (toggle) {
        toggle.addEventListener('click', function (event) {
            // stopPropagation, иначе этот же клик поймает обработчик «включить звук» ниже.
            event.stopPropagation();
            video.muted = !video.muted;
            toggle.innerHTML = video.muted ? '&#128264;' : '&#128266;';
        });
    }

    // Первый клик в любом месте страницы — это тот самый жест пользователя,
    // после которого браузер разрешает звук.
    document.addEventListener('click', function () {
        if (video.muted) {
            video.muted = false;
            if (toggle) {
                toggle.innerHTML = '&#128266;';
            }
        }
    }, { once: true });
})();

/*
 * Заглушка ролика YouTube: до клика на странице только картинка и кнопка.
 * Плеер с домена youtube-nocookie.com подставляется по клику — так сторонний
 * код не грузится заранее и не ставит куки тем, кто видео не смотрит.
 */
(function () {
    // Заглушек может быть несколько — обрабатываем каждую.
    document.querySelectorAll('.sc-youtube[data-video-id]').forEach(function (facade) {
        // Один обработчик на заглушку; once — второй клик уже приходится на сам плеер.
        facade.addEventListener('click', function () {
            // Идентификатор ролика из разметки; encodeURIComponent — защита от кривых значений.
            var video_id = encodeURIComponent(facade.getAttribute('data-video-id'));

            // Плеер создаём элементом, а не строкой HTML: никакой разметки из атрибутов.
            var iframe = document.createElement('iframe');

            // autoplay — пользователь уже нажал «играть»; rel=0 — похожие ролики только с этого канала.
            iframe.src = 'https://www.youtube-nocookie.com/embed/' + video_id + '?autoplay=1&rel=0';

            // Разрешения, без которых автозапуск и полноэкранный режим не работают.
            iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;

            // Заголовок фрейма для скринридеров.
            iframe.title = 'Product video';

            // Заменяем картинку и кнопку плеером целиком.
            facade.replaceChildren(iframe);
        }, { once: true });
    });
})();
