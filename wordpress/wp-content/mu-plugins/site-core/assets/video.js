/*
 * Демо-видео на главной: в блоке их может быть несколько (сейчас два, один за другим),
 * каждое грузится только когда посетитель доскроллил до него, поэтому не тормозит
 * первую отрисовку. Стартуют без звука — браузеры запрещают автовоспроизведение
 * со звуком, — и включают звук по первому клику посетителя.
 */
(function () {
    // Видео на странице может не быть вовсе — тогда скрипту нечего делать.
    var videos = document.querySelectorAll('.sc-demo-video');
    if (!videos.length) {
        return;
    }

    // Источники каждого видео подставляются ровно один раз.
    var loadedVideos = [];

    /**
     * Подставляет реальные адреса файлов конкретного видео и запускает воспроизведение.
     *
     * @param {HTMLVideoElement} video Элемент, для которого пора грузить источники.
     */
    function loadVideo(video) {
        // Повторный вызов для того же элемента ничего не делает.
        if (loadedVideos.indexOf(video) !== -1) {
            return;
        }
        loadedVideos.push(video);

        // Источники создаём только сейчас: до этого адреса лежат в data-атрибутах,
        // чтобы браузер не начинал качать видео заранее. webm первым — он легче.
        [['webm', 'video/webm'], ['mp4', 'video/mp4']].forEach(function (format) {
            var source = document.createElement('source');
            source.src = video.getAttribute('data-' + format[0]);
            source.type = format[1];
            video.appendChild(source);
        });

        // load() заставляет видео перечитать источники после подстановки адресов.
        video.load();

        // play() возвращает промис; отказ браузера — нормальная ситуация, гасим его.
        video.play().catch(function () {});
    }

    // Загрузка каждого видео по факту его появления в зоне видимости с запасом в 200 пикселей.
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadVideo(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '200px' });
        videos.forEach(function (video) {
            observer.observe(video);
        });
    } else {
        // Старый браузер без IntersectionObserver — грузим всё сразу, лучше так, чем никак.
        videos.forEach(loadVideo);
    }

    // У каждого видео своя кнопка звука — ищем её в той же рамке, а не по общему селектору.
    document.querySelectorAll('.sc-video-wrap').forEach(function (wrap) {
        var video = wrap.querySelector('.sc-demo-video');
        var toggle = wrap.querySelector('.sc-sound-toggle');
        if (!video || !toggle) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            // stopPropagation, иначе этот же клик поймает обработчик «включить звук» ниже.
            event.stopPropagation();
            video.muted = !video.muted;
            toggle.innerHTML = video.muted ? '&#128264;' : '&#128266;';
        });
    });

    // Первый клик в любом месте страницы — это тот самый жест пользователя,
    // после которого браузер разрешает звук. Включаем сразу у всех видео со звуком.
    document.addEventListener('click', function () {
        document.querySelectorAll('.sc-video-wrap').forEach(function (wrap) {
            var video = wrap.querySelector('.sc-demo-video');
            var toggle = wrap.querySelector('.sc-sound-toggle');
            if (video && toggle && video.muted) {
                video.muted = false;
                toggle.innerHTML = '&#128266;';
            }
        });
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
