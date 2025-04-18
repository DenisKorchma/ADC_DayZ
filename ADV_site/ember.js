document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ember').forEach((ember, index) => {
        const size = Math.random() * 8 + 4; // Увеличенный размер от 4px до 12px
        const duration = Math.random() * 5 + 8; // Длительность от 8 до 13 секунд
        const delay = Math.random() * 10; // Задержка от 0 до 10 секунд
        const leftPos = Math.random() * 100; // Позиция слева от 0 до 100%
        const animationType = Math.floor(Math.random() * 3) + 1; // Случайный тип анимации (1-3)
        
        ember.style.width = size + 'px';
        ember.style.height = size + 'px';
        ember.style.left = leftPos + '%';
        ember.style.animation = `float-up-${animationType} ${duration}s infinite linear ${delay}s`;
    });
}); 