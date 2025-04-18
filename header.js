document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header');
    let lastScrollY = window.scrollY;
    let ticking = false;

    // Порог прокрутки для начала эффекта (в пикселях)
    const scrollThreshold = 100;
    
    // Функция обработки прокрутки
    function updateHeader() {
        const currentScrollY = window.scrollY;
        
        // Если прокрутили вниз
        if (currentScrollY > lastScrollY) {
            // Если прокрутили больше порога, прячем шапку
            if (currentScrollY > scrollThreshold) {
                header.classList.add('hidden');
                header.classList.remove('transparent');
            }
        } else {
            // Если прокрутили вверх
            header.classList.remove('hidden');
            // Если прокрутили больше порога, делаем полупрозрачной
            if (currentScrollY > scrollThreshold) {
                header.classList.add('transparent');
            } else {
                header.classList.remove('transparent');
            }
        }
        
        lastScrollY = currentScrollY;
        ticking = false;
    }

    // Обработчик события прокрутки с throttling
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateHeader();
            });
            ticking = true;
        }
    });

    // Обработчик наведения на верхнюю часть экрана
    let mouseTimer;
    document.addEventListener('mousemove', function(e) {
        if (e.clientY < 150) { // Если мышь в верхней части экрана
            clearTimeout(mouseTimer);
            header.classList.remove('hidden');
            header.style.opacity = '1';
        } else if (!mouseTimer && header.classList.contains('hidden')) {
            mouseTimer = setTimeout(() => {
                if (!header.matches(':hover')) {
                    header.classList.add('hidden');
                }
            }, 300);
        }
    });
}); 