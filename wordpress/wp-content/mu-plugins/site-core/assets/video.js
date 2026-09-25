/*
 * Демо-видео на главной: их может быть несколько, в разных секциях страницы.
 * Играет всегда только одно — то, что сейчас в зоне видимости, — и играет со
 * звуком; остальные при этом стоят на паузе. Как только посетитель доскроллил
 * до следующего ролика, предыдущий останавливается, а новый подхватывает.
 *
 * Со звуком автовоспроизведение разрешает не любой браузер, а только после
 * того, как посетитель хоть раз что-то нажал на странице (правило браузеров,
 * не наше). До первого клика/тапа ролики поэтому стартуют без звука — иначе
 * play() браузер просто отклонит и видео не запустится вовсе, — а как только
 * жест случился, у текущего видимого ролика звук включается сам.
 */
(function () {
    // Видео на странице может не быть вовсе — тогда скрипту нечего делать.
    var videos = document.querySelectorAll('.sc-demo-video');
    if (!videos.length) {
        return;
    }

    // Источники каждого видео подставляются ровно один раз.
    var loadedVideos = [];

    // Ролик, который сейчас играет; null - пока ни один не попал в зону видимости.
    var activeVideo = null;

    // true после первого клика/тапа/нажатия клавиши где угодно на странице -
    // с этого момента браузер разрешает запускать видео со звуком через JS.
    var userGestureHappened = false;

    /**
     * Подставляет реальные адреса файлов конкретного видео. До вызова адреса
     * лежат в data-атрибутах, чтобы браузер не начинал качать видео заранее.
     *
     * @param {HTMLVideoElement} video Элемент, для которого пора грузить источники.
     * @return void
     */
    function loadVideo(video) {
        // Повторный вызов для того же элемента ничего не делает.
        if (loadedVideos.indexOf(video) !== -1) {
            return;
        }
        loadedVideos.push(video);

        // webm первым - он легче, браузер сам выберет первый поддерживаемый формат.
        [['webm', 'video/webm'], ['mp4', 'video/mp4']].forEach(function (format) {
            var source = document.createElement('source');
            source.src = video.getAttribute('data-' + format[0]);
            source.type = format[1];
            video.appendChild(source);
        });

        // load() заставляет видео перечитать источники после подстановки адресов.
        video.load();
    }

    /**
     * Обновляет иконку кнопки звука под текущее состояние конкретного видео.
     *
     * @param {HTMLVideoElement} video Ролик, у которого проверяем video.muted.
     * @return void
     */
    function updateSoundIcon(video) {
        var wrap = video.closest('.sc-video-wrap');
        var toggle = wrap && wrap.querySelector('.sc-sound-toggle');
        if (toggle) {
            toggle.innerHTML = video.muted ? '&#128264;' : '&#128266;';
        }
    }

    /**
     * Ролик попал в зону видимости: останавливает предыдущий активный (если был)
     * и запускает этот - со звуком, если посетитель уже где-то кликнул, иначе
     * без звука до первого клика.
     *
     * @param {HTMLVideoElement} video Ролик, который нужно сделать активным.
     * @return void
     */
    function activate(video) {
        if (activeVideo === video) {
            return;
        }
        if (activeVideo) {
            activeVideo.pause();
        }
        activeVideo = video;

        // Источники могли ещё не подгрузиться, если оба observer'а сработали не по порядку.
        loadVideo(video);

        video.muted = !userGestureHappened;
        updateSoundIcon(video);

        // play() возвращает промис; отказ браузера - нормальная ситуация, гасим его.
        video.play().catch(function () {});
    }

    /**
     * Ролик ушёл из зоны видимости: если он был активным, ставим на паузу.
     *
     * @param {HTMLVideoElement} video Ролик, который перестал быть виден.
     * @return void
     */
    function deactivate(video) {
        if (activeVideo !== video) {
            return;
        }
        video.pause();
        activeVideo = null;
    }

    if ('IntersectionObserver' in window) {
        // Ранняя подгрузка источников с запасом в 200 пикселей - к моменту, когда
        // ролик реально станет видимым, он уже готов играть без задержки.
        var loadObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadVideo(entry.target);
                    loadObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '200px' });

        // Запуск и пауза - только когда ролик действительно на виду (60% кадра).
        var playObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    activate(entry.target);
                } else {
                    deactivate(entry.target);
                }
            });
        }, { threshold: 0.6 });

        videos.forEach(function (video) {
            loadObserver.observe(video);
            playObserver.observe(video);
        });
    } else {
        // Старый браузер без IntersectionObserver - грузим и запускаем без звука, лучше так, чем никак.
        videos.forEach(function (video) {
            loadVideo(video);
            video.muted = true;
            video.play().catch(function () {});
        });
    }

    // Первый клик/тап/нажатие клавиши где угодно на странице - тот самый жест,
    // после которого браузер разрешает звук. Включаем его у текущего видимого ролика.
    function onFirstGesture() {
        if (userGestureHappened) {
            return;
        }
        userGestureHappened = true;
        if (activeVideo && activeVideo.muted) {
            activeVideo.muted = false;
            updateSoundIcon(activeVideo);
        }
    }
    ['click', 'touchstart', 'keydown'].forEach(function (eventName) {
        document.addEventListener(eventName, onFirstGesture, { once: true });
    });

    // Кнопка звука у каждого ролика - ручной переключатель поверх автоматики,
    // на случай если посетитель хочет приглушить звук у текущего видимого видео.
    document.querySelectorAll('.sc-video-wrap').forEach(function (wrap) {
        var video = wrap.querySelector('.sc-demo-video');
        var toggle = wrap.querySelector('.sc-sound-toggle');
        if (!video || !toggle) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            // stopPropagation, иначе тот же клик поймает обработчик первого жеста выше.
            event.stopPropagation();
            userGestureHappened = true;
            video.muted = !video.muted;
            updateSoundIcon(video);
        });
    });
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
